<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Branch;
use AppBundle\Entity\Result;
use AppBundle\Entity\Team;
use AppBundle\Helpers\DivisionLevels;
use AppBundle\Repository\ResultRepository;
use AppBundle\Repository\TeamRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
     * @Route("/generate-division", name="generate_division")
     */
    public function generateDivisionAction(Request $request)
    {
        $divisionName = $request->query->get('divisionName');

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

        $jsonContent = $this->serializeJson($teamData['teams']);

        return new Response($jsonContent);
    }

    /**
     * @Route("/division-games", name="division_games")
     */
    public function playDivisionAction(Request $request)
    {
        $divisionName = $request->query->get('divisionName');

        $em = $this->get('doctrine.orm.default_entity_manager');

        $teamRepo = $em->getRepository(Team::class);
        /** @var ResultRepository $resultRepo */
        $resultRepo = $em->getRepository(Result::class);

        $divisionTeams = $teamRepo->findBy(['division' => $divisionName]);
        $hasPlayed = $resultRepo->findBy(['divisionName' => $divisionName]);

        if (!empty($hasPlayed)) {

            $resultBucket = $resultRepo->findBy(['divisionName' => $divisionName, 'level' => DivisionLevels::DIVISION['level']]);
            $jsonContent = $this->serializeJson($resultBucket);

            return new Response($jsonContent);
        }

        if (empty($divisionTeams)) {
            return new Response("No teams for this division");
        }

        // Each team plays with each for current division
        $resultBucket = [];

        foreach ($divisionTeams as $home) {
            foreach ($divisionTeams as $guest) {

                /**@var Team $home */
                /**@var Team $guest */

                if ($home->getId() != $guest->getId()) {

                    $matchWon = (bool)random_int(0, 1);

                    $thisResult = new Result();
                    $thisResult->setHome($home)
                        ->setGuests($guest)
                        ->setWin($matchWon)
                        ->setLevel(DivisionLevels::DIVISION['level'])
                        ->setPoints($matchWon ? DivisionLevels::DIVISION['points'] : 0)
                        ->setDivisionName($divisionName);

                    $em->persist($thisResult);
                    $resultBucket[] = $thisResult;
                }
            }
        }

        $em->flush();

        $resultBucket = $resultRepo->findBy(['divisionName' => $divisionName, 'level' => DivisionLevels::DIVISION['level']]);
        $jsonContent = $this->serializeJson($resultBucket);

        return new Response($jsonContent);
    }

    /**
     * @Route("/playoff-games", name="playoff_games")
     */
    public function playOffForwarderAction(Request $request)
    {
        $divisionName = $request->query->get('playoffLevel');

        switch ($divisionName) {
            case ('qfinal'):
                $response = $this->forward('AppBundle:Api:playQFinal',['request' => $request] );
                break;
            case ('semifinal'):
                $response = $this->forward('AppBundle:Api:playSemiFinal',['request' => $request] );
                break;
            case ('final'):
                $response = $this->forward('AppBundle:Api:playFinals',['request' => $request] );
                break;
        }

        return $response;
    }

    /**
     * @Route("/playoff-games-qfinal", name="playoff-games-qfinal")
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

        foreach ($validDivisions as $key => $division) {

            $best4Teams = $resultRepo->getBest4TeamsbyDivisionAndLevel($division, DivisionLevels::DIVISION['level'], $MAX_RESULT);

            // any other team than first one returns best teams in reverse order
            $divisionSplit[$division] = $key == 0 ? $best4Teams : array_reverse($best4Teams);
        }

        //check if qfinals are found in result table already
        $qFinalEntries = $resultRepo->findBy(['level' => DivisionLevels::QFINAL['level']]);

        if (count($qFinalEntries) != 0) {

            $resultBucket = $resultRepo->findBy(['level' => DivisionLevels::QFINAL['level']]);
            $jsonContent = $this->serializeJson($resultBucket);

            return new Response($jsonContent);
        }

        $allBranches = $branchRepo->findAll();

        for ($i = 0; $i < $MAX_RESULT; $i++) {

            $thisBranch = ($i < $MAX_RESULT / 2) ? $allBranches[0] : end($allBranches);

            $home = $teamRepo->findOneBy(['id' => $divisionSplit[$validDivisions[0]][$i]['id']]);
            $guest = $teamRepo->findOneBy(['id' => $divisionSplit[$validDivisions[1]][$i]['id']]);

            $this->playMatch($em, DivisionLevels::QFINAL, $home, $guest);

            $home->setBranch($thisBranch);
            $guest->setBranch($thisBranch);
        }

        $em->flush();

        $resultBucket = $resultRepo->findBy(['level' => DivisionLevels::QFINAL['level']]);
        $jsonContent = $this->serializeJson($resultBucket);

        return new Response($jsonContent);

    }

    /**
     * @Route("/playoff-games-semifinal", name="playoff_games_semifinal")
     */
    public function playSemiFinalAction(Request $request)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');

        $resultRepo = $em->getRepository(Result::class);
        $branchRepo = $em->getRepository(Branch::class);

        $validBranches = $branchRepo->findAll();

        $bestTeamsByBranches = [];

        foreach ($validBranches as $branch) {

            $twoBestByBranch = $resultRepo->twoBestByBranch($branch, DivisionLevels::QFINAL['level']);
            $bestTeamsByBranches[$branch->getName()] = $twoBestByBranch;
        }

        //check if semi-finals are found in result table already
        $semiFinalEntries = $resultRepo->findBy(['level' => DivisionLevels::SEMI_FINAL['level']]);

        if (count($semiFinalEntries) != 0) {

            $resultBucket = $resultRepo->findBy(['level' => DivisionLevels::SEMI_FINAL['level']]);
            $jsonContent = $this->serializeJson($resultBucket);

            return new Response($jsonContent);
        }

        foreach ($bestTeamsByBranches as $key => $branchTeams) {


            $home = $branchTeams[0]->getHome();
            $guest = $branchTeams[1]->getHome();

            $this->playMatch($em, DivisionLevels::SEMI_FINAL, $home, $guest);
        }

        $em->flush();

        $resultBucket = $resultRepo->findBy(['level' => DivisionLevels::SEMI_FINAL['level']]);
        $jsonContent = $this->serializeJson($resultBucket);

        return new Response($jsonContent);
    }

    /**
     * @Route("/playoff-games-final", name="playoff_games_final")
     */
    public function playFinalsAction(Request $request)
    {
        $resultBucket = [];

        $em = $this->get('doctrine.orm.default_entity_manager');
        $resultRepo = $em->getRepository(Result::class);

        $semiFinalGameCount = $resultRepo->findBy(['level' => DivisionLevels::FINAL_FINAL_FIRST['level']]);

        if (count($semiFinalGameCount) != 0) {

            $resultBucket1 = $resultRepo->findBy(['level' => DivisionLevels::FINAL_FINAL_FIRST['level']]);
            $resultBucket2 = $resultRepo->findBy(['level' => DivisionLevels::FINAL_FINAL_SECOND['level']]);

            $jsonContent = $this->serializeJson(array_merge($resultBucket1, $resultBucket2));

            return new Response($jsonContent);
        }

        $semiFinalGames = $resultRepo->semiFinalGames(DivisionLevels::SEMI_FINAL['level']);

        $winners = [];
        $loosers = [];

        foreach ($semiFinalGames as $game) {
            $winners[] = $game->getHome();
            $loosers[] = $game->getGuests();
        }

        //play for third place
        $this->playMatch($em, DivisionLevels::FINAL_FINAL_SECOND, $loosers[0], $loosers[1]);

        //play for first place
        $this->playMatch($em, DivisionLevels::FINAL_FINAL_FIRST, $winners[0], $winners[1]);

        $resultBucket1 = $resultRepo->findBy(['level' => DivisionLevels::FINAL_FINAL_FIRST['level']]);
        $resultBucket2 = $resultRepo->findBy(['level' => DivisionLevels::FINAL_FINAL_SECOND['level']]);

        $jsonContent = $this->serializeJson(array_merge($resultBucket1, $resultBucket2));

        return new Response($jsonContent);
    }

    /**
     * @Route("/final-results", name="final_results")
     */
    public function finalResultsAction(Request $request)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');

        $resultRepo = $em->getRepository(Result::class);
        $finalResults = $resultRepo->getFinalResults();

        $jsonContent = $this->serializeJson($finalResults);

        return new Response($jsonContent);
    }

    private function playMatch(EntityManager $em, $level, Team $home, Team $guest)
    {
        $matchWon = (bool)random_int(0, 1);

        $thisResult = new Result();
        $thisResult->setHome($matchWon ? $home : $guest)
            ->setGuests($matchWon ? $guest : $home)
            ->setWin(true)
            ->setLevel($level['level'])
            ->setPoints($level['points']);

        $otherResult = new Result();
        $otherResult->setHome($matchWon ? $guest : $home)
            ->setGuests($matchWon ? $home : $guest)
            ->setWin(false)
            ->setLevel($level['level'])
            ->setPoints($level['points'] * 0.7);

        $em->persist($thisResult);
        $em->persist($otherResult);

        $em->flush();
    }

    /**
     * @Route("/clean-db", name="clean_db")
     */
    public function cleanDbAction(Request $request)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');
        $teamRepo = $em->getRepository(Team::class);
        $resultRepo = $em->getRepository(Result::class);
        $branchRepo = $em->getRepository(Branch::class);

        $allTeams = $teamRepo->findAll();
        $allResults = $resultRepo->findAll();
        $allBranches = $branchRepo->findAll();

        foreach ($allBranches as $branch) {

            $em->remove($branch);
        }

        $em->flush();

        foreach ($allResults as $result) {

            $em->remove($result);
        }

        foreach ($allTeams as $team) {

            $em->remove($team);
        }

        $em->flush();

        foreach ($allResults as $result) {

            $em->remove($result);
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
            if ($thisBranch == null) {

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

    /**
     * @param $data
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function serializeJson($data)
    {
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($data, 'json');

        return $jsonContent;
    }
}
