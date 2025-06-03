<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    echo "Доступ запрещен."; // Можно редирект или JSON ответ для AJAX
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $review_id_to_delete = (int)$_POST['id'];

    if ($review_id_to_delete > 0) {
        $stmt_delete = $connect->prepare("DELETE FROM `reviews` WHERE `id` = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $review_id_to_delete);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['admin_message'] = "Отзыв успешно удален.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Отзыв с ID $review_id_to_delete не найден или уже удален.";
                    $_SESSION['admin_message_type'] = "error";
                }
            } else {
                $_SESSION['admin_message'] = "Ошибка удаления отзыва: " . $stmt_delete->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_delete->close();
        } else {
            $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления отзыва: " . $connect->error;
            $_SESSION['admin_message_type'] = "error";
        }
    } else {
        $_SESSION['admin_message'] = "Некорректный ID отзыва для удаления.";
        $_SESSION['admin_message_type'] = "error";
    }
} else {
    $_SESSION['admin_message'] = "Недопустимый запрос на удаление.";
    $_SESSION['admin_message_type'] = "error";
}

header('Location: manage_reviews.php');
exit();
?>