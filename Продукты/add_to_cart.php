<?php
session_start();
require_once '../config/connect.php';

if (isset($_POST['product_id']) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $product_id = $_POST['product_id'];
    $quantity = 1;

    // Проверяем, есть ли товар уже в корзине
    $query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Если товар уже есть, увеличиваем количество
        $query = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $stmt->execute();
    } else {
        // Если товара нет, добавляем его в корзину
        $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt->execute();
    }

    // Получаем обновленное количество товаров в корзине
    $query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $quantity = $row['quantity'];

    // Возвращаем JSON-ответ
    echo json_encode(['status' => 'success', 'message' => 'Товар добавлен в корзину', 'quantity' => $quantity]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных для добавления товара в корзину']);
}
?>