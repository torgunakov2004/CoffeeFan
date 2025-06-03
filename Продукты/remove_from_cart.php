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
$remove_all_units_flag = isset($_POST['remove_all_units']) && $_POST['remove_all_units'] == 'true';

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректный ID товара.']);
    exit();
}

$new_cart_quantity = 0;
$current_stock = 0;

try {
    $stmt_cart_info = $connect->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    if (!$stmt_cart_info) throw new Exception("Ошибка подготовки (cart_info_remove): " . $connect->error);
    $stmt_cart_info->bind_param("ii", $user_id, $product_id);
    $stmt_cart_info->execute();
    $result_cart_info = $stmt_cart_info->get_result();

    if ($row_cart = $result_cart_info->fetch_assoc()) {
        $current_cart_qty = (int)$row_cart['quantity'];

        if ($current_cart_qty == 0 && !$remove_all_units_flag) {
             $stmt_get_stock_nf = $connect->prepare("SELECT stock_quantity FROM products WHERE id = ?");
             if($stmt_get_stock_nf){
                $stmt_get_stock_nf->bind_param("i", $product_id);
                $stmt_get_stock_nf->execute();
                $res_stock_nf = $stmt_get_stock_nf->get_result();
                if($row_stock_nf = $res_stock_nf->fetch_assoc()){ $current_stock = (int)$row_stock_nf['stock_quantity']; }
                $stmt_get_stock_nf->close();
             }
             echo json_encode(['status' => 'success', 'message' => 'Товара нет в корзине.', 'quantity' => 0, 'stock_quantity' => $current_stock]);
             exit();
        }

        if ($remove_all_units_flag || $current_cart_qty <= 1) {
            $stmt_delete = $connect->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            if (!$stmt_delete) throw new Exception("Ошибка подготовки (cart_delete_remove): " . $connect->error);
            $stmt_delete->bind_param("ii", $user_id, $product_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            $new_cart_quantity = 0;
        } else {
            $stmt_update_cart = $connect->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?");
            if (!$stmt_update_cart) throw new Exception("Ошибка подготовки (cart_decrease_remove): " . $connect->error);
            $stmt_update_cart->bind_param("ii", $user_id, $product_id);
            $stmt_update_cart->execute();
            $stmt_update_cart->close();
            $new_cart_quantity = $current_cart_qty - 1;
        }
        
        $message_text = $remove_all_units_flag || $new_cart_quantity == 0 ? 'Товар удален из корзины.' : 'Количество товара уменьшено.';
        
        $stmt_get_stock = $connect->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        if ($stmt_get_stock) {
            $stmt_get_stock->bind_param("i", $product_id);
            $stmt_get_stock->execute();
            $res_stock = $stmt_get_stock->get_result();
            if($row_stock = $res_stock->fetch_assoc()){
                $current_stock = (int)$row_stock['stock_quantity'];
            }
            $stmt_get_stock->close();
        }

        echo json_encode([
            'status' => 'success', 
            'message' => $message_text, 
            'quantity' => $new_cart_quantity, 
            'stock_quantity' => $current_stock,
            'removed_all' => ($remove_all_units_flag || $new_cart_quantity == 0)
        ]);

    } else {
         $stmt_get_stock_nf_alt = $connect->prepare("SELECT stock_quantity FROM products WHERE id = ?");
         if($stmt_get_stock_nf_alt){
            $stmt_get_stock_nf_alt->bind_param("i", $product_id);
            $stmt_get_stock_nf_alt->execute();
            $res_stock_nf_alt = $stmt_get_stock_nf_alt->get_result();
            if($row_stock_nf_alt = $res_stock_nf_alt->fetch_assoc()){ $current_stock = (int)$row_stock_nf_alt['stock_quantity']; }
            $stmt_get_stock_nf_alt->close();
         }
        echo json_encode(['status' => 'error', 'message' => 'Товар не найден в корзине для изменения.', 'quantity' => 0, 'stock_quantity' => $current_stock]);
    }
    $stmt_cart_info->close();

} catch (Exception $e) {
    error_log("Remove from cart error (no stock change): " . $e->getMessage() . " for product_id: " . $product_id . ", user_id: " . $user_id);
    echo json_encode(['status' => 'error', 'message' => 'Не удалось обновить корзину. ' . $e->getMessage()]);
}
exit();
?>