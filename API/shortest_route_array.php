<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/func.php';

$mysqli = initialize_mysql_connection();

try{
    if (isset($_GET['nodes'])) $nodes = $_GET['nodes']; else $nodes = "1599971369,2053249807,246698847";
    $nodes = explode(",",$nodes);
    echo json_encode(AStar_Array($nodes));
}
catch (Exception $e) {
    $error = array("error" => $e->getMessage());
    echo json_encode($error);
}
close_mysql_connection();
?>