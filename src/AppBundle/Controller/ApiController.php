<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Team;
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

        $dataFilePath = $this->get('kernel')->getRootDir().'/../var/data/teams.txt';
//        dump((string) $divisionName);die;

        $em = $this->get('doctrine.orm.default_entity_manager');

        if(!file_exists($dataFilePath)){
            return new Response('No file provided for teams');
        }

        $teams = file($dataFilePath);

        $teamRepo = $em->getRepository(Team::class);

        do {
            $teamData = $this->getTeamData($teamRepo, $divisionName);

            if($teamData['teamCount'] >= 8){
                break;
            }

            $pickTeam = array_rand($teams);

            $teamName = trim($teams[$pickTeam]);
            $teamIncluded = $teamRepo->findOneBy(['name' => $teamName]);

            if(!$teamIncluded){

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
