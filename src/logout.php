<?php
session_start();
session_unset();
session_destroy();

// Delete Remember Me cookies
if(isset($_COOKIE['remember_token'])){
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}
if(isset($_COOKIE['remember_user'])){
    setcookie('remember_user', '', time() - 3600, '/', '', false, true);
}

header('Location: login.php');
exit;
