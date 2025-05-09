<?php
session_start();
require_once '../config/connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin']) && !isset($_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$upload_dir_main = "../uploads/";
$upload_dir_gallery = "../uploads/news_gallery/";

if (!is_dir($upload_dir_main)) mkdir($upload_dir_main, 0777, true);
if (!is_dir($upload_dir_gallery)) mkdir($upload_dir_gallery, 0777, true);

function sanitize_filename($filename) {
    return preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']) ?: NULL;
    $author_id = !empty(trim($_POST['author_id'])) ? intval(trim($_POST['author_id'])) : NULL;
    $date = date('Y-m-d H:i:s');
    $content_preview = mb_substr(strip_tags($content), 0, 150) . (mb_strlen(strip_tags($content)) > 150 ? '...' : '');

    $main_image_path = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $main_image_name = sanitize_filename(basename($_FILES['image']['name']));
        $main_image_target = $upload_dir_main . $main_image_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $main_image_target)) {
            $main_image_path = $main_image_target;
        } else {
            $_SESSION['error_message'] = "Ошибка загрузки основного изображения.";
        }
    }

    if (!isset($_SESSION['error_message'])) {
        $stmt = $connect->prepare("INSERT INTO `news` (`title`, `content_preview`, `content`, `image`, `date`, `author_id`, `video_url`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssis", $title, $content_preview, $content, $main_image_path, $date, $author_id, $video_url);
            if ($stmt->execute()) {
                $news_id = $stmt->insert_id;
                $_SESSION['success_message'] = "Новость успешно добавлена.";

                // Handle gallery images
                if (isset($_FILES['gallery_images'])) {
                    $gallery_stmt = $connect->prepare("INSERT INTO `news_images` (`news_id`, `image_path`) VALUES (?, ?)");
                    if ($gallery_stmt) {
                        foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                            if ($_FILES['gallery_images']['error'][$key] == UPLOAD_ERR_OK) {
                                $gallery_image_name = sanitize_filename(basename($name));
                                $gallery_image_target = $upload_dir_gallery . $gallery_image_name;
                                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$key], $gallery_image_target)) {
                                    $gallery_stmt->bind_param("is", $news_id, $gallery_image_target);
                                    $gallery_stmt->execute();
                                }
                            }
                        }
                        $gallery_stmt->close();
                    } else {
                         $_SESSION['error_message'] = "Ошибка подготовки запроса для галереи: " . $connect->error;
                    }
                }
            } else {
                $_SESSION['error_message'] = "Ошибка добавления новости: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Ошибка подготовки запроса: " . $connect->error;
        }
    }
    header('Location: manage_news.php');
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Get main image path to delete file
    $stmt_main_img = $connect->prepare("SELECT image FROM news WHERE id = ?");
    $stmt_main_img->bind_param("i", $delete_id);
    $stmt_main_img->execute();
    $result_main_img = $stmt_main_img->get_result();
    if ($row_main = $result_main_img->fetch_assoc()) {
        if (!empty($row_main['image']) && file_exists($row_main['image'])) {
            unlink($row_main['image']);
        }
    }
    $stmt_main_img->close();

    // Get gallery image paths to delete files
    $stmt_gallery_imgs = $connect->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
    $stmt_gallery_imgs->bind_param("i", $delete_id);
    $stmt_gallery_imgs->execute();
    $result_gallery_imgs = $stmt_gallery_imgs->get_result();
    while ($row_gallery = $result_gallery_imgs->fetch_assoc()) {
        if (!empty($row_gallery['image_path']) && file_exists($row_gallery['image_path'])) {
            unlink($row_gallery['image_path']);
        }
    }
    $stmt_gallery_imgs->close();

    // Delete from news_images first due to foreign key constraints if any (though not explicitly defined in your dump for ON DELETE CASCADE for news_images)
    $stmt_del_gallery = $connect->prepare("DELETE FROM `news_images` WHERE `news_id` = ?");
    $stmt_del_gallery->bind_param("i", $delete_id);
    $stmt_del_gallery->execute();
    $stmt_del_gallery->close();

    // Delete from news
    $stmt_del_news = $connect->prepare("DELETE FROM `news` WHERE `id` = ?");
    $stmt_del_news->bind_param("i", $delete_id);
    if ($stmt_del_news->execute()) {
        $_SESSION['success_message'] = "Новость успешно удалена.";
    } else {
        $_SESSION['error_message'] = "Ошибка удаления новости: " . $stmt_del_news->error;
    }
    $stmt_del_news->close();

    header('Location: manage_news.php');
    exit();
}

