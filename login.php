<?php
/*
  author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
  created: oktober 2016
*/
if(isset($_POST['login'])){
  $usr = $_POST['username'];
  $psw = $_POST['password'];

  include 'models/User.php';
  $user = new User;
  if($user->load_user($usr, $psw)){
    session_start();
    $_SESSION['user_id'] = $user->user_id;
    header("location: home.php");
  }
  else{
    $wrong_credentials = true;
  }
}

 ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="site.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
  <?php if(isset($wrong_credentials) && $wrong_credentials) echo "<p>Wrong credentials!</p>"; ?>
<!--  <form action="login.php" method="POST">
    Username<br>
    <input type="text" name="username"/>
    <br/>
    Password:<br>
    <input type="password" name="password"/><br/>
    <input type="submit" name="login" value="submit"/>
  </form>-->
  <div class="wrapper">
      <form class="login" method="post" action="login.php">
          <p class="title">Log in</p>
          <input type="text" placeholder="Username" name="username" autofocus/>
          <i class="fa fa-user"></i>
          <input type="password" placeholder="Password" name="password" />
          <i class="fa fa-key"></i>
          <!--<a href="">No account? register here!</a>-->
          <button type="submit" name="login">
              <i class="spinner"></i>
              <span class="state">Log in</span>
          </button>
      </form>
  </div>
</body>
</html>
