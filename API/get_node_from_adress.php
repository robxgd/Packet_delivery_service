<?php
//header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../functions/func.php';

$mysqli = initialize_mysql_connection();

try{
    if (isset($_GET['streetname'])) $street = $_GET['streetname']; else $street = 'Bijlokevest';
    if (isset($_GET['housenumber'])) $number = $_GET['housenumber']; else $number = '120';

    $res = get_connected_node_from_addr($street,intval($number));
    echo $res;
}
catch (Exception $e) {
    echo 'error: ';
    $error = array("error" => $e->getMessage());
    echo json_encode($error);
}
close_mysql_connection();