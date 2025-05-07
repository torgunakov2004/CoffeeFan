<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    mysqli_query($connect, "UPDATE `menu` SET `is_popular` = $is_popular WHERE `id` = $id");
}

header('Location: manage_menu.php');
exit();
?>