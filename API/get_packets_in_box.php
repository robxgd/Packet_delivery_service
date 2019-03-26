<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/func.php';

$mysqli = initialize_mysql_connection();

try{
    if (isset($_GET['from_node'])) $from_node = $_GET['from_node']; else $from_node = '1599971369';
    if (isset($_GET['to_node'])) $to_node = $_GET['to_node']; else $to_node = '246698847';
    if (isset($_GET['uid'])) $uid = $_GET['uid']; else header("location: login.php");

    $temp = getPacketCoordinates($from_node, $to_node, $uid);

    echo $temp;
}
catch (Exception $e) {
    $error = array("error" => $e->getMessage());
    echo json_encode($error);
}
close_mysql_connection();
?>