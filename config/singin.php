<?php
session_start();
require_once 'connect.php';

$login = $_POST['login'];
$password = md5($_POST['password']); 

$check_user = mysqli_query($connect, "SELECT * FROM `user` WHERE `login` = '$login' AND `password` = '$password'");
if (mysqli_num_rows($check_user) > 0) {
    $user = mysqli_fetch_assoc($check_user);

    if ($user['is_admin'] == 1) {
        $_SESSION['admin'] = [
            "id" => $user['id'],
            "name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "login" => $user['login'],
            "email" => $user['email']
        ];
        header('Location: ../admin/admin_dashboard.php'); 
    } else {
        $_SESSION['user'] = [
            "id" => $user['id'],
            "name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "login" => $user['login'],
            "email" => $user['email']
        ];
        header('Location: ../index.php'); 
    }
} else {
    $_SESSION['message'] = 'Не верный логин или пароль';
    header('Location: ../admin/admin_login.php');
}