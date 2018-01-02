<?php
/**
 * Created by PhpStorm.
 * User: oskars
 * Date: 17.21.12
 * Time: 00:33
 */

namespace AppBundle\Helpers;


class DivisionLevels
{
    CONST DIVISION = ['level' => 1, 'points' => 1];
    CONST QFINAL = ['level' => 2, 'points' => 20];
    CONST SEMI_FINAL = ['level' => 3, 'points' => 30];
    CONST FINAL_FINAL_SECOND = ['level' => 4, 'points' => 40];
    CONST FINAL_FINAL_FIRST = ['level' => 5, 'points' => 80];

    CONST DIVISION_POINTS = 1;
    CONST QFINAL_POINTS = 20;
    CONST SEMI_FINAL_POINTS = 30;
    CONST FINAL_FINAL_POINTS = 40;
}