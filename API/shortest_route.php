<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/func.php';

$mysqli = initialize_mysql_connection();

try{
    if (isset($_GET['from_node'])) $from_node = $_GET['from_node']; else $from_node = '1599971369';
    if (isset($_GET['to_node'])) $to_node = $_GET['to_node']; else $to_node = '246698847';
    //echo 'Calling AStar : '.$from_node.', '.$to_node.PHP_EOL;
    echo AStarJSON($from_node, $to_node);
}
catch (Exception $e) {
    echo 'error: ';
    $error = array("error" => $e->getMessage());
    echo json_encode($error);
}
close_mysql_connection();
?>