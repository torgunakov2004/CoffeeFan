<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

$id = $_GET['id'];
mysqli_query($connect, "DELETE FROM `menu` WHERE `id` = '$id'");
header('Location: manage_menu.php');