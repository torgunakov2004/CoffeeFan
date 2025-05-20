<?php
session_start();
require_once '../config/connect.php'; // Убедитесь, что путь правильный

header('Content-Type: application/json'); // Всегда возвращаем JSON

if (isset($_POST['product_id']) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $product_id = (int)$_POST['product_id'];
    $remove_all_units = isset($_POST['remove_all_units']) && $_POST['remove_all_units'] == 'true';

    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Некорректный ID товара.']);
        exit();
    }

    if ($remove_all_units) {
        // Удаляем всю позицию товара из корзины
        $query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $connect->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $product_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Товар полностью удален из корзины.', 'quantity' => 0, 'removed_all' => true]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Товар не найден в корзине для полного удаления.', 'quantity' => 0, 'removed_all' => false]);
                }
            } else {
                error_log("Remove all from cart DB error: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Ошибка при полном удалении товара.']);
            }
            $stmt->close();
        } else {
            error_log("Remove all from cart prepare error: " . $connect->error);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка сервера при подготовке запроса.']);
        }
    } else {
        // Уменьшаем количество на 1 (существующая логика)
        $query_get_qty = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt_get_qty = $connect->prepare($query_get_qty);
        $new_quantity = 0; // Инициализируем

        if ($stmt_get_qty) {
            $stmt_get_qty->bind_param("ii", $user_id, $product_id);
            $stmt_get_qty->execute();
            $result_get_qty = $stmt_get_qty->get_result();
            
            if ($row_qty = $result_get_qty->fetch_assoc()) {
                $current_quantity = $row_qty['quantity'];
                if ($current_quantity > 1) {
                    $query_update = "UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?";
                    $stmt_update = $connect->prepare($query_update);
                    if ($stmt_update) {
                        $stmt_update->bind_param("ii", $user_id, $product_id);
                        $stmt_update->execute();
                        $new_quantity = $current_quantity - 1;
                        $stmt_update->close();
                        echo json_encode(['status' => 'success', 'message' => 'Товар удален из корзины.', 'quantity' => $new_quantity]);
                    } else { /* ошибка prepare */ }
                } else { // current_quantity == 1
                    $query_delete = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
                    $stmt_delete = $connect->prepare($query_delete);
                    if ($stmt_delete) {
                        $stmt_delete->bind_param("ii", $user_id, $product_id);
                        $stmt_delete->execute();
                        $new_quantity = 0;
                        $stmt_delete->close();
                        echo json_encode(['status' => 'success', 'message' => 'Товар удален из корзины.', 'quantity' => $new_quantity, 'removed_all' => true]);
                    } else { /* ошибка prepare */ }
                }
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Товар не найден в корзине.']);
            }
            $stmt_get_qty->close();
        } else {
            error_log("Remove (decrease) from cart prepare error (get_qty): " . $connect->error);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка сервера при проверке товара.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных или пользователь не авторизован.']);
}
exit();
?>