<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';

$message = '';
$message_type = 'info';
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
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
            error_log("Order status update error for order #{$order_id_to_update}: " . $e->getMessage());
        }
    } else {
        $_SESSION['admin_message'] = "Некорректные данные для обновления статуса заказа.";
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_orders.php');
    exit();
}

$orders_data = [];
$sql_orders = "SELECT 
                    o.id as order_id, 
                    o.user_id, 
                    o.customer_name,
                    o.customer_email,
                    o.customer_phone,
                    o.total_price, 
                    o.status, 
                    o.created_at, 
                    u.login as user_login,
                    (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items_quantity
               FROM orders o
               LEFT JOIN user u ON o.user_id = u.id
               ORDER BY o.created_at DESC, o.id DESC";

$stmt_orders_list = $connect->prepare($sql_orders);
if ($stmt_orders_list) {
    $stmt_orders_list->execute();
    $result_orders_list = $stmt_orders_list->get_result();
    while ($row = $result_orders_list->fetch_assoc()) {
        $orders_data[] = $row;
    }
    $stmt_orders_list->close();
} else {
    error_log("Manage Orders: Failed to prepare orders list query: " . $connect->error);
    $message = "Ошибка загрузки списка заказов: " . $connect->error;
    $message_type = "error";
}

function translateOrderStatusAdmin($status) {
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
    <title>Управление Заказами - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <img class="header__logo" src="<?php echo $base_url_prefix_for_links; ?>../img/logo.svg" alt="CoffeeFan Logo">
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_products.php">Продукты</a></li>
                    <li><a href="manage_menu.php">Меню</a></li>
                    <li><a href="manage_recipes.php">Рецепты</a></li>
                    <li><a href="promotions_list.php">Акции</a></li>
                    <li><a href="manage_reviews.php">Отзывы</a></li>
                    <li><a href="manage_news.php">Новости</a></li>
                    <li><a href="manage_advertisements.php">Реклама</a></li>
                    <li><a href="manage_orders.php" class="active">Заказы</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список заказов</h2>
                <div class="search-input-container">
                    <input type="text" id="ordersSearchInput" placeholder="Поиск по ID, клиенту, телефону, email...">
                </div>
            </div>
            <?php if (!empty($orders_data)): ?>
                <table class="admin-table orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="order_id" data-sort-type="number">ID Заказа</th>
                            <th class="sortable" data-sort="customer_name">Клиент</th>
                            <th class="sortable" data-sort="customer_phone">Телефон</th>
                            <th class="sortable" data-sort="total_items_quantity" data-sort-type="number">Общее кол-во</th>
                            <th class="sortable" data-sort="total_price" data-sort-type="number">Общая сумма</th>
                            <th class="sortable" data-sort="created_at" data-sort-type="date">Дата заказа</th>
                            <th class="sortable" data-sort="status">Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php foreach ($orders_data as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                    <small><?php echo htmlspecialchars($order['customer_email']); ?></small><br>
                                    <?php if(!empty($order['user_login'])): ?>
                                        <small>(Профиль: @<?php echo htmlspecialchars($order['user_login']); ?>)</small>
                                    <?php elseif($order['user_id']): ?>
                                        <small>(ID польз.: <?php echo htmlspecialchars($order['user_id']); ?>)</small>
                                    <?php else: ?>
                                        <small>(Гость)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($order['total_items_quantity'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float)$order['total_price'], 2, '.', ' ')); ?> ₽</td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                                <td class="order-status-cell">
                                    <form action="manage_orders.php" method="post" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                        <select name="status">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Ожидает обработки</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Выполнен</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="btn-save" style="padding: 5px 8px; font-size: 0.8em; margin-left: 5px;">OK</button>
                                    </form>
                                    <span class="status-indicator status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                        <?php echo translateOrderStatusAdmin($order['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="order_detail.php?id=<?php echo htmlspecialchars($order['order_id']); ?>" class="view-btn" title="Просмотр деталей заказа">Детали</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p id="ordersTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Заказы по вашему запросу не найдены.</p>
            <?php else: ?>
                 <p id="noOrdersRow">Заказов пока нет.</p>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .orders-table .order-items-summary { font-size: 0.85em; line-height: 1.4; max-width: 280px; }
        .orders-table .order-items-summary br { margin-bottom: 3px; }
        .order-status-cell .status-form { display: flex; align-items: center; gap: 5px; margin-bottom: 5px; }
        .order-status-cell .status-form select { padding: 4px 6px; font-size: 0.85em; flex-grow: 1; min-width: 120px; }
        .order-status-cell .status-indicator { display: block; font-size: 0.8em; text-align: center; padding: 3px 5px; border-radius: 3px; color: #fff; }
        .status-indicator.status-pending { background-color: #c79242; }
        .status-indicator.status-processing { background-color: #4a9fc1; }
        .status-indicator.status-shipped { background-color: #2f6a99; }
        .status-indicator.status-delivered, .status-indicator.status-completed { background-color: #53a553; }
        .status-indicator.status-cancelled { background-color: #c94c49; }
    </style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const $ordersTable = $('#ordersTable');
    const $ordersTableBody = $('#ordersTableBody');
    const $ordersSearchInput = $('#ordersSearchInput');
    const $noOrdersRowPHP = $('#noOrdersRow');
    const $ordersEmptyMessageJS = $('#ordersTableEmptyMessage');

    $ordersTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        if (!column) return;
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $ordersTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $ordersTableBody.find('tr:not(#noOrdersRow)').get();
        let cellIndex = $th.index();

        rows.sort(function(a, b) {
            let valA, valB;
            if (column === 'status') {
                valA = $(a).children('td').eq(cellIndex).find('select option:selected').text().trim();
                valB = $(b).children('td').eq(cellIndex).find('select option:selected').text().trim();
            } else {
                valA = $(a).children('td').eq(cellIndex).text().trim();
                valB = $(b).children('td').eq(cellIndex).text().trim();
            }
            if (type === 'number') {
                valA = parseFloat(valA.replace(/[^0-9,.]/g, '').replace(',', '.')) || 0;
                valB = parseFloat(valB.replace(/[^0-9,.]/g, '').replace(',', '.')) || 0;
                if (column === 'order_id') {
                    valA = parseFloat($(a).children('td').eq(cellIndex).text().replace('#', '')) || 0;
                    valB = parseFloat($(b).children('td').eq(cellIndex).text().replace('#', '')) || 0;
                }
            } else if (type === 'date') {
                valA = new Date(valA.split(' (UTC)')[0].split('.').reverse().join('-')).getTime() || 0;
                valB = new Date(valB.split(' (UTC)')[0].split('.').reverse().join('-')).getTime() || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $ordersTableBody.append(row);
        });
    });

    $ordersSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $ordersTableBody.find('tr:not(#noOrdersRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                 const th = $ordersTable.find('thead th').eq(index);
                 if (th.data('sort') === 'status') {
                    rowText += $(this).find('select option:selected').text().toLowerCase() + ' ';
                 } else if (index !== 7 ) {
                    rowText += $(this).text().toLowerCase() + ' ';
                }
            });
            if (rowText.includes(searchTerm)) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        if ($noOrdersRowPHP.length > 0 && $ordersTableBody.find('tr:not(#noOrdersRow):visible').length === 0 && searchTerm === "") {
            $noOrdersRowPHP.show();
            $ordersEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $ordersTableBody.find('tr:not(#noOrdersRow)').length > 0) {
            $noOrdersRowPHP.hide();
            $ordersEmptyMessageJS.show();
        } else {
            $noOrdersRowPHP.hide();
            $ordersEmptyMessageJS.hide();
        }
    });
    if ($ordersTableBody.find('tr:not(#noOrdersRow)').length === 0 && $noOrdersRowPHP.is(':visible')) {
    } else {
         $ordersSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>