<?php

    session_start();
    require_once 'connect.php';

    $name = $_POST['name'];
    $last_name = $_POST['last_name'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password === $password_confirm) {

        $password = md5($password);

        $result = mysqli_query($connect, "INSERT INTO `user` (`id`, `first_name`, `last_name`, `login`, `email`, `password`) VALUES (NULL, '$name', '$last_name', '$login', '$email', '$password')");
        
        $_SESSION['message'] = 'Регистрация прошла успешно!';
        header('Location: ../auth/authorization.php');
    } else {
        $_SESSION['message'] = 'Пароли не совподают';
        header('Location: ../auth/register.php');
    } 