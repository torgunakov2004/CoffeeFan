<?php
session_start();
require_once '../config/connect.php'; // Подключение к базе данных

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents("php://input"), true);
$recipe_id = $data['recipe_id'];
$user_id = $_SESSION['user']['id']; // Предполагаем, что ID пользователя хранится в сессии

// Проверяем, сохранен ли уже рецепт
$checkQuery = "SELECT * FROM saved_recipes WHERE user_id = ? AND recipe_id = ?";
$stmt = $connect->prepare($checkQuery);
$stmt->bind_param("ii", $user_id, $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Этот рецепт уже сохранен!']);
} else {
    // Сохраняем рецепт в базу данных
    $insertQuery = "INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)";
    $stmt = $connect->prepare($insertQuery);
    $stmt->bind_param("ii", $user_id, $recipe_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении рецепта.']);
    }
}
?>