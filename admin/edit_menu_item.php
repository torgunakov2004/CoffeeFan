<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

$id = $_GET['id'];
$item = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `menu` WHERE `id` = '$id'"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    
    // Обработка загрузки изображения
    $image = $_FILES['image']['name'];
    $target = "../uploads/" . basename($image); // Путь для сохранения изображения

    // Если изображение загружено, перемещаем его в папку uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Обновление записи в базе данных с новым изображением
        mysqli_query($connect, "UPDATE `menu` SET `title` = '$title', `price` = '$price', `image` = '$target' WHERE `id` = '$id'");
    } else {
        // Если изображение не загружено, обновляем только название и цену
        mysqli_query($connect, "UPDATE `menu` SET `title` = '$title', `price` = '$price' WHERE `id` = '$id'");
    }
    
    header('Location: manage_menu.php');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пункт меню</title>
</head>
<body>
    <h1>Редактировать пункт меню</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
        <label>Название</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
        <label>Цена (₽)</label>
        <input type="number" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" step="0.01" required>
        <label>Изображение (выберите файл)</label>
        <input type="file" name="image" accept="image/*">
        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" width="50">
        <button type="submit">Сохранить</button>
    </form>
    <a href="manage_menu.php">Назад</a>
</body>
</html>