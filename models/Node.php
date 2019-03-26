<?php

class Node
{
    private $id;
    private $previous;
    private $neighbours = array();
    private $lat;
    private $lon;

    private $gScore;
    private $hScore;

    public function __construct($id, $lat, $lon)
    {
        $this->id = $id;
        $this->previous = NULL;
        $this->gScore = INF;
        $this->hScore = INF;
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getLat(){
        return $this->lat;
    }

    public function getLon(){
        return $this->lon;
    }

    public function setPrevious($prev)
    {
        $this->previous = $prev;
    }

    public function getPrevious()
    {
        return $this->previous;
    }

    public function addNeighbour($neighbour_id, $distance)
    {
        $this->neighbours[$neighbour_id] = $distance;
    }

    public function getNeighbours()
    {
        return $this->neighbours;
    }

    public function setG($score)
    {
        $this->gScore = $score;
    }

    public function setH($score)
    {
        $this->hScore = $score;
    }

    public function getG()
    {
        return $this->gScore;
    }

    public function getH()
    {
        return $this->hScore;
    }

    public function getF()
    {
        return $this->getG() + $this->getH();
    }
}

?>