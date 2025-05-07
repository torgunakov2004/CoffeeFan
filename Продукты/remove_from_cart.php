<?php
session_start();
require_once '../config/connect.php';

if (isset($_POST['product_id']) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $product_id = $_POST['product_id'];

    // Получаем текущее количество товара в корзине
    $query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_quantity = $row['quantity'];

    if ($current_quantity > 1) {
        // Если количество больше 1, уменьшаем его на 1
        $query = "UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $new_quantity = $current_quantity - 1;
    } else {
        // Если количество равно 1, удаляем товар из корзины
        $query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $new_quantity = 0;
    }

    // Возвращаем JSON-ответ с новым количеством
    echo json_encode(['status' => 'success', 'message' => 'Товар удален из корзины', 'quantity' => $new_quantity]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных для удаления товара из корзины']);
}
?>