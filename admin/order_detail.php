<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

$order_id_view = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order_details = null;
$order_items_details = [];

if ($order_id_view <= 0) {
    $_SESSION['admin_message'] = "Некорректный ID заказа для просмотра.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_orders.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id_to_update = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

    if ($order_id_to_update > 0 && in_array($new_status, $allowed_statuses)) {
        $connect->begin_transaction();
        $status_updated_successfully = false;
        try {
            $stmt_get_current_status = $connect->prepare("SELECT status FROM orders WHERE id = ?");
            if (!$stmt_get_current_status) throw new Exception("Ошибка подготовки (get_current_status): " . $connect->error);
            $stmt_get_current_status->bind_param("i", $order_id_to_update);
            $stmt_get_current_status->execute();
            $result_current_status = $stmt_get_current_status->get_result();
            $current_order_data = $result_current_status->fetch_assoc();
            $stmt_get_current_status->close();

            if (!$current_order_data) {
                throw new Exception("Заказ не найден.");
            }
            $old_status = $current_order_data['status'];

            $stmt_update_status = $connect->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if (!$stmt_update_status) throw new Exception("Ошибка подготовки (update_status): " . $connect->error);
            
            $stmt_update_status->bind_param("si", $new_status, $order_id_to_update);
            if (!$stmt_update_status->execute()) {
                throw new Exception("Ошибка обновления статуса заказа: " . $stmt_update_status->error);
            }
            $stmt_update_status->close();
            $status_updated_successfully = true;

            if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
                $stmt_get_items = $connect->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                if (!$stmt_get_items) throw new Exception("Ошибка подготовки (get_items_for_stock_return): " . $connect->error);
                
                $stmt_get_items->bind_param("i", $order_id_to_update);
                $stmt_get_items->execute();
                $items_result = $stmt_get_items->get_result();
                
                $stmt_return_stock = $connect->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                if (!$stmt_return_stock) throw new Exception("Ошибка подготовки (return_stock): " . $connect->error);

                while ($item = $items_result->fetch_assoc()) {
                    $stmt_return_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                    if(!$stmt_return_stock->execute()){
                        throw new Exception("Ошибка возврата товара (ID: {$item['product_id']}) на склад: " . $stmt_return_stock->error);
                    }
                }
                $stmt_get_items->close();
                $stmt_return_stock->close();
                $_SESSION['admin_message'] = "Статус заказа #{$order_id_to_update} обновлен на 'Отменен'. Товары возвращены на склад.";
            } elseif ($status_updated_successfully) {
                 $_SESSION['admin_message'] = "Статус заказа #{$order_id_to_update} успешно обновлен.";
            }
            $_SESSION['admin_message_type'] = "success";
            $connect->commit();
        } catch (Exception $e) {
            $connect->rollback();
            $_SESSION['admin_message'] = $e->getMessage();
            $_SESSION['admin_message_type'] = "error";
            error_log("Order status update error for order #{$order_id_to_update} on detail page: " . $e->getMessage());
        }
    } else {
        $_SESSION['admin_message'] = "Некорректные данные для обновления статуса заказа.";
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: order_detail.php?id=" . $order_id_to_update); 
    exit();
}

$stmt_order = $connect->prepare(
    "SELECT o.*, u.login as user_login, u.first_name as user_first_name, u.last_name as user_last_name 
     FROM orders o
     LEFT JOIN user u ON o.user_id = u.id
     WHERE o.id = ?"
);
if ($stmt_order) {
    $stmt_order->bind_param("i", $order_id_view);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    if ($result_order->num_rows === 1) {
        $order_details = $result_order->fetch_assoc();
    } else {
        $_SESSION['admin_message'] = "Заказ с ID #{$order_id_view} не найден.";
        $_SESSION['admin_message_type'] = "error";
        header('Location: manage_orders.php');
        exit();
    }
    $stmt_order->close();
} else {
    $_SESSION['admin_message'] = "Ошибка загрузки данных заказа: " . $connect->error;
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_orders.php');
    exit();
}

$stmt_items = $connect->prepare(
    "SELECT oi.quantity, oi.price as item_price, p.title as product_title, p.image as product_image, p.id as product_id
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);
if ($stmt_items) {
    $stmt_items->bind_param("i", $order_id_view);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row_item = $result_items->fetch_assoc()) {
        $order_items_details[] = $row_item;
    }
    $stmt_items->close();
} else {
    error_log("Order Detail: Failed to prepare order items query: " . $connect->error);
}

