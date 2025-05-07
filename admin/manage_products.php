<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Инициализация переменной
    $is_popular = isset($_POST['is_popular']) ? $_POST['is_popular'] : [];

    foreach ($is_popular as $id => $value) {
        $is_popular = $value ? 1 : 0;
        mysqli_query($connect, "UPDATE `products` SET `is_popular` = '$is_popular' WHERE `id` = '$id'");
    }
}
// Обработка добавления нового продукта
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];

    // Обработка загрузки изображения
    $image = $_FILES['image']['name'];
    $target = "../uploads/" . basename($image); // Путь для сохранения изображения

    // Перемещение загруженного файла в папку uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Сохранение пути к изображению в базе данных
        mysqli_query($connect, "INSERT INTO `products` (`title`, `price`, `image`) VALUES ('$title', '$price', '$target')");
    } else {
        echo "Ошибка загрузки изображения.";
    }
}

// Обработка удаления продукта
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    mysqli_query($connect, "DELETE FROM `products` WHERE `id` = '$delete_id'");
}

// Получение всех продуктов
$products = mysqli_query($connect, "SELECT * FROM `products`");

if (!$products) {
    // Если запрос не выполнен, выводим ошибку
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление продуктами</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="JS.js"></script>
</head>
<body>
    <h1>Управление продуктами</h1>

    <h2>Добавить новый продукт</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Название</label>
        <input type="text" name="title" required>
        <label>Цена</label>
        <input type="number" name="price" required>
        <label>Изображение</label>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" name="add_product">Добавить продукт</button>
    </form>

    <h2>Список продуктов</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Цена</th>
            <th>Изображение</th>
            <th>Действия</th>
        </tr>
        <?php while ($product = mysqli_fetch_assoc($products)): ?>
            <tr>
                <td><?php echo $product['id']; ?></td>
                <td><?php echo $product['title']; ?></td>
                <td><?php echo $product['price']; ?></td>
                <td><img src="<?php echo $product['image']; ?>" alt="<?php echo $product['title']; ?>" width="50"></td>
                <td>
                    <input type="checkbox" name="is_popular[<?php echo $product['id']; ?>]" <?php echo $product['is_popular'] ? 'checked' : ''; ?>>
                </td>
                <td>
                    <a href="edit_product.php?id=<?php echo $product['id']; ?>">Редактировать</a>
                    <a href="?delete_id=<?php echo $product['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить этот продукт?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
            <button id="save-popularity" style="display: none;">Сохранить изменения</button>
    </table>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>