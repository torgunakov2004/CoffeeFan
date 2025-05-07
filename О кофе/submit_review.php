<?php
session_start();
require_once '../config/connect.php'; // Путь к файлу connect.php

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Произошла неизвестная ошибка.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    $name = '';
    if ($user_id) {
        $name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']; // Или просто first_name
        // Если имя берется из сессии, поле name в форме можно убрать для авторизованных
    } else {
        $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : 'Аноним';
    }
    
    $review_text = isset($_POST['review']) ? htmlspecialchars(trim($_POST['review'])) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (!empty($name) && !empty($review_text)) {
        // Убедитесь, что поле user_id существует в таблице reviews
        $stmt = $connect->prepare("INSERT INTO `reviews` (`name`, `review`, `rating`, `user_id`, `status`) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("ssii", $name, $review_text, $rating, $user_id);

            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = "Отзыв успешно отправлен на модерацию!";
            } else {
                $response['message'] = "Ошибка при отправке отзыва: " . $stmt->error;
                error_log("Review submit execute error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = "Ошибка подготовки запроса: " . $connect->error;
            error_log("Review submit prepare error: " . $connect->error);
        }
    } else {
        $response['message'] = "Пожалуйста, заполните все обязательные поля (имя и текст отзыва).";
    }
} else {
    $response['message'] = "Неверный метод запроса.";
}

echo json_encode($response);
exit();
?>