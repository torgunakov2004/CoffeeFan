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

$stats = [
    'total_orders' => 0,
    'total_revenue' => 0.00,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'processing_orders' => 0,
    'shipped_orders' => 0,
    'delivered_orders' => 0,
    'cancelled_orders' => 0,
    'top_products' => []
];

$stmt_order_summary = $connect->prepare(
    "SELECT 
        COUNT(*) as total_orders_count, 
        SUM(total_price) as total_revenue_sum,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
     FROM orders"
);

if ($stmt_order_summary) {
    $stmt_order_summary->execute();
    $result_summary = $stmt_order_summary->get_result();
    if ($row_summary = $result_summary->fetch_assoc()) {
        $stats['total_orders'] = (int)$row_summary['total_orders_count'];
        $stats['total_revenue'] = (float)($row_summary['total_revenue_sum'] ?? 0.00);
        $stats['pending_orders'] = (int)($row_summary['pending_count'] ?? 0);
        $stats['completed_orders'] = (int)($row_summary['completed_count'] ?? 0);
        $stats['processing_orders'] = (int)($row_summary['processing_count'] ?? 0);
        $stats['shipped_orders'] = (int)($row_summary['shipped_count'] ?? 0);
        $stats['delivered_orders'] = (int)($row_summary['delivered_count'] ?? 0);
        $stats['cancelled_orders'] = (int)($row_summary['cancelled_count'] ?? 0);
    }
    $stmt_order_summary->close();
} else {
    error_log("Admin Dashboard: Failed to prepare order summary query: " . $connect->error);
}

$limit_top_products = 5;
$stmt_top_products = $connect->prepare(
    "SELECT p.title, SUM(oi.quantity) as total_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.status NOT IN ('cancelled')
     GROUP BY oi.product_id, p.title
     ORDER BY total_sold DESC
     LIMIT ?"
);

if ($stmt_top_products) {
    $stmt_top_products->bind_param("i", $limit_top_products);
    $stmt_top_products->execute();
    $result_top = $stmt_top_products->get_result();
    while ($row_top = $result_top->fetch_assoc()) {
        $stats['top_products'][] = $row_top;
    }
    $stmt_top_products->close();
} else {
    error_log("Admin Dashboard: Failed to prepare top products query: " . $connect->error);
}

