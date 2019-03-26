<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../functions/func.php';

$mysqli = initialize_mysql_connection();

try{
    if (isset($_GET['packet'])) $packet = $_GET['packet'];
    if (isset($_GET['status'])) {$status = $_GET['status'];}
    if (isset($_GET['uid'])) {$uid = $_GET['uid'];}
    else{
        $status = "busy";
    }
    if($packet && $status){
        echo updatePacketStatus($packet, $status, $uid);

    }
    //header('location: ../home.php');
}
catch (Exception $e) {
    echo 'error: ';
    $error = array("error" => $e->getMessage());
    echo json_encode($error);
}
close_mysql_connection();
?>