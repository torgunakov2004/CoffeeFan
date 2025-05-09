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

function sanitize_filename($filename) {
    return preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
}

$news_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($news_id === 0) {
    $_SESSION['error_message'] = "ID новости не указан.";
    header('Location: manage_news.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_news'])) {
    $id = $news_id;
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']) ?: NULL;
    $author_id = !empty(trim($_POST['author_id'])) ? intval(trim($_POST['author_id'])) : NULL;
    $content_preview = mb_substr(strip_tags($content), 0, 150) . (mb_strlen(strip_tags($content)) > 150 ? '...' : '');

    // Fetch current main image to compare and delete if new one is uploaded
    $current_main_image_path_db = $connect->query("SELECT image FROM news WHERE id = $id")->fetch_assoc()['image'] ?? NULL;
    $new_main_image_path = $current_main_image_path_db; // Default to current

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $main_image_name = sanitize_filename(basename($_FILES['image']['name']));
        $main_image_target = $upload_dir_main . $main_image_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $main_image_target)) {
            // Delete old main image if it exists and is different from new one
            if (!empty($current_main_image_path_db) && $current_main_image_path_db != $main_image_target && file_exists($current_main_image_path_db)) {
                unlink($current_main_image_path_db);
            }
            $new_main_image_path = $main_image_target;
        } else {
            $_SESSION['error_message'] = "Ошибка загрузки нового основного изображения.";
        }
    }

    if (!isset($_SESSION['error_message'])) {
        $stmt_update_news = $connect->prepare("UPDATE `news` SET `title` = ?, `content_preview` = ?, `content` = ?, `image` = ?, `author_id` = ?, `video_url` = ? WHERE `id` = ?");
        if ($stmt_update_news) {
            $stmt_update_news->bind_param("ssssisi", $title, $content_preview, $content, $new_main_image_path, $author_id, $video_url, $id);
            if (!$stmt_update_news->execute()) {
                $_SESSION['error_message'] = "Ошибка обновления новости: " . $stmt_update_news->error;
            } else {
                 $_SESSION['success_message'] = "Новость успешно обновлена.";
            }
            $stmt_update_news->close();
        } else {
            $_SESSION['error_message'] = "Ошибка подготовки запроса обновления новости: " . $connect->error;
        }
    }


    // Handle gallery image deletions
    if (isset($_POST['delete_gallery_image']) && is_array($_POST['delete_gallery_image'])) {
        $delete_gallery_stmt = $connect->prepare("DELETE FROM `news_images` WHERE `id` = ? AND `news_id` = ?");
        $select_path_stmt = $connect->prepare("SELECT image_path FROM `news_images` WHERE `id` = ?");

        foreach ($_POST['delete_gallery_image'] as $gallery_image_id_to_delete) {
            $gallery_image_id_to_delete = intval($gallery_image_id_to_delete);

            // Get path to delete file
            $select_path_stmt->bind_param("i", $gallery_image_id_to_delete);
            $select_path_stmt->execute();
            $path_result = $select_path_stmt->get_result();
            if ($path_row = $path_result->fetch_assoc()) {
                if (!empty($path_row['image_path']) && file_exists($path_row['image_path'])) {
                    unlink($path_row['image_path']);
                }
            }
            
            $delete_gallery_stmt->bind_param("ii", $gallery_image_id_to_delete, $id);
            $delete_gallery_stmt->execute();
        }
        $delete_gallery_stmt->close();
        $select_path_stmt->close();
    }

    // Handle new gallery image uploads
    if (isset($_FILES['new_gallery_images'])) {
        $new_gallery_stmt = $connect->prepare("INSERT INTO `news_images` (`news_id`, `image_path`) VALUES (?, ?)");
        if ($new_gallery_stmt) {
            foreach ($_FILES['new_gallery_images']['name'] as $key => $name) {
                if ($_FILES['new_gallery_images']['error'][$key] == UPLOAD_ERR_OK) {
                    $gallery_image_name = sanitize_filename(basename($name));
                    $gallery_image_target = $upload_dir_gallery . $gallery_image_name;
                    if (move_uploaded_file($_FILES['new_gallery_images']['tmp_name'][$key], $gallery_image_target)) {
                        $new_gallery_stmt->bind_param("is", $id, $gallery_image_target);
                        $new_gallery_stmt->execute();
                    }
                }
            }
            $new_gallery_stmt->close();
        } else {
            $_SESSION['error_message'] = (isset($_SESSION['error_message']) ? $_SESSION['error_message'] . " " : "") . "Ошибка подготовки запроса для новых галерейных изображений: " . $connect->error;
        }
    }
    
    if (!isset($_SESSION['error_message']) && !isset($_SESSION['success_message'])) { // If no specific message, assume success
        $_SESSION['success_message'] = "Новость успешно обновлена.";
    }


    header('Location: manage_news.php');
    exit();
}


