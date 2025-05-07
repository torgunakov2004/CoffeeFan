<?php
session_start();
require_once 'config/connect.php'; // Подключение к базе данных

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован']);
    exit();
}

$user_id = $_SESSION['user']['id'];

// Начинаем транзакцию
$connect->begin_transaction();

try {
    // Получаем товары из корзины
    $query = "SELECT p.id, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total_price = 0;
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }

    if (empty($cart_items)) {
        throw new Exception('Корзина пуста');
    }

    // Создаем заказ
    $query = "INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("id", $user_id, $total_price);
    $stmt->execute();
    $order_id = $connect->insert_id;

    // Добавляем товары в заказ
    foreach ($cart_items as $item) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    // Очищаем корзину
    $query = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Фиксируем транзакцию
    $connect->commit();

    echo json_encode(['status' => 'success', 'message' => 'Заказ успешно оформлен']);
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    $connect->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>