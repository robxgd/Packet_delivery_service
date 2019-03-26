<?php
/*
  author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
  created: oktober 2016
*/
session_start();
$_SESSION['user_id'] = null;
header("location: login.php");
 ?>