$news_query_result = $connect->query("SELECT n.*, u.login as author_login FROM `news` n LEFT JOIN `user` u ON n.author_id = u.id ORDER BY n.date DESC");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление новостями</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>
    <div class="container">
        <h1>Управление новостями</h1>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<p class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<p class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
            unset($_SESSION['error_message']);
        }
        ?>

        <h2>Добавить новую новость</h2>
        <form action="manage_news.php" method="post" enctype="multipart/form-data" class="form-styled">
            <div>
                <label for="title">Название:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
    <label for="content">Содержимое:</label>
        <div class="bbcode-toolbar">
                <button type="button" onclick="insertBBCode('b')"><b>B</b></button>
                <button type="button" onclick="insertBBCode('h2')">H2</button>
                <button type="button" onclick="insertBBCode('h3')">H3</button>
                <button type="button" onclick="insertBBCode('quote')">Цитата</button>
                <button type="button" onclick="insertBBCode('ul')">Список UL</button>
                <button type="button" onclick="insertBBCode('ol')">Список OL</button>
                <button type="button" onclick="insertBBCode('li')">Пункт списка</button>
            </div>
            <textarea id="content" name="content" rows="10" required></textarea>
        </div>
            <div>
                <label for="image">Основное изображение:</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <div>
                <label for="gallery_images">Дополнительные изображения (галерея):</label>
                <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
            </div>
            <div>
                <label for="video_url">URL видео (например, YouTube embed link):</label>
                <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/embed/VIDEO_ID">
            </div>
            <div>
                <label for="author_id">ID Автора (пользователя, необязательно):</label>
                <input type="number" id="author_id" name="author_id">
            </div>
            <button type="submit" name="add_news">Добавить новость</button>
        </form>

        <h2>Список новостей</h2>
        <table class="table-styled">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Превью</th>
                    <th>Основное изображение</th>
                    <th>Автор</th>
                    <th>Видео URL</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($news_query_result && $news_query_result->num_rows > 0): ?>
                    <?php while ($news_item = $news_query_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($news_item['id']); ?></td>
                            <td><?php echo htmlspecialchars($news_item['title']); ?></td>
                            <td><?php echo htmlspecialchars($news_item['content_preview']); ?></td>
                            <td>
                                <?php if (!empty($news_item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($news_item['image']); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" width="100">
                                <?php else: ?>
                                    Нет изображения
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($news_item['author_login'] ?: ($news_item['author_id'] ?: 'N/A')); ?></td>
                            <td><?php echo htmlspecialchars($news_item['video_url'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($news_item['date']))); ?></td>
                            <td>
                                <a href="edit_news.php?id=<?php echo htmlspecialchars($news_item['id']); ?>" class="btn btn-edit">Редактировать</a>
                                <a href="manage_news.php?delete_id=<?php echo htmlspecialchars($news_item['id']); ?>" class="btn btn-delete" onclick="return confirm('Вы уверены, что хотите удалить эту новость и все связанные с ней изображения?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Новостей пока нет.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <a href="admin_dashboard.php" class="btn">Назад в админ-панель</a>
    </div>
    <script>
        function insertBBCode(tag) {
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        let replacement = '';

        if (tag === 'ul' || tag === 'ol') {
            replacement = `[${tag}]\n[li]Пункт 1[/li]\n[li]Пункт 2[/li]\n[/${tag}]`;
        } else if (tag === 'li') {
            replacement = `[${tag}]${selectedText || 'Текст пункта'}[/${tag}]`;
        }
        else {
            replacement = `[${tag}]${selectedText || 'Текст'}[/${tag}]`;
        }

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        // Позиционируем курсор после вставленного тега (можно улучшить)
        textarea.selectionStart = start + replacement.length - (selectedText ? `[/${tag}]`.length : 0);
        textarea.selectionEnd = textarea.selectionStart;
    }
    </script>
</body>
</html>