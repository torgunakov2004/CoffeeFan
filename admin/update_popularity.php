<?php
session_start();
require_once '../config/connect.php';

header('Content-Type: application/json'); // Устанавливаем заголовок для JSON

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Доступ запрещен.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['is_popular'])) {
    $id = (int)$_POST['id'];
    $is_popular = (int)$_POST['is_popular'] == 1 ? 1 : 0; // Приводим к 0 или 1

    $stmt = $connect->prepare("UPDATE `products` SET `is_popular` = ? WHERE `id` = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $is_popular, $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Статус популярности обновлен.']);
            } else {
                 // Либо такой ID не найден, либо статус уже был таким
                echo json_encode(['status' => 'success', 'message' => 'Статус популярности не изменен (возможно, он уже был таким или товар не найден).']);
            }
        } else {
            error_log("Update Popularity Error: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления статуса: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        error_log("Update Popularity Prepare Error: " . $connect->error);
        echo json_encode(['status' => 'error', 'message' => 'Ошибка подготовки запроса: ' . $connect->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Некорректные данные запроса.']);
}
?>