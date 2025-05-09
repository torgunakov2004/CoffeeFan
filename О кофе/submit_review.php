<?php
session_start();
require_once '../config/connect.php';

header('Content-Type: application/json');

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_SESSION['user']) ? $_SESSION['user']['name'] : htmlspecialchars(trim($_POST['name']));
    $review = htmlspecialchars(trim($_POST['review']));
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (!empty($name) && !empty($review)) {
        $stmt = $connect->prepare("INSERT INTO `reviews` (`name`, `review`, `rating`, `status`) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("ssi", $name, $review, $rating);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Отзыв успешно отправлен на модерацию!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка при отправке отзыва: ' . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Пожалуйста, заполните все поля.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Недопустимый метод запроса'
    ]);
}
?>