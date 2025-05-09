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

function sanitize_filename_news_edit($filename) {
    $filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    $filename = strtolower($filename);
    return $filename;
}

$news_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$news_item_data = null;
$gallery_images_data = [];
$errors = [];
$message = '';
$message_type = '';


if ($news_id_to_edit === 0) {
    $_SESSION['admin_message'] = "ID новости не указан для редактирования.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_news.php');
    exit();
}

$stmt_get_news = $connect->prepare("SELECT * FROM `news` WHERE `id` = ?");
if ($stmt_get_news) {
    $stmt_get_news->bind_param("i", $news_id_to_edit);
    $stmt_get_news->execute();
    $result_get_news = $stmt_get_news->get_result();
    if ($result_get_news->num_rows === 1) {
        $news_item_data = $result_get_news->fetch_assoc();
    } else {
        $_SESSION['admin_message'] = "Новость с ID $news_id_to_edit не найдена.";
        $_SESSION['admin_message_type'] = "error";
        header('Location: manage_news.php');
        exit();
    }
    $stmt_get_news->close();
} else {
    $errors[] = "Ошибка загрузки данных новости: " . $connect->error;
    $news_item_data = $_POST; 
}

$stmt_get_gallery = $connect->prepare("SELECT id, image_path FROM `news_images` WHERE `news_id` = ? ORDER BY id ASC");
if ($stmt_get_gallery) {
    $stmt_get_gallery->bind_param("i", $news_id_to_edit);
    $stmt_get_gallery->execute();
    $result_gallery = $stmt_get_gallery->get_result();
    while($row_gallery = $result_gallery->fetch_assoc()){
        $gallery_images_data[] = $row_gallery;
    }
    $stmt_get_gallery->close();
} else {
    $errors[] = "Ошибка загрузки изображений галереи: " . $connect->error;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_news'])) {
    $id_form = (int)$_POST['id'];
    if ($id_form !== $news_id_to_edit) {
         $errors[] = "Ошибка ID новости при отправке формы.";
    }

    $title = trim($_POST['title']);
    $content_preview = trim($_POST['content_preview']); // Получаем превью из формы
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']) ?: NULL;
    $author_id = !empty(trim($_POST['author_id'])) ? (int)trim($_POST['author_id']) : NULL;
    
    if (empty($title)) $errors[] = "Заголовок обязателен.";
    if (empty($content_preview)) $errors[] = "Краткое описание (превью) обязательно.";
    if (empty($content)) $errors[] = "Полное содержимое новости обязательно.";

    $current_db_main_image = $news_item_data['image'];
    $new_main_image_path_db = $current_db_main_image;
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_name_main_edit = uniqid('news_main_edit_', true) . '_' . sanitize_filename_news_edit(basename($_FILES['image']['name']));
        $target_file_main_edit = $upload_dir_main . $file_name_main_edit;
        $imageFileType_main_edit = strtolower(pathinfo($target_file_main_edit, PATHINFO_EXTENSION));

        if (!in_array($imageFileType_main_edit, $allowed_types)) {
            $errors[] = "Основное изображение: недопустимый тип файла.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { 
            $errors[] = "Основное изображение: файл слишком большой.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_main_edit)) {
                $new_main_image_path_db = 'uploads/news_main/' . $file_name_main_edit;
                if (!empty($current_db_main_image) && file_exists('../' . ltrim($current_db_main_image, '/'))) {
                    unlink('../' . ltrim($current_db_main_image, '/'));
                }
            } else {
                $errors[] = "Ошибка загрузки нового основного изображения.";
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
         $errors[] = "Ошибка при загрузке основного изображения (код: " . $_FILES['image']['error'] . ").";
    }

    if (empty($errors)) {
        // Обновляем content_preview из формы, а не генерируем его
        $stmt_update_news = $connect->prepare("UPDATE `news` SET `title` = ?, `content_preview` = ?, `content` = ?, `image` = ?, `author_id` = ?, `video_url` = ? WHERE `id` = ?");
        if ($stmt_update_news) {
            $stmt_update_news->bind_param("ssssisi", $title, $content_preview, $content, $new_main_image_path_db, $author_id, $video_url, $id_form);
            if (!$stmt_update_news->execute()) {
                $errors[] = "Ошибка обновления новости: " . $stmt_update_news->error;
            }
            $stmt_update_news->close();
        } else {
            $errors[] = "Ошибка подготовки запроса обновления новости: " . $connect->error;
        }
    }

    if (empty($errors) && isset($_POST['delete_gallery_image']) && is_array($_POST['delete_gallery_image'])) {
        $delete_gallery_stmt = $connect->prepare("DELETE FROM `news_images` WHERE `id` = ? AND `news_id` = ?");
        $select_path_stmt = $connect->prepare("SELECT image_path FROM `news_images` WHERE `id` = ?");

        if ($delete_gallery_stmt && $select_path_stmt) {
            foreach ($_POST['delete_gallery_image'] as $gallery_image_id_to_delete_str) {
                $gallery_image_id_to_delete = (int)$gallery_image_id_to_delete_str;
                
                $select_path_stmt->bind_param("i", $gallery_image_id_to_delete);
                $select_path_stmt->execute();
                $path_result = $select_path_stmt->get_result();
                if ($path_row = $path_result->fetch_assoc()) {
                    if (!empty($path_row['image_path']) && file_exists('../' . ltrim($path_row['image_path'], '/'))) {
                        unlink('../' . ltrim($path_row['image_path'], '/'));
                    }
                }
                
                $delete_gallery_stmt->bind_param("ii", $gallery_image_id_to_delete, $id_form);
                if(!$delete_gallery_stmt->execute()){
                    $errors[] = "Ошибка удаления изображения галереи ID $gallery_image_id_to_delete: " . $delete_gallery_stmt->error;
                }
            }
            $delete_gallery_stmt->close();
            $select_path_stmt->close();
        } else {
            $errors[] = "Ошибка подготовки запросов для удаления изображений галереи.";
        }
    }

    if (empty($errors) && isset($_FILES['new_gallery_images'])) {
        $new_gallery_stmt = $connect->prepare("INSERT INTO `news_images` (`news_id`, `image_path`) VALUES (?, ?)");
        if ($new_gallery_stmt) {
            foreach ($_FILES['new_gallery_images']['name'] as $key => $name) {
                if ($_FILES['new_gallery_images']['error'][$key] == UPLOAD_ERR_OK) {
                    $gallery_image_name_edit = uniqid('news_gallery_edit_', true) . '_' . sanitize_filename_news_edit(basename($name));
                    $gallery_image_target_edit = $upload_dir_gallery . $gallery_image_name_edit;
                    $imageFileType_gallery_edit = strtolower(pathinfo($gallery_image_target_edit, PATHINFO_EXTENSION));

                    if (!in_array($imageFileType_gallery_edit, $allowed_types)) {
                        $errors[] = "Галерея: Файл '$name' имеет недопустимый тип."; continue;
                    }
                    if ($_FILES['new_gallery_images']['size'][$key] > 3 * 1024 * 1024) { 
                        $errors[] = "Галерея: Файл '$name' слишком большой."; continue;
                    }

                    if (move_uploaded_file($_FILES['new_gallery_images']['tmp_name'][$key], $gallery_image_target_edit)) {
                        $new_gallery_image_path_db = 'uploads/news_gallery/' . $gallery_image_name_edit;
                        $new_gallery_stmt->bind_param("is", $id_form, $new_gallery_image_path_db);
                        if(!$new_gallery_stmt->execute()){
                            $errors[] = "Ошибка добавления нового изображения галереи '$name' в БД: " . $new_gallery_stmt->error;
                        }
                    } else {
                         $errors[] = "Ошибка загрузки нового файла галереи '$name'.";
                    }
                } elseif ($_FILES['new_gallery_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                     $errors[] = "Ошибка при загрузке файла галереи '$name' (код: " . $_FILES['new_gallery_images']['error'][$key] . ").";
                }
            }
            $new_gallery_stmt->close();
        } else {
            $errors[] = "Ошибка подготовки запроса для новых галерейных изображений: " . $connect->error;
        }
    }
    
    if (empty($errors)) {
        $_SESSION['admin_message'] = "Новость успешно обновлена.";
        $_SESSION['admin_message_type'] = "success";
        header('Location: manage_news.php');
        exit();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
        $news_item_data_form = $_POST; 
        $news_item_data_form['image'] = $current_db_main_image; 
        $news_item_data_form['id'] = $id_form; 
        $news_item_data = array_merge($news_item_data, $news_item_data_form); 
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать Новость - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Редактирование Новости</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_news.php" class="active">Новости</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2><?php echo htmlspecialchars($news_item_data['title'] ?? 'Редактирование'); ?></h2>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo nl2br($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors) && empty($message)): ?>
                 <div class="message error"><?php echo implode("<br>", $errors); ?></div>
            <?php endif; ?>


            <form action="edit_news.php?id=<?php echo $news_id_to_edit; ?>" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($news_item_data['id']); ?>">
                <div class="form-group">
                    <label for="title_edit">Название:</label>
                    <input type="text" id="title_edit" name="title" value="<?php echo htmlspecialchars($news_item_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="content_preview_edit">Краткое описание (превью):</label>
                    <textarea id="content_preview_edit" name="content_preview" rows="3" required maxlength="255"><?php echo htmlspecialchars($news_item_data['content_preview'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content_edit">Полное содержимое новости (BB-коды):</label>
                    <div class="bbcode-toolbar">
                        <button type="button" onclick="insertBBCodeEdit('b')"><b>B</b></button>
                        <button type="button" onclick="insertBBCodeEdit('h2')">H2</button>
                        <button type="button" onclick="insertBBCodeEdit('h3')">H3</button>
                        <button type="button" onclick="insertBBCodeEdit('quote')">Цитата</button>
                        <button type="button" onclick="insertBBCodeEdit('ul')">UL</button>
                        <button type="button" onclick="insertBBCodeEdit('ol')">OL</button>
                        <button type="button" onclick="insertBBCodeEdit('li')">LI</button>
                    </div>
                    <textarea id="content_edit" name="content" rows="10" required><?php echo htmlspecialchars($news_item_data['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_edit">Основное изображение (оставьте пустым, если не хотите менять):</label>
                    <?php if (!empty($news_item_data['image'])): ?>
                        <div class="current-image-container">
                            <img src="../<?php echo htmlspecialchars(ltrim($news_item_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_edit" name="image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <h3>Галерея изображений</h3>
                    <?php if (!empty($gallery_images_data)): ?>
                        <div class="gallery-edit">
                            <?php foreach ($gallery_images_data as $img): ?>
                                <div class="gallery-item-edit">
                                    <img src="../<?php echo htmlspecialchars(ltrim($img['image_path'],'/')); ?>" alt="Gallery image">
                                    <label>
                                        <input type="checkbox" name="delete_gallery_image[]" value="<?php echo $img['id']; ?>"> Удалить
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Нет дополнительных изображений в галерее.</p>
                    <?php endif; ?>
                    <label for="new_gallery_images_edit">Добавить новые изображения в галерею:</label>
                    <input type="file" id="new_gallery_images_edit" name="new_gallery_images[]" accept="image/*" multiple>
                </div>

                <div class="form-group">
                    <label for="video_url_edit">URL видео:</label>
                    <input type="url" id="video_url_edit" name="video_url" value="<?php echo htmlspecialchars($news_item_data['video_url'] ?? ''); ?>" placeholder="https://www.youtube.com/embed/VIDEO_ID">
                </div>
                <div class="form-group">
                    <label for="author_id_edit">ID Автора:</label>
                    <input type="number" id="author_id_edit" name="author_id" value="<?php echo htmlspecialchars($news_item_data['author_id'] ?? ''); ?>" min="1">
                </div>
                <div class="form-actions">
                    <button type="submit" name="edit_news" class="btn-save">Сохранить изменения</button>
                    <a href="manage_news.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
<script>
function insertBBCodeEdit(tag) {
    const textarea = document.getElementById('content_edit'); 
    if (!textarea) {
        console.error('Textarea with id "content_edit" not found for BBCode insertion.');
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