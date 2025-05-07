<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $is_popular = intval($_POST['is_popular']);

    // Обновление статуса популярности продукта
    $query = "UPDATE `products` SET `is_popular` = '$is_popular' WHERE `id` = '$id'";
    if (mysqli_query($connect, $query)) {
        echo "Статус популярности обновлен успешно.";
    } else {
        echo "Ошибка: " . mysqli_error($connect);
    }
}
?>