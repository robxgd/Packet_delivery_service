<?php
/**
 * Created by PhpStorm.
 * User: robhofman
 * Date: 04/12/2018
 * Time: 13:38
 */

/*
  author: Maarten Slembrouck <maarten.slembrouck@ugent.be>
  created: oktober 2016
*/

include 'config/config.php';
include 'config/function.php';
initialize_mysql_connection();

class User
{
    public $username;
    public $user_id;
    public $email;
    public $role_id;

    public function load_user($usr, $psw)
    {
        global $conn;
        initialize_mysql_connection();
        $sql = "SELECT id, username, password, email, role_id FROM users WHERE username='$usr' and password=md5('$psw')";
        //echo $sql;
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $this->user_id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->role_id = $row['role_id'];
            close_mysql_connection();
            return true;
        }
        close_mysql_connection();
        return false;
    }
}



