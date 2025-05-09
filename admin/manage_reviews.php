<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed_statuses = ['pending', 'approved', 'rejected'];

    if ($review_id > 0 && in_array($status, $allowed_statuses)) {
        $stmt = $connect->prepare("UPDATE `reviews` SET `status` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $review_id);
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "Статус отзыва успешно обновлён!";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Ошибка при обновлении статуса: " . $stmt->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = "Ошибка подготовки запроса обновления статуса: " . $connect->error;
            $_SESSION['admin_message_type'] = "error";
        }
    } else {
        $_SESSION['admin_message'] = "Некорректные данные для обновления статуса.";
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_reviews.php');
    exit();
}

$stmt_reviews = $connect->prepare("SELECT r.id, r.name as review_author_name, r.review, r.rating, r.status, r.created_at, u.login as user_login, u.first_name as user_first_name, u.last_name as user_last_name FROM `reviews` r LEFT JOIN `user` u ON r.user_id = u.id ORDER BY r.`created_at` DESC, r.`id` DESC");
$reviews_arr = [];
if ($stmt_reviews) {
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    while ($row = $result_reviews->fetch_assoc()) {
        $reviews_arr[] = $row;
    }
    $stmt_reviews->close();
} else {
    error_log("Manage Reviews: Failed to prepare reviews list query: " . $connect->error);
}

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
    <title>Модерация Отзывов - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Модерация Отзывов</h1>
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
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="admin-content">
            <h2>Список Отзывов</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Автор</th>
                        <th>Текст отзыва</th>
                        <th>Рейтинг</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reviews_arr)): ?>
                        <?php foreach ($reviews_arr as $review): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['id']); ?></td>
                                <td>
                                    <?php 
                                        $author_display_name = htmlspecialchars($review['review_author_name']);
                                        if (!empty($review['user_login'])) {
                                            $author_display_name .= " (@" . htmlspecialchars($review['user_login']) . ")";
                                        } elseif(!empty($review['user_first_name'])) {
                                            $author_display_name = htmlspecialchars($review['user_first_name'] . " " . $review['user_last_name']);
                                        }
                                        echo $author_display_name;
                                    ?>
                                </td>
                                <td style="max-width: 300px; white-space: pre-wrap; word-break: break-word;">
                                    <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php echo ($review['rating'] > 0) ? htmlspecialchars($review['rating']) . ' <span style="color: #f0a500;">★</span>' : 'N/A'; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($review['created_at']))); ?></td>
                                <td>
                                    <form action="manage_reviews.php" method="post" class="status-form" style="display: flex; align-items: center; gap: 5px;">
                                        <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['id']); ?>">
                                        <select name="status" style="flex-grow: 1; padding: 4px 6px; font-size: 0.85em;">
                                            <option value="pending" <?php echo $review['status'] == 'pending' ? 'selected' : ''; ?>>На модерации</option>
                                            <option value="approved" <?php echo $review['status'] == 'approved' ? 'selected' : ''; ?>>Одобрен</option>
                                            <option value="rejected" <?php echo $review['status'] == 'rejected' ? 'selected' : ''; ?>>Отклонён</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-save" style="padding: 5px 8px; font-size: 0.8em; line-height: 1;">OK</button>
                                    </form>
                                    <span class="status-<?php echo strtolower(htmlspecialchars($review['status'])); ?>" style="display: block; margin-top: 5px; font-size: 0.8em; text-align:center; padding: 2px 4px; border-radius: 3px;">
                                        <?php 
                                            switch($review['status']){
                                                case 'pending': echo 'Ожидает'; break;
                                                case 'approved': echo 'Одобрен'; break;
                                                case 'rejected': echo 'Отклонен'; break;
                                                default: echo htmlspecialchars($review['status']);
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form action="delete_review.php" method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот отзыв?');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($review['id']); ?>">
                                        <button type="submit" class="delete-btn">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Отзывов для модерации или просмотра нет.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>