<?php
/*
  author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
  created: oktober 2016
*/

include 'config/config.php';
include 'config/function.php';

initialize_mysql_connection();

$user_id = $_POST["user_id"];
$lat = $_POST["lat"];
$lon = $_POST["lon"];
$acc = $_POST["acc"];

// check if timestamp exists
$sql = "SELECT * FROM geolocation WHERE user_id =  '$user_id'";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) == 0){
  $sql = "INSERT INTO geolocation (user_id, lat, lon, acc, changed) VALUES ('$user_id','$lat','$lon','$acc',NOW())";
  mysqli_query($conn, $sql);
}
else{
  $sql = "UPDATE geolocation SET lat='$lat', lon='$lon', acc='$acc', changed=NOW() WHERE  user_id =  '$user_id'";
  mysqli_query($conn, $sql);
}

//retrieve locations from the other users in the database
$sql = "SELECT user_id, lat, lon, acc, changed FROM geolocation";
$retval = mysqli_query($conn, $sql);
$n = mysqli_num_rows($retval);
$geoloc = array();
for($i = 0; $i < $n; $i++){
	//$geoloc[] = mysqli_fetch_array($retval, MYSQL_NUM);
    $geoloc[] = $retval->fetch_array(MYSQLI_NUM);

}

header('Content-Type: application/json');
echo json_encode($geoloc);


close_mysql_connection();

?>
