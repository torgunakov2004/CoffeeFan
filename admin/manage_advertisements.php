<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Получение текущего текста "Новинка 2025"
$adTextQuery = "SELECT * FROM advertisement_text LIMIT 1";
$adTextResult = mysqli_query($connect, $adTextQuery);
$adText = mysqli_fetch_assoc($adTextResult);

// Обновление текста "Новинка 2025"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ad_text'])) {
    $newText = mysqli_real_escape_string($connect, $_POST['ad_text']);
    $query = "UPDATE advertisement_text SET text = '$newText' WHERE id = 1";
    if (mysqli_query($connect, $query)) {
        header('Location: manage_advertisements.php');
        exit();
    } else {
        echo "Ошибка при обновлении текста: " . mysqli_error($connect);
    }
}

// Добавление новой рекламы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_advertisement'])) {
    $title = mysqli_real_escape_string($connect, $_POST['title']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $link = mysqli_real_escape_string($connect, $_POST['link']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Загрузка изображения
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/advertisements/';

        // Проверяем, существует ли папка, если нет — создаем
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadFile = $uploadDir . basename($_FILES['image']['name']);

        // Проверяем, удалось ли загрузить файл
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = $uploadFile;
        } else {
            echo "Ошибка при загрузке файла. Проверьте права доступа к папке.";
            exit();
        }
    }

    // Если новая реклама активна, деактивируем все остальные
    if ($is_active) {
        $query = "UPDATE advertisements SET is_active = 0";
        if (!mysqli_query($connect, $query)) {
            echo "Ошибка при деактивации старых реклам: " . mysqli_error($connect);
            exit();
        }
    }

    // Добавляем новую рекламу
    $query = "INSERT INTO advertisements (title, description, image, link, is_active) VALUES ('$title', '$description', '$image', '$link', '$is_active')";
    if (mysqli_query($connect, $query)) {
        header('Location: manage_advertisements.php');
        exit();
    } else {
        echo "Ошибка при добавлении рекламы: " . mysqli_error($connect);
    }
}

// Удаление рекламы
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM advertisements WHERE id = $id";
    mysqli_query($connect, $query);
    header('Location: manage_advertisements.php');
    exit();
}

// Получение всех реклам
$query = "SELECT * FROM advertisements";
$advertisements = mysqli_query($connect, $query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление рекламой</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Управление рекламой</h1>

    <!-- Форма для редактирования текста "Новинка 2025" -->
    <form method="POST">
        <label for="ad_text">Название карточки</label>
        <input type="text" name="ad_text" value="<?php echo htmlspecialchars($adText['text']); ?>" required>
        <button type="submit" name="update_ad_text">Обновить</button>
    </form>

    <!-- Форма для добавления новой рекламы -->
    <h2>Добавить новую рекламу</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Заголовок:</label>
        <input type="text" name="title" required><br>
        
        <label for="description">Описание:</label>
        <textarea name="description" required></textarea><br>
        
        <label for="image">Изображение:</label>
        <input type="file" name="image" required><br>
        
        <label for="link">Ссылка:</label>
        <input type="text" name="link" required><br>
        
        <label for="is_active">Активна:</label>
        <input type="checkbox" name="is_active"><br>
        
        <button type="submit" name="add_advertisement">Добавить</button>
    </form>

    <!-- Список реклам -->
    <h2>Список реклам</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Заголовок</th>
            <th>Описание</th>
            <th>Изображение</th>
            <th>Ссылка</th>
            <th>Активна</th>
            <th>Действия</th>
        </tr>
        <?php while ($ad = mysqli_fetch_assoc($advertisements)): ?>
            <tr>
                <td><?php echo htmlspecialchars($ad['id']); ?></td>
                <td><?php echo htmlspecialchars($ad['title']); ?></td>
                <td><?php echo htmlspecialchars($ad['description']); ?></td>
                <td><img src="<?php echo htmlspecialchars($ad['image']); ?>" width="100"></td>
                <td><?php echo htmlspecialchars($ad['link']); ?></td>
                <td><?php echo $ad['is_active'] ? 'Да' : 'Нет'; ?></td>
                <td>
                    <a href="?delete=<?php echo htmlspecialchars($ad['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить эту рекламу?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="admin_dashboard.php">Назад в админ панель</a>
</body>
</html>