function translateOrderStatusDetailAdmin($status) {
    switch (strtolower($status)) {
        case 'pending': return 'Ожидает обработки';
        case 'processing': return 'В обработке';
        case 'shipped': return 'Отправлен';
        case 'delivered': return 'Доставлен';
        case 'completed': return 'Выполнен';
        case 'cancelled': return 'Отменен';
        default: return htmlspecialchars($status);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали Заказа #<?php echo htmlspecialchars($order_id_view); ?> - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <style>
        .order-detail-card { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-detail-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .order-detail-header h2 { margin-top: 0; color: #2c3e50; font-size: 1.8em; }
        .order-detail-header .order-meta { font-size: 0.9em; color: #777; }
        .order-detail-header .order-meta strong { color: #555; }
        .order-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .info-block h4 { font-size: 1.1em; color: #C99E71; margin-top: 0; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px dotted #ddd;}
        .info-block p { margin: 5px 0; font-size: 0.95em; color: #444; }
        .info-block p strong { color: #2c3e50; }
        .order-items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-items-table th, .order-items-table td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; font-size:0.9em; }
        .order-items-table th { background-color: #f1f3f5; }
        .order-items-table img.item-thumbnail { max-width: 50px; height: auto; border-radius: 3px; vertical-align: middle; margin-right: 10px;}
        .order-items-table .item-price, .order-items-table .item-subtotal { text-align: right; white-space: nowrap;}
        .order-detail-total { text-align: right; margin-top: 20px; font-size: 1.2em; }
        .order-detail-total strong { color: #C99E71; font-size: 1.3em; }
        .status-form-detail select { padding: 6px 8px; margin-right: 10px; }
        .status-form-detail button { padding: 7px 12px; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Детали Заказа #<?php echo htmlspecialchars($order_id_view); ?></h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_orders.php" class="active">Заказы</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <a href="manage_orders.php" class="back-link" style="margin-bottom: 20px;">← К списку заказов</a>
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="message <?php echo htmlspecialchars($_SESSION['admin_message_type'] ?? 'info'); ?>">
                <?php echo nl2br(htmlspecialchars($_SESSION['admin_message'])); unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
            </div>
        <?php endif; ?>

        <?php if ($order_details): ?>
            <div class="order-detail-card">
                <div class="order-detail-header">
                    <h2>Заказ #<?php echo htmlspecialchars($order_details['order_id']); ?></h2>
                    <p class="order-meta">
                        Дата: <strong><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($order_details['created_at']))); ?></strong> |
                        Текущий статус: <strong class="status-<?php echo strtolower(htmlspecialchars($order_details['status'])); ?>"><?php echo translateOrderStatusDetailAdmin($order_details['status']); ?></strong>
                    </p>
                </div>

                <div class="order-info-grid">
                    <div class="info-block">
                        <h4>Информация о клиенте (из заказа)</h4>
                        <p><strong>Имя:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
                        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($order_details['customer_phone']); ?></p>
                        <?php if ($order_details['user_id']): ?>
                             <p><strong>Профиль на сайте:</strong> 
                                <?php echo htmlspecialchars($order_details['user_first_name'] . ' ' . $order_details['user_last_name']); ?>
                                (@<?php echo htmlspecialchars($order_details['user_login']); ?>, ID: <?php echo $order_details['user_id']; ?>)
                             </p>
                        <?php else: ?>
                            <p><strong>Профиль на сайте:</strong> Гость</p>
                        <?php endif; ?>
                    </div>
                    <div class="info-block">
                        <h4>Доставка и оплата</h4>
                        <p><strong>Адрес доставки:</strong><br><?php echo nl2br(htmlspecialchars($order_details['delivery_address'])); ?></p>
                        <p><strong>Способ оплаты:</strong> 
                            <?php 
                                if($order_details['payment_method'] === 'cash_on_delivery') echo 'Оплата при получении';
                                elseif($order_details['payment_method'] === 'card_online_mock') echo 'Картой онлайн (Имитация)';
                                else echo htmlspecialchars($order_details['payment_method']);
                            ?>
                        </p>
                        <?php if (!empty($order_details['order_comment'])): ?>
                            <p><strong>Комментарий к заказу:</strong><br><?php echo nl2br(htmlspecialchars($order_details['order_comment'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-block">
                    <h4>Изменить статус заказа</h4>
                     <form action="order_detail.php?id=<?php echo htmlspecialchars($order_details['id']); ?>" method="post" class="status-form-detail admin-form" style="padding:0; box-shadow:none; margin:0;">
                      <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_details['id']); ?>">
                        <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                            <select name="status" style="flex-grow:1;">
                                <option value="pending" <?php echo $order_details['status'] == 'pending' ? 'selected' : ''; ?>>Ожидает обработки</option>
                                <option value="processing" <?php echo $order_details['status'] == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                <option value="shipped" <?php echo $order_details['status'] == 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                <option value="delivered" <?php echo $order_details['status'] == 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                <option value="completed" <?php echo $order_details['status'] == 'completed' ? 'selected' : ''; ?>>Выполнен</option>
                                <option value="cancelled" <?php echo $order_details['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                            <button type="submit" name="update_order_status" class="btn-save" style="padding: 7px 12px; font-size:0.9em;">Обновить</button>
                        </div>
                    </form>
                </div>

                <h4>Товары в заказе</h4>
                <?php if (!empty($order_items_details)): ?>
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Фото</th>
                                <th>Название товара</th>
                                <th style="text-align:center;">Кол-во</th>
                                <th class="item-price">Цена за шт.</th>
                                <th class="item-subtotal">Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items_details as $item): ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars(ltrim($item['product_image'] ?: 'img/default-product.png','/')); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_title']); ?>" class="item-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="item-price"><?php echo htmlspecialchars(number_format((float)$item['item_price'], 2, '.', ' ')); ?> ₽</td>
                                    <td class="item-subtotal"><?php echo htmlspecialchars(number_format((float)$item['item_price'] * (int)$item['quantity'], 2, '.', ' ')); ?> ₽</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="order-detail-total">
                        <p>Общая сумма заказа: <strong><?php echo htmlspecialchars(number_format((float)$order_details['total_price'], 2, '.', ' ')); ?> ₽</strong></p>
                    </div>
                <?php else: ?>
                    <p>В этом заказе нет товаров или не удалось их загрузить.</p>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <div class="message error">
                Не удалось загрузить детали заказа. Возможно, заказ не существует или произошла ошибка.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>