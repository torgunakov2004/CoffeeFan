<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Функция для получения всех новостей
function getAllNews($connect) {
    $query = "SELECT * FROM `news`"; // Предполагается, что у вас есть таблица `news`
    return mysqli_query($connect, $query);
}

// Получение всех новостей
$news = getAllNews($connect);

// Обработка удаления новости
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    mysqli_query($connect, "DELETE FROM `news` WHERE `id` = '$delete_id'");
    header('Location: manage_news.php');
    exit();
}

// Обработка добавления новой новости
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $content_preview = mb_substr($content, 0, 100) . '....'; // Создание превью из первых 100 символов
    $image = $_FILES['image']['name'];
    $date = date('Y-m-d H:i:s'); // Текущая дата и время
    $target = "../uploads/" . basename($image);

    // Перемещение загруженного файла в папку uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Сохранение полного пути к изображению в базе данных
        $full_image_path = "../uploads/" . basename($image); // Путь относительно корня сайта
        $query = "INSERT INTO `news` (`title`, `content_preview`, `content`, `image`, `date`) VALUES ('$title', '$content_preview', '$content', '$full_image_path', '$date')";
        
        if (mysqli_query($connect, $query)) {
            echo "Новость добавлена успешно.";
        } else {
            echo "Ошибка: " . mysqli_error($connect);
        }
    } else {
        echo "Ошибка загрузки изображения.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление новостями</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <h1>Управление новостями</h1>

    <h2>Добавить новую новость</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Название</label>
        <input type="text" name="title" required>
        <label>Содержимое</label>
        <textarea name="content" required></textarea>
        <label>Изображение</label>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" name="add_news">Добавить новость</button>
    </form>

    <h2>Список новостей</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Содержимое</th>
            <th>Изображение</th>
            <th>Действия</th>
        </tr>
        <?php while ($news_item = mysqli_fetch_assoc($news)): ?>
            <tr>
                <td><?php echo htmlspecialchars($news_item['id']); ?></td>
                <td><?php echo htmlspecialchars($news_item['title']); ?></td>
                <td><?php echo htmlspecialchars($news_item['content']); ?></td>
                <td><img src="<?php echo htmlspecialchars($news_item['image']); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" width="50"></td>
                <td>
                    <a href="edit_news.php?id=<?php echo htmlspecialchars($news_item['id']); ?>">Редактировать</a>
                    <a href="?delete_id=<?php echo htmlspecialchars($news_item['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить эту новость?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>