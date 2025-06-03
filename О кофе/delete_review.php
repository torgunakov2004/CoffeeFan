<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

if (isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $stmt = $connect->prepare("DELETE FROM `reviews` WHERE `id` = ?");
    $stmt->bind_param("i", $review_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Отзыв успешно удалён!";
    } else {
        $_SESSION['error'] = "Ошибка при удалении отзыва: " . $stmt->error;
    }

    $stmt->close();
}

header('Location: manage_reviews.php');
exit();