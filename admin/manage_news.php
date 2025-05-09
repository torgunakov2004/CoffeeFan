<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

$upload_dir_main = '../uploads/news_main/';
$upload_dir_gallery = '../uploads/news_gallery/';

if (!is_dir($upload_dir_main)) mkdir($upload_dir_main, 0777, true);
if (!is_dir($upload_dir_gallery)) mkdir($upload_dir_gallery, 0777, true);

function sanitize_filename_news($filename) {
    $filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    $filename = strtolower($filename);
    return $filename;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content_preview = trim($_POST['content_preview']); // Получаем превью из нового поля
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']) ?: NULL;
    $author_id = !empty(trim($_POST['author_id'])) ? (int)trim($_POST['author_id']) : NULL;
    $date = date('Y-m-d H:i:s');
    $errors_add = [];

    if (empty($title)) $errors_add[] = "Заголовок обязателен.";
    if (empty($content_preview)) $errors_add[] = "Краткое описание (превью) обязательно."; // Добавили проверку
    if (empty($content)) $errors_add[] = "Полное содержимое новости обязательно.";


    $main_image_path_db = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_name_main = uniqid('news_main_', true) . '_' . sanitize_filename_news(basename($_FILES['image']['name']));
        $target_file_main = $upload_dir_main . $file_name_main;
        $imageFileType_main = strtolower(pathinfo($target_file_main, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType_main, $allowed_types)) {
            $errors_add[] = "Основное изображение: недопустимый тип файла.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { 
            $errors_add[] = "Основное изображение: файл слишком большой (макс 5MB).";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_main)) {
                $main_image_path_db = 'uploads/news_main/' . $file_name_main;
            } else {
                $errors_add[] = "Ошибка загрузки основного изображения.";
            }
        }
    } else {
         $errors_add[] = "Основное изображение обязательно для новости.";
    }


    if (empty($errors_add)) {
        $stmt = $connect->prepare("INSERT INTO `news` (`title`, `content_preview`, `content`, `image`, `date`, `author_id`, `video_url`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            // content_preview теперь берется из формы
            $stmt->bind_param("sssssis", $title, $content_preview, $content, $main_image_path_db, $date, $author_id, $video_url);
            if ($stmt->execute()) {
                $news_id = $stmt->insert_id;
                $_SESSION['admin_message'] = "Новость успешно добавлена.";
                $_SESSION['admin_message_type'] = "success";

                if (isset($_FILES['gallery_images'])) {
                    $gallery_stmt = $connect->prepare("INSERT INTO `news_images` (`news_id`, `image_path`) VALUES (?, ?)");
                    if ($gallery_stmt) {
                        foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                            if ($_FILES['gallery_images']['error'][$key] == UPLOAD_ERR_OK) {
                                $file_name_gallery = uniqid('news_gallery_', true) . '_' . sanitize_filename_news(basename($name));
                                $target_file_gallery = $upload_dir_gallery . $file_name_gallery;
                                $imageFileType_gallery = strtolower(pathinfo($target_file_gallery, PATHINFO_EXTENSION));
                                $allowed_types_gallery = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // Определяем здесь, если нужно

                                if (!in_array($imageFileType_gallery, $allowed_types_gallery)) {
                                    $_SESSION['admin_message'] .= "<br>Галерея: Файл '$name' имеет недопустимый тип.";
                                    $_SESSION['admin_message_type'] = "error";
                                    continue;
                                }
                                if ($_FILES['gallery_images']['size'][$key] > 3 * 1024 * 1024) { 
                                    $_SESSION['admin_message'] .= "<br>Галерея: Файл '$name' слишком большой (макс 3MB).";
                                     $_SESSION['admin_message_type'] = "error";
                                    continue;
                                }

                                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$key], $target_file_gallery)) {
                                    $gallery_image_path_db = 'uploads/news_gallery/' . $file_name_gallery;
                                    $gallery_stmt->bind_param("is", $news_id, $gallery_image_path_db);
                                    if(!$gallery_stmt->execute()){
                                        $_SESSION['admin_message'] .= "<br>Ошибка добавления изображения галереи '$name' в БД: " . $gallery_stmt->error;
                                        $_SESSION['admin_message_type'] = "error";
                                    }
                                } else {
                                     $_SESSION['admin_message'] .= "<br>Ошибка загрузки файла галереи '$name'.";
                                     $_SESSION['admin_message_type'] = "error";
                                }
                            }
                        }
                        $gallery_stmt->close();
                    } else {
                         $_SESSION['admin_message'] .= "<br>Ошибка подготовки запроса для галереи: " . $connect->error;
                         $_SESSION['admin_message_type'] = "error";
                    }
                }
            } else {
                $_SESSION['admin_message'] = "Ошибка добавления новости: " . $stmt->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = "Ошибка подготовки запроса добавления новости: " . $connect->error;
            $_SESSION['admin_message_type'] = "error";
        }
    } else {
        $_SESSION['admin_message'] = implode("<br>", $errors_add);
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_news.php');
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    $stmt_get_main_img = $connect->prepare("SELECT image FROM news WHERE id = ?");
    $stmt_get_main_img->bind_param("i", $delete_id);
    $stmt_get_main_img->execute();
    $result_main_img = $stmt_get_main_img->get_result();
    if ($row_main = $result_main_img->fetch_assoc()) {
        if (!empty($row_main['image']) && file_exists('../' . ltrim($row_main['image'], '/'))) {
            unlink('../' . ltrim($row_main['image'], '/'));
        }
    }
    $stmt_get_main_img->close();

    $stmt_get_gallery_imgs = $connect->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
    $stmt_get_gallery_imgs->bind_param("i", $delete_id);
    $stmt_get_gallery_imgs->execute();
    $result_gallery_imgs = $stmt_get_gallery_imgs->get_result();
    while ($row_gallery = $result_gallery_imgs->fetch_assoc()) {
        if (!empty($row_gallery['image_path']) && file_exists('../' . ltrim($row_gallery['image_path'], '/'))) {
            unlink('../' . ltrim($row_gallery['image_path'], '/'));
        }
    }
    $stmt_get_gallery_imgs->close();

    $stmt_del_gallery = $connect->prepare("DELETE FROM `news_images` WHERE `news_id` = ?");
    $stmt_del_gallery->bind_param("i", $delete_id);
    $stmt_del_gallery->execute();
    $stmt_del_gallery->close();
    
    $stmt_del_comments = $connect->prepare("DELETE FROM `news_comments` WHERE `news_id` = ?");
    $stmt_del_comments->bind_param("i", $delete_id);
    $stmt_del_comments->execute();
    $stmt_del_comments->close();

    $stmt_del_news = $connect->prepare("DELETE FROM `news` WHERE `id` = ?");
    if ($stmt_del_news) {
        $stmt_del_news->bind_param("i", $delete_id);
        if ($stmt_del_news->execute()) {
            $_SESSION['admin_message'] = "Новость успешно удалена.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Ошибка удаления новости: " . $stmt_del_news->error;
            $_SESSION['admin_message_type'] = "error";
        }
        $stmt_del_news->close();
    } else {
         $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления новости: " . $connect->error;
         $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_news.php');
    exit();
}

$stmt_news_list = $connect->prepare("SELECT n.*, u.login as author_login FROM `news` n LEFT JOIN `user` u ON n.author_id = u.id ORDER BY n.date DESC");
$news_list_arr = [];
if ($stmt_news_list) {
    $stmt_news_list->execute();
    $result_news_list = $stmt_news_list->get_result();
    while ($row = $result_news_list->fetch_assoc()) {
        $news_list_arr[] = $row;
    }
    $stmt_news_list->close();
} else {
     error_log("Manage News: Failed to prepare news list query: " . $connect->error);
}


if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Новостями - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Управление Новостями</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo nl2br($message); ?></div>
        <?php endif; ?>

        <div class="admin-content">
            <h2>Добавить новую новость</h2>
            <form action="manage_news.php" method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="title_add">Название:</label>
                    <input type="text" id="title_add" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content_preview_add">Краткое описание (превью, будет видно в списке новостей):</label>
                    <textarea id="content_preview_add" name="content_preview" rows="3" required maxlength="255"></textarea>
                </div>
                <div class="form-group">
                    <label for="content_add">Полное содержимое новости (поддерживаются BB-коды):</label>
                    <div class="bbcode-toolbar">
                        <button type="button" onclick="insertBBCode('b')"><b>B</b></button>
                        <button type="button" onclick="insertBBCode('h2')">H2</button>
                        <button type="button" onclick="insertBBCode('h3')">H3</button>
                        <button type="button" onclick="insertBBCode('quote')">Цитата</button>
                        <button type="button" onclick="insertBBCode('ul')">UL</button>
                        <button type="button" onclick="insertBBCode('ol')">OL</button>
                        <button type="button" onclick="insertBBCode('li')">LI</button>
                    </div>
                    <textarea id="content_add" name="content" rows="10" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image_add">Основное изображение:</label>
                    <input type="file" id="image_add" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="gallery_images_add">Дополнительные изображения (галерея):</label>
                    <input type="file" id="gallery_images_add" name="gallery_images[]" accept="image/*" multiple>
                </div>
                <div class="form-group">
                    <label for="video_url_add">URL видео (встраивание, например, с YouTube):</label>
                    <input type="url" id="video_url_add" name="video_url" placeholder="https://www.youtube.com/embed/VIDEO_ID">
                </div>
                <div class="form-group">
                    <label for="author_id_add">ID Автора (пользователя, необязательно):</label>
                    <input type="number" id="author_id_add" name="author_id" min="1">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_news" class="btn-save">Добавить новость</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <h2>Список новостей</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Превью</th>
                        <th>Автор</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($news_list_arr)): ?>
                        <?php foreach ($news_list_arr as $news_item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($news_item['id']); ?></td>
                                <td>
                                    <?php if (!empty($news_item['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($news_item['image'],'/')); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($news_item['title']); ?></td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($news_item['content_preview']); ?>">
                                    <?php echo htmlspecialchars($news_item['content_preview']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($news_item['author_login'] ?: ($news_item['author_id'] ?: 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($news_item['date']))); ?></td>
                                <td class="actions">
                                    <a href="edit_news.php?id=<?php echo htmlspecialchars($news_item['id']); ?>" class="edit-btn">Редакт.</a>
                                    <a href="manage_news.php?delete_id=<?php echo htmlspecialchars($news_item['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту новость и все связанные с ней данные (изображения, комментарии)?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Новостей пока нет.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script>
function insertBBCode(tag) {
    const textarea = document.getElementById('content_add'); 
    if (!textarea) {
        console.error('Textarea with id "content_add" not found for BBCode insertion.');
        return;
    }
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    let replacement = '';

    if (tag === 'ul' || tag === 'ol') {
        replacement = `[${tag}]\n[li]Пункт 1[/li]\n[li]Пункт 2[/li]\n[/${tag}]`;
    } else if (tag === 'li') {
        replacement = `\n[${tag}]${selectedText || 'Текст пункта'}[/${tag}]`;
    } else {
        replacement = `[${tag}]${selectedText || 'Текст'}[/${tag}]`;
    }

    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
    let cursorPosition = start + replacement.indexOf('Текст');
    if(selectedText || tag === 'ul' || tag === 'ol' || tag === 'li'){
      cursorPosition = start + replacement.length;
      if(tag === 'ul' || tag === 'ol') cursorPosition -= `[/${tag}]`.length;
      else if(tag === 'li' && !selectedText) cursorPosition -= `[/${tag}]`.length - 'Текст пункта'.length;
      else if (tag !== 'li' && !selectedText) cursorPosition -= `[/${tag}]`.length - 'Текст'.length;
    }
    if (cursorPosition < 0) cursorPosition = start + replacement.length;
    textarea.setSelectionRange(cursorPosition, cursorPosition);
}
</script>
</body>
</html>