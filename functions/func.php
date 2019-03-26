<?php

require_once '../models/Node.php';
require_once '../models/NodeList.php';
require_once '../models/Packet.php';
function initialize_mysql_connection()
{
    global $servername;
    global $username;
    global $password;
    global $dbname;

    // Create connection
    $mysqli = new mysqli($servername, $username, $password, $dbname);

    if ($mysqli->connect_errno) {
        echo "Sorry, this website is experiencing problems.";
        echo "Error: Failed to make a MySQL connection, here is why: \n";
        echo "Errno: " . $mysqli->connect_errno . "\n";
        echo "Error: " . $mysqli->connect_error . "\n";
        exit;
    }
    return $mysqli;
}

function AStarJSON($from_id, $to_id)
{
    return json_encode(Astar_cached($from_id, $to_id));
}

function Astar_cached($from_id, $to_id)
{
    $cacheFile = 'cache.json';
    if (file_exists($cacheFile)) {
        $file = fopen($cacheFile, 'r');
        $results = fgets($file);
        fclose($file);
        $results = json_decode($results, $assoc = true);
        if ($results == null) {
            $results = [];
        }
        $found = false;
        for ($i = 0; $i < count($results) and !$found; ++$i) {
            if ($results[$i]['from_node'] == $from_id and $results[$i]['to_node'] == $to_id) {
                $result = $results[$i];
                //LRU cache -> move to front
                array_splice($results, $i, 1);
                array_unshift($results, $result);
                $found = true;
            }
        }
        if (!$found) {
            $result = AStar($from_id, $to_id);
            array_unshift($results, $result);
        }
        $max_entries = 100;
        if (count($results) > $max_entries) {
            //remove last elements
            $results = array_slice($results, 0, $max_entries);
        }
        $file = fopen($cacheFile, 'w');
        fwrite($file, JSON_encode($results));
        fclose($file);
    } else {
        $file = fopen($cacheFile, 'w');
        $result = AStar($from_id, $to_id);
        fwrite($file, JSON_encode([$result]));
        fclose($file);
    }
    return $result;
}


function AStar($from_id, $to_id)
{
    $nodeList = new NodeList();
    $nodeList = fetch_nodes_near($from_id, $nodeList);
    if (!$nodeList->contains($to_id)) {
        $nodeList = fetch_nodes_near($to_id, $nodeList);
    }
    $openList = new NodeList();
    $closedList = new NodeList();
    $arrive_only = fetch_arrive_only();
    $goal = $nodeList->get($to_id);
    $start = $nodeList->get($from_id);

    if ($goal == null or $start == null) {
        //if (in_array($from_id, $arrive_only) or in_array($to_id, $arrive_only)) {
        //    throw new Exception('ASTAR: goal or start is arrive_only');
        //}
        throw new Exception('ASTAR: goal or start is null');
    }

    $start->setG(0);
    $start->setH(heuristic_cost_estimate($start, $goal));
    $openList->add($start);

    while (!$openList->isEmpty()) {
        $currentNode = $openList->extractBest();
        $closedList->add($currentNode);
        if ($currentNode->getID() === $goal->getID()) {
            return generatePath($start, $goal);
        }
        $neighbours = $currentNode->getNeighbours();
        foreach ($neighbours as $neighbour_id => $distance) { //key, value pairs key = id, distance is value
            $neighbour = $nodeList->get($neighbour_id);
            if ($neighbour == null) {
                if (in_array($neighbour_id, $arrive_only)) {
                    //has no neighbours, no need to add to open list
                    //only a problem if goal is arrive only
                    continue;
                    //$latlon = get_lat_lon($neighbour_id);
                    //$neighbour = new Node($neighbour_id, $latlon[0], $latlon[1]);
                } else {
                    $nodeList = fetch_nodes_near($neighbour_id, $nodeList);
                    $neighbour = $nodeList->get($neighbour_id);
                    if ($neighbour == null) {
                        throw new Exception('ASTAR: failed to fetch node: ' . $neighbour_id);
                    }
                }
            }
            //the distance from start to a neighbour
            $tentative_G = $currentNode->getG() + $distance;

            if ($openList->contains($neighbour_id)){
                if($tentative_G >= $neighbour->getG()){
                    continue;
                }
            }elseif($closedList->contains($neighbour_id)){
                if($tentative_G >= $neighbour->getG()){
                    continue;
                }
                $closedList->remove($neighbour);
                $openList->add($neighbour);
            }else{
                $openList->add($neighbour);
                $neighbour->setH(heuristic_cost_estimate($neighbour, $goal));
            }
            $neighbour->setG($tentative_G);
            $neighbour->setPrevious($currentNode);
        }
    }

    throw new Exception("ASTAR: No route found from " . $from_id . " to " . $to_id);
}

