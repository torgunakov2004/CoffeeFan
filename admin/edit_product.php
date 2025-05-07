<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $price = $_POST['price'];

    // Проверка, загружено ли новое изображение
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        $target = "../uploads/" . basename($image);
        
        // Проверка успешной загрузки
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // Успешная загрузка
            $query = "UPDATE `products` SET `title` = ?, `price` = ?, `image` = ? WHERE `id` = ?";
            $stmt = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param($stmt, 'sdsi', $title, $price, $target, $id); // Изменено на $target
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo "Ошибка загрузки изображения.";
        }
    } else {
        // Если изображение не загружено, просто обновляем другие поля
        $query = "UPDATE `products` SET `title` = ?, `price` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, 'sdi', $title, $price, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: manage_products.php');
    exit();
}

// Получение продукта для редактирования
$id = $_GET['id'];
$product = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `products` WHERE `id` = '$id'"));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать продукт</title>
</head>
<body>
    <h1>Редактировать продукт</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
        <label>Название</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
        <label>Цена</label>
        <input type="number" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        <label>Изображение (оставьте пустым, если не хотите менять)</label>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Сохранить изменения</button>
    </form>
    <a href="manage_products.php">Назад к управлению продуктами</a>
</body>
</html>