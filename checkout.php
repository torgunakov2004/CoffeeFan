<?php

session_start();
require_once 'config/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован']);
    exit();
}

$user_id = $_SESSION['user']['id'];

$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
$customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
$customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$order_comment = isset($_POST['order_comment']) && trim($_POST['order_comment']) !== '' ? trim($_POST['order_comment']) : null;

if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($delivery_address) || empty($payment_method)) {
    echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, заполните все обязательные поля для оформления заказа.']);
    exit();
}
if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректный формат Email.']);
    exit();
}

$connect->begin_transaction();

try {
    $query_cart_items = "SELECT p.id, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt_cart_items = $connect->prepare($query_cart_items);
    if (!$stmt_cart_items) {
        throw new Exception("Ошибка подготовки запроса товаров корзины: " . $connect->error);
    }
    $stmt_cart_items->bind_param("i", $user_id);
    if (!$stmt_cart_items->execute()) {
        throw new Exception("Ошибка выполнения запроса товаров корзины: " . $stmt_cart_items->error);
    }
    $result_cart_items = $stmt_cart_items->get_result();

    $total_price = 0;
    $cart_items_for_order = [];
    while ($row = $result_cart_items->fetch_assoc()) {
        $cart_items_for_order[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }
    $stmt_cart_items->close();

    if (empty($cart_items_for_order)) {
        throw new Exception('Корзина пуста');
    }

    $query_create_order = "INSERT INTO orders 
                            (user_id, customer_name, customer_email, customer_phone, delivery_address, payment_method, order_comment, total_price, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt_create_order = $connect->prepare($query_create_order);
    if (!$stmt_create_order) {
        throw new Exception("Ошибка подготовки запроса создания заказа: " . $connect->error);
    }
    
    $stmt_create_order->bind_param("issssssd", 
        $user_id, 
        $customer_name, 
        $customer_email, 
        $customer_phone, 
        $delivery_address, 
        $payment_method, 
        $order_comment, 
        $total_price
    );
    if (!$stmt_create_order->execute()) {
        throw new Exception("Ошибка выполнения запроса создания заказа: " . $stmt_create_order->error);
    }
    
    $order_id = $connect->insert_id;
    $stmt_create_order->close();

    if (!$order_id) {
        throw new Exception('Не удалось создать заказ (insert_id = 0).');
    }

    $query_add_order_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt_add_order_item = $connect->prepare($query_add_order_item);
    if (!$stmt_add_order_item) {
        throw new Exception("Ошибка подготовки запроса добавления товаров заказа: " . $connect->error);
    }
    
    foreach ($cart_items_for_order as $item) {
        $stmt_add_order_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        if(!$stmt_add_order_item->execute()) {
            throw new Exception("Ошибка выполнения запроса добавления товара заказа (ID товара: {$item['id']}): " . $stmt_add_order_item->error);
        }
    }
    $stmt_add_order_item->close();

    $query_clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt_clear_cart = $connect->prepare($query_clear_cart);
    if (!$stmt_clear_cart) {
        throw new Exception("Ошибка подготовки запроса очистки корзины: " . $connect->error);
    }
    
    $stmt_clear_cart->bind_param("i", $user_id);
    if(!$stmt_clear_cart->execute()) {
        throw new Exception("Ошибка выполнения запроса очистки корзины: " . $stmt_clear_cart->error);
    }
    $stmt_clear_cart->close();

    $connect->commit();

    if ($payment_method === 'card_online_mock') {
        // Дополнительная логика для имитации онлайн-оплаты
    }

    echo json_encode(['status' => 'success', 'message' => 'Заказ успешно оформлен! Мы свяжемся с вами для подтверждения.']);

} catch (Exception $e) {
    $connect->rollback();
    error_log("Checkout Error: " . $e->getMessage() . " for user_id: " . $user_id . " (POST data: " . print_r($_POST, true) . ")");
    echo json_encode(['status' => 'error', 'message' => 'Произошла ошибка при оформлении заказа. Пожалуйста, обратитесь в поддержку или попробуйте позже.']);
}
exit();
?>