$stmt_users = $connect->prepare("SELECT id, first_name, last_name, login, email, is_admin FROM `user` ORDER BY id ASC");
$users = [];
if ($stmt_users) {
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();
} else {
    error_log("Admin Dashboard: Failed to prepare user query: " . $connect->error);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <img class="header__logo" src="../img/logo.svg" alt="CoffeeFan Logo">
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="manage_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">Заказы</a></li>
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

        <div class="admin-content dashboard-welcome">
            <h2>Добро пожаловать, <?php echo htmlspecialchars($admin_name); ?>!</h2>
            <p>Это главная страница административной панели CoffeeFan. Отсюда вы можете управлять различными аспектами вашего интернет-магазина.</p>
            <div class="quick-actions">
                <a href="manage_products.php#add-form" class="quick-action-btn">
                    <span class="material-icons-outlined">add_shopping_cart</span>Добавить продукт
                </a>
                <a href="manage_news.php#add-form" class="quick-action-btn">
                    <span class="material-icons-outlined">post_add</span>Добавить новость
                </a>
                <a href="manage_orders.php?status=pending" class="quick-action-btn">
                    <span class="material-icons-outlined">pending_actions</span>Новые заказы (<?php echo $stats['pending_orders']; ?>)
                </a>
                 <a href="manage_reviews.php?status=pending" class="quick-action-btn">
                    <span class="material-icons-outlined">rate_review</span>Отзывы на модерацию
                </a>
            </div>
        </div>

        <div class="admin-content">
            <h2>Статистика Заказов</h2>
            <div class="stats-container-animated">
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">shopping_cart</span>
                    <h4>Всего заказов</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['total_orders']; ?>">0</div>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">account_balance_wallet</span>
                    <h4>Общая сумма</h4>
                    <div class="stat-value-animated" data-count="<?php echo (float)$stats['total_revenue']; ?>" data-format-currency="true">0.00</div>
                     <span class="stat-description">руб.</span>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">hourglass_empty</span>
                    <h4>В ожидании</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['pending_orders']; ?>">0</div>
                    <span class="stat-description">Ожидают обработки</span>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">sync</span>
                    <h4>В обработке</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['processing_orders']; ?>">0</div>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">local_shipping</span>
                    <h4>Отправлено</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['shipped_orders']; ?>">0</div>
                </div>
                 <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">inventory_2</span>
                    <h4>Доставлено</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['delivered_orders']; ?>">0</div>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">check_circle_outline</span>
                    <h4>Выполненные</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['completed_orders']; ?>">0</div>
                    <span class="stat-description">Успешно завершены</span>
                </div>
                <div class="stat-card-animated">
                    <span class="material-icons-outlined stat-icon">cancel</span>
                    <h4>Отмененные</h4>
                    <div class="stat-value-animated" data-count="<?php echo $stats['cancelled_orders']; ?>">0</div>
                </div>
            </div>
            <?php if (!empty($stats['top_products'])): ?>
                <div class="top-products-card">
                    <h4>Топ-<?php echo $limit_top_products; ?> продаваемых товаров</h4>
                    <ul>
                        <?php foreach ($stats['top_products'] as $product_stat): ?>
                            <li>
                                <span class="product-name"><?php echo htmlspecialchars($product_stat['title']); ?></span>
                                <span class="sold-count"><?php echo htmlspecialchars($product_stat['total_sold']); ?> шт.</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-content">
                <div class="users-table-controls">
                    <h2>Управление пользователями</h2> 
                    <div class="search-input-container"> 
                        <input type="text" id="userSearchInput" placeholder="Поиск пользователей...">
                    </div>
                </div>
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                            <th class="sortable" data-sort="first_name">Имя</th>
                            <th class="sortable" data-sort="last_name">Фамилия</th>
                            <th class="sortable" data-sort="login">Логин</th>
                            <th class="sortable" data-sort="email">Email</th>
                            <th class="sortable" data-sort="role">Роль</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="<?php echo $user['is_admin'] ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo $user['is_admin'] ? 'Администратор' : 'Пользователь'; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="edit-btn">Редакт.</a>
                                        <?php 
                                            $current_admin_id_for_delete_check = $_SESSION['admin']['id'] ?? ($_SESSION['user']['id'] ?? null);
                                            if ($current_admin_id_for_delete_check != $user['id']): 
                                        ?>
                                        <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="delete-btn">Удалить</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="noUsersRow">
                                <td colspan="7">Пользователи не найдены.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p id="usersTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Пользователи по вашему запросу не найдены.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-value-animated');
            const animateCounter = (element) => {
                const target = +element.getAttribute('data-count');
                const formatCurrency = element.getAttribute('data-format-currency') === 'true';
                let current = 0;
                const incrementBase = target > 1000 ? 50 : (target > 100 ? 10 : 1);
                const steps = Math.max(20, Math.min(100, target / incrementBase)); 
                const increment = target / steps;
                const duration = 1500;
                const stepTime = duration / steps;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    if (formatCurrency) {
                        element.innerText = parseFloat(current).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, " ");
                    } else {
                        element.innerText = Math.ceil(current);
                    }
                }, stepTime > 0 ? stepTime : 15);
                 if (target === 0) {
                    element.innerText = formatCurrency ? '0,00' : '0';
                }
            };
            if(window.IntersectionObserver){
                const observer = new IntersectionObserver((entries, observerInstance) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animateCounter(entry.target);
                            observerInstance.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                counters.forEach(counter => { observer.observe(counter); });
            } else {
                counters.forEach(counter => { animateCounter(counter); });
            }
        });

        $(document).ready(function() {
            const $usersTable = $('#usersTable');
            const $usersTableBody = $('#usersTableBody');
            const $searchInput = $('#userSearchInput');
            const $noUsersRowPHP = $('#noUsersRow'); 
            const $emptyMessageJS = $('#usersTableEmptyMessage');

            $usersTable.find('th.sortable').on('click', function() {
                const $th = $(this);
                const column = $th.data('sort');
                const type = $th.data('sort-type') || 'string';
                let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
                $usersTable.find('th.sortable').removeClass('asc desc');
                $th.addClass(currentOrder);
                const rows = $usersTableBody.find('tr:not(#noUsersRow)').get();
                rows.sort(function(a, b) {
                    let valA = $(a).children('td').eq($th.index()).text().trim();
                    let valB = $(b).children('td').eq($th.index()).text().trim();
                    if (type === 'number') {
                        valA = parseFloat(valA) || 0;
                        valB = parseFloat(valB) || 0;
                    } else if (column === 'role') {
                        valA = ($(a).children('td').eq($th.index()).text().trim().toLowerCase() === 'администратор' ? 0 : 1);
                        valB = ($(b).children('td').eq($th.index()).text().trim().toLowerCase() === 'администратор' ? 0 : 1);
                    } else {
                        valA = valA.toLowerCase();
                        valB = valB.toLowerCase();
                    }
                    if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
                    if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
                    return 0;
                });
                $.each(rows, function(index, row) {
                    $usersTableBody.append(row);
                });
            });

            $searchInput.on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                let visibleRows = 0;
                $usersTableBody.find('tr:not(#noUsersRow)').each(function() {
                    const $row = $(this);
                    const rowText = $row.text().toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        $row.show();
                        visibleRows++;
                    } else {
                        $row.hide();
                    }
                });
                if ($noUsersRowPHP.length > 0 && $usersTableBody.find('tr:not(#noUsersRow):visible').length === 0 && searchTerm === "") {
                    $noUsersRowPHP.show();
                    $emptyMessageJS.hide();
                } else if (visibleRows === 0 && $usersTableBody.find('tr:not(#noUsersRow)').length > 0) {
                    $noUsersRowPHP.hide();
                    $emptyMessageJS.show();
                } else {
                    $noUsersRowPHP.hide();
                    $emptyMessageJS.hide();
                }
            });
             if ($usersTableBody.find('tr:not(#noUsersRow)').length === 0 && $noUsersRowPHP.is(':visible')) {
            } else {
                 $searchInput.trigger('keyup');
            }
        });
    </script>
</body>
</html>