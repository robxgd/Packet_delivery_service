<?php
/**
 * Created by PhpStorm.
 * User: robhofman
 * Date: 24/11/2018
 * Time: 18:34
 */

class Packet implements JsonSerializable
{
    private $lat;
    private $lon;
    private $closest_streetsegment; //this is an int and is the start int. so the line goes from this -> this +1
    private $dist_to_streetsegment = INF;
    private $id;
    private $u;
    private $node;

    public function __construct($lat, $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLat()
    {
        return $this->lat;
    }

    public function getLon()
    {
        return $this->lon;
    }

    public function get_dist_to_streetsegment()
    {
        return $this->dist_to_streetsegment;
    }

    public function getClosestStreetSegment()
    {
        return $this->closest_streetsegment;
    }

    public function getNode(){
        return $this->node;
    }

    public function getU(){
        return $this->u;
    }

    public function setStreetParams($dist, $segm, $u, $id, $node)
    {
        //echo($dist." ".$segm." ".$u." ".$id." ".$node.PHP_EOL);
        $this->closest_streetsegment = $segm;
        $this->dist_to_streetsegment = $dist;
        $this->id = $id;
        $this->u = $u;
        $this->node = $node;
    }

    public static function sort_arr_on_projection_dist(array &$arr)
    {
        usort($arr, function ($a, $b) {
            $dista = $a->get_dist_to_streetsegment();
            $distb = $b->get_dist_to_streetsegment();
            if ($dista == $distb) {
                return 0;
            }
            //return ($dista < $distb) ? -1 : 1;
            if ($dista < $distb) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    public static function sort_arr_on_place_on_route(array &$arr)
    {
        usort($arr, function ($a, $b) {
            $placea = $a->getClosestStreetSegment();
            $placeb = $b->getClosestStreetSegment();
            if ($placea == $placeb) {
                return 0;
            }
            //return ($dista < $distb) ? -1 : 1;
            if ($placea < $placeb) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $temp = [
            'id' => $this->id,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'closest_streetsegment' => $this->closest_streetsegment,
            'dist_to_streetsegment' => $this->dist_to_streetsegment,
            'node'=>$this->node,
            'u' => $this->u
        ];
        return $temp;
    }
}
