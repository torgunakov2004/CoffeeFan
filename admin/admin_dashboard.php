<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Функция для получения всех пользователей
function getAllUsers($connect) {
    $query = "SELECT * FROM `user`";
    return mysqli_query($connect, $query);
}

// Получение всех пользователей
$users = getAllUsers($connect);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <h1>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['admin']['name']); ?></h1>
    
    <h2><a href="manage_products.php">Управление продуктами</a></h2>
    <h2><a href="manage_news.php">Управление новостями</a></h2> 
    <h2><a href="manage_recipes.php">Управление рецептами</a></h2>
    <h2><a href="manage_menu.php">Управление меню</a></h2>
    <h2><a href="manage_reviews.php">Модерация отзывов</a></h2>
    <h2><a href="manage_advertisements.php">Управление рекламой</a></h2>
    <h2><a href="promotions_list.php">Управление акциями</a></h2>
    <h2>Список пользователей</h2>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Фамилия</th>
            <th>Логин</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Действия</th>
        </tr>
        <?php while ($user = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                <td><?php echo htmlspecialchars($user['login']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['is_admin'] ? 'Администратор' : 'Пользователь'; ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>">Редактировать</a>
                    <a href="delete_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="../index.php">Выход</a>
</body>
</html>