<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Branch;
use AppBundle\Entity\Result;
use AppBundle\Entity\Team;
use AppBundle\Helpers\DivisionLevels;
use AppBundle\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/api")
 */
class ApiController extends Controller
{
    CONST TEAM_COUNT_PER_DEVISION = 8;

    /**
     * @Route("/generate-division/{divisionName}", name="generate_division")
     */
    public function generateDivisionAction(Request $request, $divisionName)
    {

        $dataFilePath = $this->get('kernel')->getRootDir() . '/../var/data/teams.txt';
        $em = $this->get('doctrine.orm.default_entity_manager');

        if (!file_exists($dataFilePath)) {
            return new Response('No file provided for teams');
        }

        $teams = file($dataFilePath);
        $teamRepo = $em->getRepository(Team::class);

        do {

            $teamData = $this->getTeamData($teamRepo, $divisionName);

            if ($teamData['teamCount'] >= self::TEAM_COUNT_PER_DEVISION) {
                break;
            }

            $pickTeam = array_rand($teams);

            $teamName = trim($teams[$pickTeam]);
            $teamIncluded = $teamRepo->findOneBy(['name' => $teamName]);

            if (!$teamIncluded) {

                $newTeam = new Team();
                $newTeam
                    ->setName($teamName)
                    ->setDivision($divisionName);

                $em->persist($newTeam);
                $em->flush();
            }

            $teamData = $this->getTeamData($teamRepo, $divisionName);

        } while ($teamData['teamCount'] <= self::TEAM_COUNT_PER_DEVISION);

        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($teamData['teams'], 'json');

        return new Response($jsonContent);
    }

    /**
     * @Route("/division-games/{divisionName}", name="division_games")
     */
    public function playDivisionAction(Request $request, $divisionName)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');

        $teamRepo = $em->getRepository(Team::class);
        $resultRepo = $em->getRepository(Result::class);

        $divisionTeams = $teamRepo->findBy(['division' => $divisionName]);
        $hasPlayed = $resultRepo->findBy(['divisionName' => $divisionName]);

        if(!empty($hasPlayed)){
            return new Response("This division has already played");
        }

        if(empty($divisionTeams)){
            return new Response("No teams for this division");
        }

        // Each team plays with each for current division

        foreach ($divisionTeams as $home){
            foreach ($divisionTeams as $guest){

                /**@var Team $home */
                /**@var Team $guest */

                if($home->getId() != $guest->getId()){

                    $matchWon = (bool)random_int(0, 1);

                    $thisResult = new Result();
                    $thisResult->setHome($home)
                        ->setGuests($guest)
                        ->setWin($matchWon)
                        ->setLevel(DivisionLevels::DIVISION['level'])
                        ->setPoints($matchWon ? DivisionLevels::DIVISION['points'] : 0)
                        ->setDivisionName($divisionName)
                    ;

                    $em->persist($thisResult);
                }
            }
        }

        $em->flush();

        return $this->json(
            [
            'status' => 'Success',
            'message' => sprintf('teams of %s division finished games!', $divisionName),
            ]
        );
    }

    /**
     * @Route("/qfinal-games", name="qfinal_games")
     */
    public function playQFinalAction(Request $request)
    {
        $MAX_RESULT = 4;
        $em = $this->get('doctrine.orm.default_entity_manager');

        $teamRepo = $em->getRepository(Team::class);
        $resultRepo = $em->getRepository(Result::class);
        $branchRepo = $em->getRepository(Branch::class);

        /** @var array $validDivisions */
        $validDivisions = $teamRepo->getDivisions();
        $divisionSplit = [];

        foreach ($validDivisions as $key => $division){

            $best4Teams = $resultRepo->getBest4TeamsbyDivisionAndLevel($division, DivisionLevels::DIVISION['level'], $MAX_RESULT);

            // any other team than first one returns best teams in reverse order
            $divisionSplit[$division] = $key == 0 ? $best4Teams: array_reverse($best4Teams);
        }

        //check if qfinals are not found in result table already

        $qFinalEntries = $resultRepo->findBy(['level' => DivisionLevels::QFINAL['level']]);

        if(count($qFinalEntries) != 0){

            return $this->json([
                'status' => 'fail',
                'message' => 'q-finals already played!'
            ]);
        }

        $resultBag = [];

        for($i = 0; $i < $MAX_RESULT; $i++){

            $branchNo = ($i < $MAX_RESULT / 2) ? 1 : 2;
            $thisBranch = $branchRepo->find($branchNo);

            $home = $teamRepo->findOneBy(['id' => $divisionSplit[$validDivisions[0]][$i]['id']]);
            $guest = $teamRepo->findOneBy(['id' => $divisionSplit[$validDivisions[1]][$i]['id']]);

            $matchWon = (bool)random_int(0, 1);

            $thisResult = new Result();
            $thisResult->setHome($home)
                ->setGuests($guest)
                ->setWin($matchWon)
                ->setLevel(DivisionLevels::QFINAL['level'])
                ->setPoints($matchWon ? DivisionLevels::QFINAL['points'] : 0)
            ;

            $em->persist($thisResult);

            $resultBag[] = $thisResult;
        }

        $em->flush();

        return $this->json([
            'status' => 'Qfinal games finished',
            'message' => 'Db cleared',
            'ŗesult_bag' => $resultBag,
        ]);

    }

    /**
     * @Route("/clean-db", name="clean_db")
     */
    public function cleanDbAction(Request $request)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');
        $teamRepo = $em->getRepository('AppBundle:Team');

        $allTeams = $teamRepo->findAll();

        foreach ($allTeams as $team) {

            $em->remove($team);
        }

        $em->flush();

        return $this->json([
            'status' => 'Success',
            'message' => 'Db cleared'
        ]);
    }

    /**
     * @Route("/setup-branches", name="setup_branches")
     */
    public function setUpBranches(Request $request)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');
        $branchRepo = $em->getRepository('AppBundle:Branch');

        $branches = ['A', 'B'];

        foreach ($branches as $branch) {

            $thisBranch = $branchRepo->findBy(['name' => $branch]);
            if($thisBranch == null) {

                $newBranch = new Branch();
                $newBranch->setName($branch);
                $em->persist($newBranch);
            }
        }

        $em->flush();

        return $this->json([
            'status' => 'Success',
            'message' => 'Branches created'
        ]);
    }

    /**
     * @param TeamRepository $teamRepo
     * @param $divisionName
     * @return array
     */
    private function getTeamData(TeamRepository $teamRepo, $divisionName)
    {
        $thisDivisionTeams = $teamRepo->findBy(['division' => $divisionName]);
        $thisDivisionTeamCount = count($thisDivisionTeams);

        return [
            'teamCount' => $thisDivisionTeamCount,
            'teams' => $thisDivisionTeams
        ];
    }
}
