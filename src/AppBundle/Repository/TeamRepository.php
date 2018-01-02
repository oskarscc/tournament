<?php

namespace AppBundle\Repository;

/**
 * TeamRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TeamRepository extends \Doctrine\ORM\EntityRepository
{

    public function getDivisions(){

        $qb = $this->createQueryBuilder('team');

        $query = $qb
            ->select('team.division')
            ->where('team.division IS NOT null')
            ->distinct()
            ->getQuery()
        ;

        $result = $query->getScalarResult();
        $divisions = array_column($result, "division");

        return $divisions;
    }

}
