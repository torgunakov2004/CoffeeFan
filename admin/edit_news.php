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
    $content = $_POST['content'];

    // Проверка, загружено ли новое изображение
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        $target = "../uploads/" . basename($image);
        
        // Проверка успешной загрузки
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // Успешная загрузка
            $query = "UPDATE `news` SET `title` = ?, `content` = ?, `image` = ?, `content_preview` = ? WHERE `id` = ?";
            $stmt = mysqli_prepare($connect, $query);
            $content_preview = substr($content, 0, 100) . '....'; // Создание превью из первых 100 символов с многоточием
            mysqli_stmt_bind_param($stmt, 'ssssi', $title, $content, $target, $content_preview, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo "Ошибка загрузки изображения.";
        }
    } else {
        // Если изображение не загружено, просто обновляем другие поля
        $query = "UPDATE `news` SET `title` = ?, `content` = ?, `content_preview` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare($connect, $query);
        $content_preview = substr($content, 0, 100) . '....'; // Создание превью из первых 100 символов с многоточием
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $content, $content_preview, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: manage_news.php');
    exit();
}

// Получение новости для редактирования
$id = $_GET['id'];
$news_item = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `news` WHERE `id` = '$id'"));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать новость</title>
</head>
<body>
    <h1>Редактировать новость</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($news_item['id']); ?>">
        <label>Название</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($news_item['title']); ?>" required>
        <label>Содержимое</label>
        <textarea name="content" required><?php echo htmlspecialchars($news_item['content']); ?></textarea>
        <label>Изображение (оставьте пустым, если не хотите менять)</label>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Сохранить изменения</button>
    </form>
    <a href="manage_news.php">Назад к управлению новостями</a>
</body>
</html>