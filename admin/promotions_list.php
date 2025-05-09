<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

$stmt_promos = $connect->prepare("SELECT id, title, image, conditions, type, is_active, created_at FROM promotions ORDER BY created_at DESC");
$promotions_arr = [];
if ($stmt_promos) {
    $stmt_promos->execute();
    $result_promos = $stmt_promos->get_result();
    while ($row = $result_promos->fetch_assoc()) {
        $promotions_arr[] = $row;
    }
    $stmt_promos->close();
} else {
    error_log("Promotions List: Failed to prepare promotions list query: " . $connect->error);
}

$message = '';
$message_type = 'info';
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Акциями - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Управление Акциями</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <a href="promotion_form.php" class="add-new-btn">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новую акцию
        </a>

        <div class="admin-content">
            <h2>Список акций</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Заголовок</th>
                        <th>Условия</th>
                        <th>Тип</th>
                        <th>Активна</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($promotions_arr)): ?>
                        <?php foreach ($promotions_arr as $promo): ?>
                            <tr>
                                <td><?php echo $promo['id']; ?></td>
                                <td>
                                    <?php if (!empty($promo['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($promo['image'],'/')); ?>" alt="Превью" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($promo['title']); ?></td>
                                <td><?php echo htmlspecialchars($promo['conditions'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($promo['type'])); ?></td>
                                <td>
                                     <form action="process_promotion.php?action=toggle_status" method="post" style="display:inline;" class="toggle-status-form">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $promo['is_active']; ?>">
                                        <button type="submit" class="status-toggle-btn <?php echo $promo['is_active'] ? 'active' : 'inactive'; ?>" 
                                                title="<?php echo $promo['is_active'] ? 'Деактивировать' : 'Активировать'; ?>">
                                            <?php echo $promo['is_active'] ? 'Да' : 'Нет'; ?>
                                        </button>
                                     </form>
                                </td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($promo['created_at']))); ?></td>
                                <td class="actions">
                                    <a href="promotion_form.php?id=<?php echo $promo['id']; ?>" class="edit-btn">Редакт.</a>
                                    <form action="process_promotion.php?action=delete" method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту акцию? Это действие необратимо.');">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <button type="submit" class="delete-btn">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">Акций пока нет.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>
        .status-toggle-btn {
            padding: 5px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            color: white;
            min-width: 60px;
            text-align: center;
        }
        .status-toggle-btn.active { background-color: #28a745; }
        .status-toggle-btn.inactive { background-color: #dc3545; }
    </style>
</body>
</html>