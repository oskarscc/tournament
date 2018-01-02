<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Result
 *
 * @ORM\Table(name="result")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ResultRepository")
 */
class Result
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Team")
     * @ORM\JoinColumn(name="home_team", referencedColumnName="id", onDelete="cascade")
     */
    private $home;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Team")
     * @ORM\JoinColumn(name="guest_team", referencedColumnName="id")
     */
    private $guests;

    /**
     * @var bool
     *
     * @ORM\Column(name="win", type="boolean")
     */
    private $win;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=255)
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="division_name", type="string", length=255, nullable=true)
     */
    private $divisionName;

    /**
     * @var int
     *
     * @ORM\Column(name="points", type="integer")
     */
    private $points;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * @param Team $home
     */
    public function setHome($home): Result
    {
        $this->home = $home;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGuests()
    {
        return $this->guests;
    }

    /**
     * @param Team $guests
     */
    public function setGuests($guests): Result
    {
        $this->guests = $guests;

        return $this;
    }


    /**
     * Set win
     *
     * @param boolean $win
     *
     * @return Result
     */
    public function setWin($win): Result
    {
        $this->win = $win;

        return $this;
    }

    /**
     * Get win
     *
     * @return bool
     */
    public function getWin()
    {
        return $this->win;
    }

    /**
     * Set level
     *
     * @param string $level
     *
     * @return Result
     */
    public function setLevel($level): Result
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set points
     *
     * @param integer $points
     *
     * @return Result
     */
    public function setPoints($points): Result
    {
        $this->points = $points;

        return $this;
    }

    /**
     * Get points
     *
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @return string
     */
    public function getDivisionName()
    {
        return $this->divisionName;
    }

    /**
     * @param string $divisionName
     */
    public function setDivisionName($divisionName): Result
    {
        $this->divisionName = $divisionName;

        return $this;
    }
}

