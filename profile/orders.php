<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header('Location: ../auth/authorization.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$orders = [];

$stmt_orders = $connect->prepare(
    "SELECT o.id as order_id, o.total_price, o.status, o.created_at, 
            oi.quantity, oi.price as item_price, 
            p.title as product_title, p.image as product_image
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC, o.id DESC, oi.id ASC"
);

if ($stmt_orders) {
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    $grouped_orders = [];
    while ($row = $result_orders->fetch_assoc()) {
        $order_id = $row['order_id'];
        if (!isset($grouped_orders[$order_id])) {
            $grouped_orders[$order_id] = [
                'id' => $order_id,
                'total_price' => $row['total_price'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'items' => []
            ];
        }
        $grouped_orders[$order_id]['items'][] = [
            'product_title' => $row['product_title'],
            'product_image' => $row['product_image'],
            'quantity' => $row['quantity'],
            'item_price' => $row['item_price']
        ];
    }
    $orders = array_values($grouped_orders);
    $stmt_orders->close();
} else {
    error_log("Ошибка подготовки запроса для получения заказов: " . $connect->error);
    $_SESSION['orders_message'] = "Не удалось загрузить историю заказов. Пожалуйста, попробуйте позже.";
    $_SESSION['orders_message_type'] = "error";
}

$cart_quantities = [];
$has_items_in_cart = false;
if (isset($_SESSION['user']['id'])) {
    $user_id_for_cart = $_SESSION['user']['id'];
    $query_cart_header = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart_header = $connect->prepare($query_cart_header);
    if ($stmt_cart_header) {
        $stmt_cart_header->bind_param("i", $user_id_for_cart);
        $stmt_cart_header->execute();
        $result_cart_header = $stmt_cart_header->get_result();
        while ($row_cart_header = $result_cart_header->fetch_assoc()) {
            $cart_quantities[$row_cart_header['product_id']] = $row_cart_header['quantity'];
        }
        $stmt_cart_header->close();
    }
    $has_items_in_cart = !empty($cart_quantities);
}

function translateOrderStatus($status) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeeFan - Мои заказы</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="profile_style_v2.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="orders_style.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <main class="profile-page-main-v2 orders-page">
        <div class="profile-card-container">
            <div class="profile-card orders-card-override">
                
                <div class="profile-card-header orders-card-header-override">
                    <div class="profile-card-userinfo">
                        <h1 class="profile-card-name">Мои заказы</h1>
                    </div>
                </div>

                <?php if (isset($_SESSION['orders_message'])): ?>
                    <div class="profile-message-v2 <?php echo htmlspecialchars($_SESSION['orders_message_type'] ?? 'error'); ?>">
                        <?php echo htmlspecialchars($_SESSION['orders_message']); ?>
                    </div>
                    <?php unset($_SESSION['orders_message'], $_SESSION['orders_message_type']); ?>
                <?php endif; ?>

                <div class="profile-card-content orders-card-content-override">
                    <?php if (!empty($orders)): ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-card-header">
                                        <span class="order-id">Заказ #<?php echo htmlspecialchars($order['id']); ?></span>
                                        <span class="order-date">от <?php echo display_date_in_user_timezone($order['created_at']); ?></span>
                                        <span class="order-status status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                            <?php echo translateOrderStatus($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="order-card-body">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="order-item">
                                                <img src="../<?php echo htmlspecialchars($item['product_image'] ?: 'img/default-product.png'); ?>" alt="<?php echo htmlspecialchars($item['product_title']); ?>" class="order-item-image">
                                                <div class="order-item-details">
                                                    <p class="order-item-title"><?php echo htmlspecialchars($item['product_title']); ?></p>
                                                    <p class="order-item-price-qty">
                                                        <?php echo htmlspecialchars($item['quantity']); ?> шт. x <?php echo number_format($item['item_price'], 2, '.', ' '); ?> ₽
                                                    </p>
                                                </div>
                                                <p class="order-item-subtotal">
                                                    <?php echo number_format($item['quantity'] * $item['item_price'], 2, '.', ' '); ?> ₽
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="order-card-footer">
                                        <span class="order-total-label">Итого:</span>
                                        <span class="order-total-amount"><?php echo number_format($order['total_price'], 2, '.', ' '); ?> ₽</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                         <?php if (!isset($_SESSION['orders_message'])): ?>
                            <div class="no-orders-message">
                                <span class="material-icons-outlined no-orders-icon">shopping_bag</span>
                                <p>У вас пока нет заказов.</p>
                                <a href="../Продукты/index.php" class="btn-primary-v2">Перейти к покупкам</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
</body>
</html>