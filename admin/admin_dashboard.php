<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

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
            <h1>CoffeeFan Админ</h1>
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
        <div class="admin-content">
            <h2>Добро пожаловать, <?php echo htmlspecialchars($admin_name); ?>!</h2>
            <p>Это главная страница административной панели. Выберите раздел для управления.</p>
        </div>

        <div class="admin-content">
            <h2>Управление пользователями</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Фамилия</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['is_admin'] ? 'Администратор' : 'Пользователь'; ?></td>
                                <td class="actions">
                                    <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="edit-btn">Редакт.</a>
                                    <?php if ($_SESSION['admin']['id'] != $user['id']): // Нельзя удалить самого себя ?>
                                    <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <button type="submit" class="delete-btn">Удалить</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Пользователи не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>