function heuristic_cost_estimate(Node $from, Node $to)
{
    return haversineGreatCircleDistance($from->getLat(), $from->getLon(), $to->getLat(), $to->getLon());
    # return 1000 * sqrt((($from->getLat() - $to->getLat())*111.25) ** 2 + (($from->getLon() - $to->getLon())*70.2) ** 2); //guesstimate van de afstand in meter
}

function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function generatePath(Node $from_node, Node $to_node)
{
    $path_geo = array();
    $current = $to_node;
    while ($current != $from_node) {
        $path_geo[] = array($current->getLat(), $current->getLon());
        $current = $current->getPrevious();
    }
    $path_geo[] = array($from_node->getLat(), $from_node->getLon());

    $output = array(
        "from_node" => $from_node->getID(),
        "to_node" => $to_node->getID(),
        "path_geo" => array_reverse($path_geo),
        "distance" => $to_node->getG()
    );
    return $output;
}

function fetch_arrive_only()
{
    global $mysqli;
    $sql = "SELECT neighbour_id FROM osm_node_neighbours_bike where neighbour_id not in (SELECT node_id FROM osm_node_neighbours_bike)";
    $ret = $mysqli->query($sql);
    if (!$ret) {
        throw new Exception("Unable to execute " . $sql);
    }
    $ids = array();
    $count = 0;
    while ($ret && $row = $ret->fetch_assoc()) {
        $ids[] = $row['neighbour_id'];
    }
    return $ids;
}

function fetch_nodes_near($node_id, $nodelist)
{
    global $mysqli;

    $res = get_lat_lon($node_id);
    $frac = 0.02;
    $floor_lat = floorToNearestFraction($res[0], $frac);
    $floor_lon = floorToNearestFraction($res[1], $frac);
    // echo 'fetching nodes near '.$res[0].','.$res[1].'=>'.$floor_lat.','.$floor_lon.PHP_EOL;
    $sql = "select node_id, node_lat, node_lon ,neighbour_id, distance from osm_node_neighbours_bike where node_lat<=" . ($floor_lat + $frac) . " and node_lat>=" . $floor_lat . " and node_lon<=" . ($floor_lon + $frac) . " and node_lon>=" . $floor_lon;
    // echo $sql.PHP_EOL;

    $ret = $mysqli->query($sql);
    if (!$ret) {
        throw new Exception("Unable to execute " . $sql);
    }
    while ($ret && $row = $ret->fetch_assoc()) {
        $from_id = $row['node_id'];
        $to_id = $row['neighbour_id'];
        $node = $nodelist->get($from_id);
        if (!($node)) {
            $t1 = floatval($row['node_lat']);
            $t2 = floatval($row['node_lon']);

            $node = new Node($from_id, $t1, $t2);
            $nodelist->add($node);
        }
        $node->addNeighbour($to_id, floatval($row['distance']));
    }
    return $nodelist;
}

function floorDec($val, $precision = 2)
{
    if ($precision < 0) {
        $precision = 0;
    }
    $numPointPosition = intval(strpos($val, '.'));
    if ($numPointPosition === 0) { //$val is an integer
        return strval($val);
    }
    return substr($val, 0, $numPointPosition + $precision + 1);
}

function floorToNearestFraction($number, $fractionAsDecimal)
{
    $factor = 1 / $fractionAsDecimal;
    return floor($number * $factor) / $factor;
}

function get_lat_lon($node_id)
{
    global $mysqli;
    $sql = "select lat,lon from osm_nodes where id=" . $node_id;
    $ret = $mysqli->query($sql);
    if (!$ret) {
        throw new Exception("Unable to execute " . $sql);
    }
    $row = $ret->fetch_assoc();
    $lat = $row['lat'];
    $lon = $row['lon'];
    return [$lat, $lon];
}

