<?php
/*
	author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
	created: oktober 2016
*/

function initialize_mysql_connection(){
	global $servername;
	global $username;
	global $password;
	global $dbname;
  	global $conn;

	// Create connection
	$conn = mysqli_connect($servername, $username, $password, $dbname);
	// Check connection
  if (mysqli_connect_errno()){
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
}

function close_mysql_connection(){
  global $conn;
	mysqli_close($conn);
}

?>
