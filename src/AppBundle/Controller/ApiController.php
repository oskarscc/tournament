<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Result;
use AppBundle\Entity\Team;
use AppBundle\Helpers\DIvisionLevels;
use AppBundle\Repository\TeamRepository;
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
                        ->setLevel($matchWon ? DIvisionLevels::DIVISION : 0)
                        ->setPoints($matchWon ? DIvisionLevels::DIVISION_POINTS : 0)
                        ->setDivisionName($divisionName)
                    ;

                    $em->persist($thisResult);
                }
            }
        }

        $em->flush();

        return new Response(sprintf('teams of %s division finished games!', $divisionName));

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

        /** @var array $validDivisions */
        $validDivisions = $teamRepo->getDivisions();

        foreach ($validDivisions as $division){

            $best4Teams = $resultRepo->getBest4TeamsbyDivisionAndLevel($division, DIvisionLevels::DIVISION, $MAX_RESULT);

            dump($best4Teams);
        }

        die;

        return new Response(sprintf('Q-final games finished!'));

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
