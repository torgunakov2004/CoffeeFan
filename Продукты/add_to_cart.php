<?php
session_start();
require_once '../config/connect.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных или пользователь не авторизован.']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$product_id = (int)$_POST['product_id'];
$quantity_to_add = 1;

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректный ID товара.']);
    exit();
}

$current_stock = 0;
$final_cart_quantity = 0;

try {
    $stmt_check_stock = $connect->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    if (!$stmt_check_stock) throw new Exception("Ошибка подготовки (stock_check_add): " . $connect->error);
    $stmt_check_stock->bind_param("i", $product_id);
    $stmt_check_stock->execute();
    $result_stock = $stmt_check_stock->get_result();
    
    if ($row_stock = $result_stock->fetch_assoc()) {
        $current_stock = (int)$row_stock['stock_quantity'];
    } else {
        throw new Exception("Товар не найден.");
    }
    $stmt_check_stock->close();

    $stmt_cart_check = $connect->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    if (!$stmt_cart_check) throw new Exception("Ошибка подготовки (cart_check_add): " . $connect->error);
    $stmt_cart_check->bind_param("ii", $user_id, $product_id);
    $stmt_cart_check->execute();
    $result_cart_check = $stmt_cart_check->get_result();
    $current_cart_qty_for_user = 0;
    if ($row_cart_user = $result_cart_check->fetch_assoc()) {
        $current_cart_qty_for_user = (int)$row_cart_user['quantity'];
    }
    $stmt_cart_check->close();

    if ($current_stock < ($current_cart_qty_for_user + $quantity_to_add)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Извините, на складе недостаточно товара. Доступно: ' . $current_stock . ' шт.',
            'stock_quantity' => $current_stock,
            'quantity' => $current_cart_qty_for_user
        ]);
        exit();
    }

    if ($current_cart_qty_for_user > 0) {
        $stmt_update_cart = $connect->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        if (!$stmt_update_cart) throw new Exception("Ошибка подготовки (cart_update_add): " . $connect->error);
        $stmt_update_cart->bind_param("iii", $quantity_to_add, $user_id, $product_id);
        $stmt_update_cart->execute();
        $stmt_update_cart->close();
    } else {
        $stmt_insert_cart = $connect->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        if (!$stmt_insert_cart) throw new Exception("Ошибка подготовки (cart_insert_add): " . $connect->error);
        $stmt_insert_cart->bind_param("iii", $user_id, $product_id, $quantity_to_add);
        $stmt_insert_cart->execute();
        $stmt_insert_cart->close();
    }
    
    $final_cart_quantity = $current_cart_qty_for_user + $quantity_to_add;

    echo json_encode([
        'status' => 'success', 
        'message' => 'Товар добавлен в корзину!', 
        'quantity' => $final_cart_quantity, 
        'stock_quantity' => $current_stock
    ]);

} catch (Exception $e) {
    error_log("Add to cart error (no stock change): " . $e->getMessage() . " for product_id: " . $product_id . ", user_id: " . $user_id);
    echo json_encode(['status' => 'error', 'message' => 'Не удалось добавить товар в корзину. ' . $e->getMessage()]);
}
exit();
?>