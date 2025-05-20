<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';
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
$message_type = 'info';

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content_preview = trim($_POST['content_preview']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']) ?: NULL;
    $date = date('Y-m-d H:i:s');
    $errors_add = [];

    if (empty($title)) $errors_add[] = "Заголовок обязателен.";
    if (empty($content_preview)) $errors_add[] = "Краткое описание (превью) обязательно.";
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
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        if($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE){
            $errors_add[] = "Основное изображение обязательно для новости.";
         } else {
            $errors_add[] = "Ошибка при загрузке основного изображения (код: " . $_FILES['image']['error'] . ").";
         }
    }

    if (empty($errors_add)) {
        $stmt = $connect->prepare("INSERT INTO `news` (`title`, `content_preview`, `content`, `image`, `date`, `video_url`) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $title, $content_preview, $content, $main_image_path_db, $date, $video_url);
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
                                $allowed_types_gallery = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

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
                            } elseif ($_FILES['gallery_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                                $_SESSION['admin_message'] .= "<br>Ошибка при загрузке файла галереи '$name' (код: " . $_FILES['gallery_images']['error'][$key] . ").";
                                $_SESSION['admin_message_type'] = "error";
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
        $_SESSION['form_add_news_errors'] = $errors_add;
        $_SESSION['old_add_news_data'] = $_POST;
    }
    header('Location: manage_news.php');
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $connect->begin_transaction();
    try {
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
        $stmt_del_news->bind_param("i", $delete_id);
        if ($stmt_del_news->execute()) {
            $_SESSION['admin_message'] = "Новость и все связанные с ней данные успешно удалены.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            throw new Exception("Ошибка удаления новости: " . $stmt_del_news->error);
        }
        $stmt_del_news->close();
        $connect->commit();
    } catch (Exception $e) {
        $connect->rollback();
        $_SESSION['admin_message'] = "Произошла ошибка при удалении: " . $e->getMessage();
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_news.php');
    exit();
}

$stmt_news_list = $connect->prepare("SELECT id, title, content_preview, image, date FROM `news` ORDER BY date DESC");
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

$form_add_news_errors = $_SESSION['form_add_news_errors'] ?? [];
$old_add_news_data = $_SESSION['old_add_news_data'] ?? [];
unset($_SESSION['form_add_news_errors'], $_SESSION['old_add_news_data']);
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
            <img class="header__logo" src="<?php echo $base_url_prefix_for_links; ?>../img/logo.svg" alt="CoffeeFan Logo">
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="manage_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">Заказы</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAddNewsFormBtn">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новую новость
        </button>

        <div class="admin-content form-container" id="addNewsFormContainer" style="display: <?php echo !empty($form_add_news_errors) ? 'block' : 'none'; ?>;">
            <div id="add-form-anchor"></div>
            <h2>Добавить новую новость</h2>
            <?php if (!empty($form_add_news_errors)): ?>
                <div class="message error">
                    <strong>Обнаружены ошибки при добавлении:</strong><br>
                    <?php foreach ($form_add_news_errors as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="manage_news.php" method="post" enctype="multipart/form-data" class="admin-form" id="addNewsForm">
                <div class="form-group">
                    <label for="title_add">Название:</label>
                    <input type="text" id="title_add" name="title" value="<?php echo htmlspecialchars($old_add_news_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="content_preview_add">Краткое описание (превью, будет видно в списке новостей):</label>
                    <textarea id="content_preview_add" name="content_preview" rows="3" required maxlength="255"><?php echo htmlspecialchars($old_add_news_data['content_preview'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content_add">Полное содержимое новости (поддерживаются BB-коды):</label>
                    <div class="bbcode-toolbar">
                        <button type="button" onclick="insertBBCode('b', 'content_add')"><b>B</b></button>
                        <button type="button" onclick="insertBBCode('h2', 'content_add')">H2</button>
                        <button type="button" onclick="insertBBCode('h3', 'content_add')">H3</button>
                        <button type="button" onclick="insertBBCode('quote', 'content_add')">Цитата</button>
                        <button type="button" onclick="insertBBCode('ul', 'content_add')">UL</button>
                        <button type="button" onclick="insertBBCode('ol', 'content_add')">OL</button>
                        <button type="button" onclick="insertBBCode('li', 'content_add')">LI</button>
                    </div>
                    <textarea id="content_add" name="content" rows="10" required><?php echo htmlspecialchars($old_add_news_data['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_add_form">Основное изображение:</label>
                    <input type="file" id="image_add_form" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="gallery_images_add">Дополнительные изображения (галерея):</label>
                    <input type="file" id="gallery_images_add" name="gallery_images[]" accept="image/*" multiple>
                </div>
                <div class="form-group">
                    <label for="video_url_add">URL видео (встраивание, например, с YouTube):</label>
                    <input type="url" id="video_url_add" name="video_url" value="<?php echo htmlspecialchars($old_add_news_data['video_url'] ?? ''); ?>" placeholder="https://www.youtube.com/embed/VIDEO_ID">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_news" class="btn-save">Добавить новость</button>
                    <button type="button" class="btn-cancel" id="cancelAddNewsBtn">Отмена</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список новостей</h2>
                <div class="search-input-container">
                    <input type="text" id="newsSearchInput" placeholder="Поиск по названию, превью...">
                </div>
            </div>
            <table class="admin-table" id="newsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                        <th>Изображение</th>
                        <th class="sortable" data-sort="title">Название</th>
                        <th class="sortable" data-sort="content_preview">Превью</th>
                        <th class="sortable" data-sort="date" data-sort-type="date">Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="newsTableBody">
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
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($news_item['date']))); ?></td>
                                <td class="actions">
                                    <a href="edit_news.php?id=<?php echo htmlspecialchars($news_item['id']); ?>" class="edit-btn">Редакт.</a>
                                    <a href="manage_news.php?delete_id=<?php echo htmlspecialchars($news_item['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту новость и все связанные с ней данные (изображения, комментарии)?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noNewsRow">
                            <td colspan="6">Новостей пока нет.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p id="newsTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Новости по вашему запросу не найдены.</p>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function insertBBCode(tag, textareaId) {
    const textarea = document.getElementById(textareaId); 
    if (!textarea) {
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
    let cursorPosition = start + replacement.length;
    if (!selectedText && tag !== 'ul' && tag !== 'ol' && tag !== 'li') {
        cursorPosition = start + replacement.indexOf('[/');
    } else if (!selectedText && tag === 'li') {
         cursorPosition = start + replacement.indexOf('Текст пункта') + 'Текст пункта'.length;
    } else if ((tag === 'ul' || tag === 'ol') && !selectedText) {
        cursorPosition = start + replacement.indexOf('[li]') + '[li]'.length;
    }
    textarea.setSelectionRange(cursorPosition, cursorPosition);
}

$(document).ready(function() {
    const $addNewsFormContainer = $('#addNewsFormContainer');
    const $toggleAddNewsFormBtn = $('#toggleAddNewsFormBtn');
    const $cancelAddNewsBtn = $('#cancelAddNewsBtn');

    if ($('.message.error').length > 0 && $addNewsFormContainer.find('.message.error').length > 0) {
        $addNewsFormContainer.show();
        $toggleAddNewsFormBtn.find('.material-icons-outlined').text('remove_circle_outline');
        $toggleAddNewsFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Скрыть форму');
    }


    $toggleAddNewsFormBtn.on('click', function() {
        $addNewsFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $addNewsFormContainer.is(':visible') ? 'remove_circle_outline' : 'add_circle_outline'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $addNewsFormContainer.is(':visible') ? 'Скрыть форму' : 'Добавить новую новость'
        );
        if (!$addNewsFormContainer.is(':visible')) {
            $('#addNewsForm')[0].reset();
            $addNewsFormContainer.find('.message.error').remove();
        }
    });
    $cancelAddNewsBtn.on('click', function() {
        $addNewsFormContainer.slideUp(300);
        $toggleAddNewsFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddNewsFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новую новость');
        $('#addNewsForm')[0].reset();
        $addNewsFormContainer.find('.message.error').remove();
    });

    const $newsTable = $('#newsTable');
    const $newsTableBody = $('#newsTableBody');
    const $newsSearchInput = $('#newsSearchInput');
    const $noNewsRowPHP = $('#noNewsRow');
    const $newsEmptyMessageJS = $('#newsTableEmptyMessage');

    $newsTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $newsTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $newsTableBody.find('tr:not(#noNewsRow)').get();
        rows.sort(function(a, b) {
            let valA = $(a).children('td').eq($th.index()).text().trim();
            let valB = $(b).children('td').eq($th.index()).text().trim();
            if (type === 'number') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else if (type === 'date') {
                valA = new Date(valA.split('.').reverse().join('-') + ' ' + ($(a).children('td').eq($th.index()).text().split(' ')[1] || '00:00')).getTime() || 0;
                valB = new Date(valB.split('.').reverse().join('-') + ' ' + ($(b).children('td').eq($th.index()).text().split(' ')[1] || '00:00')).getTime() || 0;
            }
            else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $newsTableBody.append(row);
        });
    });

    $newsSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $newsTableBody.find('tr:not(#noNewsRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                 if (index !== 1 && index !== 5) {
                    rowText += $(this).text().toLowerCase() + ' ';
                }
            });
            if (rowText.includes(searchTerm)) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        if ($noNewsRowPHP.length > 0 && $newsTableBody.find('tr:not(#noNewsRow):visible').length === 0 && searchTerm === "") {
            $noNewsRowPHP.show();
            $newsEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $newsTableBody.find('tr:not(#noNewsRow)').length > 0) {
            $noNewsRowPHP.hide();
            $newsEmptyMessageJS.show();
        } else {
            $noNewsRowPHP.hide();
            $newsEmptyMessageJS.hide();
        }
    });
    if ($newsTableBody.find('tr:not(#noNewsRow)').length === 0 && $noNewsRowPHP.is(':visible')) {
    } else {
         $newsSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>