function AStar_Array($nodearray)
{
    $result = Astar_cached($nodearray[0], $nodearray[1]);
    $path_geo = $result['path_geo'];
    $distance = $result['distance'];
    for ($x = 1; $x < count($nodearray) - 1; $x++) {
        $from_id = $nodearray[$x];
        $to_id = $nodearray[$x + 1];
        $result = Astar_cached($from_id, $to_id);
        array_pop($path_geo);
        $path_geo = array_merge($path_geo, $result['path_geo']);
        $distance += $result['distance'];
    }
    $output = array(
        "to_ids" => $nodearray,
        "path_geo" => $path_geo,
        "distance" => $distance
    );
    return $output;
}

function AStar_lat_lon($from_lat, $from_lon, $to_lat, $to_lon)
{
    $from_id = get_node_ID_near($from_lat, $from_lon);
    $to_id = get_Node_ID_near($to_lat, $to_lon);
    return AStarJSON($from_id, $to_id);
}

function get_Node_ID_near($lat, $lon)
{
    global $mysqli;
    $sql = "select id from osm_nodes_bike where abs(lat-" . $lat . ")<0.001 and abs(lon-" . $lon . ")<0.001 order by POW(lat-" . $lat . ",2)+POW(lon-" . $lon . ",2) limit 1";
    $ret = $mysqli->query($sql);
    if ($ret && $row = $ret->fetch_assoc()) {
        return $row['id'];
    }
    throw new Exception("Error or No Result: " . $sql);
}

function getPacketCoordinates($from, $to, $uid)
{
    global $mysqli;
    $latlngs = [];
    $path = Astar_cached($from, $to);
    $path_latlngs = $path['path_geo'];
    $id_arr = [];
    $sql = "SELECT lat, lon, id, node FROM `packets` WHERE status='idle' OR (status='busy' AND courier_id=".$uid.")";
    $retval = $mysqli->query($sql);
    if (!$retval) {
        throw new Exception("Unable to execute " . $sql);
    }
    while ($row = $retval->fetch_assoc()) {
        $latlngs[] = [floatval($row["lat"]), floatval($row["lon"]), $row["id"], $row["node"]];
    }
    return get_shortest_distances_packets($latlngs, $path_latlngs);
    //return false;
}

function get_shortest_distances_packets($latlngs, $path)
{

    $lats = [];
    $lons = [];
    $testpath_lon = [];
    $testpath_lat = [];
    $packets_in_bounding_box = [];
    $treshold = 0.002;
    //unpack latlngs into lats and lons
    foreach ($latlngs as &$value) {
        $lats[] = $value[0];
        $lons[] = $value[1];
    }
    foreach ($path as &$value) {
        $testpath_lat[] = &$value[0];
        $testpath_lon[] = &$value[1];
    }

    //bounding box lat/lon
    $max_lon = floatval((max($testpath_lon) + $treshold));
    $min_lon = floatval((min($testpath_lon) - $treshold));
    $max_lat = floatval((max($testpath_lat) + $treshold));
    $min_lat = floatval((min($testpath_lat) - $treshold));

    for ($i = 0; $i < count($latlngs); $i++) {
        //check if packets are in bounding box && push them in the array

        if ($lats[$i] >= $min_lat && $lats[$i] <= $max_lat && $lons[$i] <= $max_lon && $lons[$i] >= $min_lon) {
            $p = new Packet($lats[$i], $lons[$i]);
            array_push($packets_in_bounding_box, $p);

            //calculate distance to path
            //0 loop over all street segments
            for ($l = 0; $l < count($testpath_lat) - 1; $l++) {
                //1 check if packet location can be projected on street segment
                if ($testpath_lat[$l] == $testpath_lat[$l + 1] && $testpath_lon[$l] == $testpath_lon[$l + 1]) continue; //$testpath_lat[$l] -= 0.00001;

                $u = (($p->getLat() - $testpath_lat[$l]) * ($testpath_lat[$l + 1] - $testpath_lat[$l])) +
                    (($p->getLon() - $testpath_lon[$l]) * ($testpath_lon[$l + 1] - $testpath_lon[$l]));

                $udenom = pow($testpath_lat[$l + 1] - $testpath_lat[$l], 2) + pow($testpath_lon[$l + 1] - $testpath_lon[$l], 2);
                $u /= $udenom;

                if ($u < 0) {
                    $temp_lat = $testpath_lat[$l];
                    $temp_lon = $testpath_lon[$l];
                    $u = 0;
                } elseif ($u > 1) {
                    $temp_lat = $testpath_lat[$l + 1];
                    $temp_lon = $testpath_lon[$l + 1];
                    $u = 1;
                } else {
                    $temp_lat = $testpath_lat[$l] + ($u * ($testpath_lat[$l + 1] - $testpath_lat[$l]));
                    $temp_lon = $testpath_lon[$l] + ($u * ($testpath_lon[$l + 1] - $testpath_lon[$l]));
                }
                $distance = sqrt(pow((($temp_lat - $p->getLat()) * 111.25), 2) + pow((($temp_lon - $p->getLon()) * 70.19), 2)) * 1000;
                if ($distance < $p->get_dist_to_streetsegment()) {
                    //echo($latlngs[$l][2]);
                    $p->setStreetParams($distance, $l, $u, $latlngs[$i][2], $latlngs[$i][3]);
                }

                /*$temp_lat = $testpath_lat[$l] + ($u * ($testpath_lat[$l+1] - $testpath_lat[$l]));
                $temp_lon = $testpath_lon[$l] + ($u * ($testpath_lon[$l+1] - $testpath_lon[$l]));
                $minx = min(floatval($testpath_lat[$l]), floatval($testpath_lat[$l+1]));
                $maxx = max(floatval($testpath_lat[$l]), floatval($testpath_lat[$l+1]));

                $miny = min(floatval($testpath_lon[$l]), floatval($testpath_lon[$l+1]));
                $maxy = max(floatval($testpath_lon[$l]), floatval($testpath_lon[$l+1]));
                $isValid = ($temp_lat>= $minx && $temp_lat <= $maxx) && ($temp_lon >= $miny && $temp_lon <= $maxy);

                if($isValid){
                    //2 if true calculate distance
                    ;

                    $distance = sqrt(pow((($temp_lat-$p->getLat())*111.25),2) + pow((($temp_lon - $p->getLon())*70.19),2))*1000;
                    //3 keep shortest distance
                    if($distance < $p->get_dist_to_streetsegment()){
                        $p->setStreetParams($distance, $l, $temp_lon, $temp_lat);
                    }
                }*/

            }

        }

    }
    $packetcounter = 0;
    $res = [];

    //sort packets
    Packet::sort_arr_on_projection_dist($packets_in_bounding_box);
    foreach ($packets_in_bounding_box as &$value) {
        $res[] = $value->jsonSerialize();
    }
    return json_encode($res, JSON_FORCE_OBJECT);

}