$stmt_get_news = $connect->prepare("SELECT * FROM `news` WHERE `id` = ?");
$stmt_get_news->bind_param("i", $news_id);
$stmt_get_news->execute();
$news_item_result = $stmt_get_news->get_result();
if ($news_item_result->num_rows === 0) {
    $_SESSION['error_message'] = "Новость с ID $news_id не найдена.";
    header('Location: manage_news.php');
    exit();
}
$news_item = $news_item_result->fetch_assoc();
$stmt_get_news->close();

$stmt_get_gallery = $connect->prepare("SELECT * FROM `news_images` WHERE `news_id` = ? ORDER BY id ASC");
$stmt_get_gallery->bind_param("i", $news_id);
$stmt_get_gallery->execute();
$gallery_images_result = $stmt_get_gallery->get_result();
$gallery_images = [];
while($row = $gallery_images_result->fetch_assoc()){
    $gallery_images[] = $row;
}
$stmt_get_gallery->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать новость</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>
    <div class="container">
        <h1>Редактировать новость: <?php echo htmlspecialchars($news_item['title']); ?></h1>

        <form action="edit_news.php" method="post" enctype="multipart/form-data" class="form-styled">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($news_item['id']); ?>">
            <div>
                <label for="title">Название:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($news_item['title']); ?>" required>
            </div>
            <div>
                <label for="content">Содержимое (BB-коды):</label>
                <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($news_item['content']); ?></textarea>
            </div>
            <div>
                <label for="image">Основное изображение (оставьте пустым, если не хотите менять):</label>
                <?php if (!empty($news_item['image'])): ?>
                    <p><img src="<?php echo htmlspecialchars($news_item['image']); ?>" alt="Текущее изображение" width="150"></p>
                <?php endif; ?>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <div>
                <h3>Галерея изображений</h3>
                <?php if (!empty($gallery_images)): ?>
                    <div class="gallery-edit">
                        <?php foreach ($gallery_images as $img): ?>
                            <div class="gallery-item-edit">
                                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery image" width="100">
                                <label>
                                    <input type="checkbox" name="delete_gallery_image[]" value="<?php echo $img['id']; ?>"> Удалить
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Нет дополнительных изображений в галерее.</p>
                <?php endif; ?>
                <label for="new_gallery_images">Добавить новые изображения в галерею:</label>
                <input type="file" id="new_gallery_images" name="new_gallery_images[]" accept="image/*" multiple>
            </div>

            <div>
                <label for="video_url">URL видео:</label>
                <input type="url" id="video_url" name="video_url" value="<?php echo htmlspecialchars($news_item['video_url'] ?? ''); ?>" placeholder="https://www.youtube.com/embed/VIDEO_ID">
            </div>
            <div>
                <label for="author_id">ID Автора:</label>
                <input type="number" id="author_id" name="author_id" value="<?php echo htmlspecialchars($news_item['author_id'] ?? ''); ?>">
            </div>
            <button type="submit" name="edit_news">Сохранить изменения</button>
        </form>
        <br>
        <a href="manage_news.php" class="btn">Назад к управлению новостями</a>
    </div>
</body>
</html>