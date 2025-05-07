<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Обработка добавления нового пункта меню
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu_item'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0; // Проверка, отмечен ли пункт как популярный

    // Обработка загрузки изображения
    $image = $_FILES['image']['name'];
    $target = "../uploads/" . basename($image); // Путь для сохранения изображения

    // Перемещение загруженного файла в папку uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Сохранение пути к изображению и статуса популярности в базе данных
        mysqli_query($connect, "INSERT INTO `menu` (`title`, `price`, `image`, `is_popular`) VALUES ('$title', '$price', '$target', '$is_popular')");
    } else {
        echo "Ошибка загрузки изображения.";
    }
}

// Получение всех пунктов меню
$menuItems = mysqli_query($connect, "SELECT * FROM `menu`");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление меню</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <h1>Управление меню</h1>
    
    <h2>Добавить пункт меню</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Название</label>
        <input type="text" name="title" required>
        <label>Цена (₽)</label>
        <input type="number" name="price" step="0.01" required>
        <label>Изображение</label>
        <input type="file" name="image" accept="image/*" required>
        <label>Популярный</label>
        <input type="checkbox" name="is_popular" value="1">
        <button type="submit" name="add_menu_item">Добавить пункт меню</button>
    </form>
    
    <h2>Список пунктов меню</h2>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Цена (₽)</th>
            <th>Изображение</th>
            <th>Популярный</th>
            <th>Действия</th>
        </tr>
        <?php while ($item = mysqli_fetch_assoc($menuItems)): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['id']); ?></td>
                <td><?php echo htmlspecialchars($item['title']); ?></td>
                <td><?php echo htmlspecialchars($item['price']); ?></td>
                <td><img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" width="50"></td>
                <td>
                    <form action="toggle_popular.php" method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
                        <input type="checkbox" name="is_popular" value="1" <?php echo $item['is_popular'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                    </form>
                </td>
                <td>
                    <a href="edit_menu_item.php?id=<?php echo htmlspecialchars($item['id']); ?>">Редактировать</a>
                    <a href="delete_menu_item.php?id=<?php echo htmlspecialchars($item['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить этот пункт меню?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>