function updatePacketStatus($packet, $status, $uid)
{
    $sql = "UPDATE `packets` SET `status` = '" . $status . "', `courier_id`='.$uid.' WHERE `id`=" . $packet;
    global $mysqli;
    $retval = $mysqli->query($sql);
    if (!$retval) {
        throw new Exception("Unable to execute " . $sql);
    } else return $packet;

}

function get_connected_node_from_addr($streetname, $housenumber){
    $node_id=get_node_from_addr($streetname, $housenumber);
    $latlon=get_lat_lon($node_id);
    return get_Node_ID_near($latlon[0],$latlon[1]);
}

function get_node_from_addr($streetname, $housenumber)
{
    $sql = "SELECT node_id, v FROM (SELECT node_id, way_id FROM osm_way_nodes WHERE  seq = 1) AS W
    INNER JOIN (SELECT way_id, v FROM   osm_way_tags WHERE  k = 'addr:housenumber' AND way_id IN
        (SELECT way_id FROM   osm_way_tags WHERE  k = 'addr:street' AND Lower(v) LIKE '" . strtolower($streetname) . "')
    ) AS S ON S.way_id = W.way_id ";
    global $mysqli;
    $retval = $mysqli->query($sql);
    if (!$retval) {
        throw new Exception("Unable to execute " . $sql);
    }
    $check_later = [];
    while ($retval && $row = $retval->fetch_assoc()) {
        $number = $row['v'];
        $number = preg_replace('/[^0-9;-]/', "", $number);
        $number = explode(";", $number);
        for ($x = 0; $x < count($number); $x++) {
            preg_match_all('/\d+/', $number[$x], $matches);
            if (count($matches[0]) > 1) {
                $check_later[] = [$matches[0], $row['node_id']];
            } elseif (intval($matches[0][0]) == $housenumber) {
                return $row['node_id'];
            }
        }
    }
    for ($x = 0; $x < count($check_later); $x++) {
        $temp = array_map('intval', $check_later[$x][0]);
        $max = max($temp);
        $min = min($temp);
        if ($housenumber <= $max and $housenumber >= $min) return $check_later[$x][1];
    }

    return null;
}

?>
