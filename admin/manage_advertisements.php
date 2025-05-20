<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';
$upload_dir = '../uploads/advertisements/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = 'info';

$stmt_text = $connect->prepare("SELECT text FROM advertisement_text WHERE id = 1 LIMIT 1");
$current_ad_text_value = '';
if ($stmt_text) {
    $stmt_text->execute();
    $result_text = $stmt_text->get_result();
    if ($row_text = $result_text->fetch_assoc()) {
        $current_ad_text_value = $row_text['text'];
    }
    $stmt_text->close();
}

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_ad_text'])) {
        $new_text = trim($_POST['ad_text']);
        $stmt_update_text = $connect->prepare("UPDATE advertisement_text SET text = ? WHERE id = 1");
        if ($stmt_update_text) {
            $stmt_update_text->bind_param("s", $new_text);
            if ($stmt_update_text->execute()) {
                $_SESSION['admin_message'] = "Текст карточки успешно обновлен.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Ошибка обновления текста карточки: " . $stmt_update_text->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_update_text->close();
        } else {
            $_SESSION['admin_message'] = "Ошибка подготовки запроса обновления текста: " . $connect->error;
            $_SESSION['admin_message_type'] = "error";
        }
        header('Location: manage_advertisements.php');
        exit();
    } elseif (isset($_POST['add_advertisement'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $link = trim($_POST['link']);
        $is_active_add = isset($_POST['is_active_add']) ? 1 : 0;
        $errors_add = [];

        if (empty($title)) $errors_add[] = "Заголовок рекламы обязателен.";
        if (empty($description)) $errors_add[] = "Описание рекламы обязательно.";
        if (empty($link)) $errors_add[] = "Ссылка для рекламы обязательна.";

        $image_path_db = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid('ad_', true) . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($imageFileType, $allowed_types)) {
                $errors_add[] = "Недопустимый тип файла изображения.";
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $errors_add[] = "Файл изображения слишком большой (макс 3MB).";
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path_db = 'uploads/advertisements/' . $file_name;
                } else {
                    $errors_add[] = "Ошибка загрузки файла изображения.";
                }
            }
        } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
             if($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE){
                $errors_add[] = "Изображение для рекламы обязательно.";
             } else {
                $errors_add[] = "Ошибка при загрузке изображения (код: " . $_FILES['image']['error'] . ").";
             }
        }

        if (empty($errors_add)) {
            if ($is_active_add == 1) {
                $connect->query("UPDATE advertisements SET is_active = 0");
            }
            $stmt_add = $connect->prepare("INSERT INTO advertisements (title, description, image, link, is_active) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_add) {
                $stmt_add->bind_param("ssssi", $title, $description, $image_path_db, $link, $is_active_add);
                if ($stmt_add->execute()) {
                    $_SESSION['admin_message'] = "Реклама успешно добавлена.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Ошибка добавления рекламы: " . $stmt_add->error;
                    $_SESSION['admin_message_type'] = "error";
                }
                $stmt_add->close();
            } else {
                $_SESSION['admin_message'] = "Ошибка подготовки запроса добавления рекламы: " . $connect->error;
                $_SESSION['admin_message_type'] = "error";
            }
        } else {
            $_SESSION['admin_message'] = implode("<br>", $errors_add);
            $_SESSION['admin_message_type'] = "error";
            $_SESSION['form_add_advertisement_errors'] = $errors_add;
            $_SESSION['old_add_advertisement_data'] = $_POST;
        }
        header('Location: manage_advertisements.php');
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt_get_img = $connect->prepare("SELECT image FROM advertisements WHERE id = ?");
    if ($stmt_get_img) {
        $stmt_get_img->bind_param("i", $delete_id);
        $stmt_get_img->execute();
        $img_result = $stmt_get_img->get_result();
        if($img_row = $img_result->fetch_assoc()){
            if(!empty($img_row['image']) && file_exists('../' . ltrim($img_row['image'], '/'))){
                unlink('../' . ltrim($img_row['image'], '/'));
            }
        }
        $stmt_get_img->close();
    }

    $stmt_delete = $connect->prepare("DELETE FROM advertisements WHERE id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            $_SESSION['admin_message'] = "Реклама успешно удалена.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Ошибка удаления рекламы: " . $stmt_delete->error;
            $_SESSION['admin_message_type'] = "error";
        }
        $stmt_delete->close();
    } else {
        $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления рекламы: " . $connect->error;
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_advertisements.php');
    exit();
}

if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    $stmt_get_status = $connect->prepare("SELECT is_active FROM advertisements WHERE id = ?");
    if ($stmt_get_status) {
        $stmt_get_status->bind_param("i", $toggle_id);
        $stmt_get_status->execute();
        $status_result = $stmt_get_status->get_result();
        if ($status_row = $status_result->fetch_assoc()) {
            $new_status = $status_row['is_active'] ? 0 : 1;
            if ($new_status == 1) {
                $connect->query("UPDATE advertisements SET is_active = 0");
            }
            $stmt_toggle = $connect->prepare("UPDATE advertisements SET is_active = ? WHERE id = ?");
            if ($stmt_toggle) {
                $stmt_toggle->bind_param("ii", $new_status, $toggle_id);
                if ($stmt_toggle->execute()) {
                    $_SESSION['admin_message'] = "Статус рекламы изменен.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                     $_SESSION['admin_message'] = "Ошибка изменения статуса: " . $stmt_toggle->error;
                     $_SESSION['admin_message_type'] = "error";
                }
                $stmt_toggle->close();
            } else {
                 $_SESSION['admin_message'] = "Ошибка подготовки запроса изменения статуса: " . $connect->error;
                 $_SESSION['admin_message_type'] = "error";
            }
        }
        $stmt_get_status->close();
    } else {
        $_SESSION['admin_message'] = "Ошибка получения статуса рекламы: " . $connect->error;
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_advertisements.php');
    exit();
}

$stmt_ads = $connect->prepare("SELECT * FROM advertisements ORDER BY created_at DESC");
$advertisements_arr = [];
if ($stmt_ads) {
    $stmt_ads->execute();
    $result_ads = $stmt_ads->get_result();
    while ($row = $result_ads->fetch_assoc()) {
        $advertisements_arr[] = $row;
    }
    $stmt_ads->close();
} else {
    error_log("Manage Advertisements: Failed to prepare ads list query: " . $connect->error);
}

$form_add_advertisement_errors = $_SESSION['form_add_advertisement_errors'] ?? [];
$old_add_advertisement_data = $_SESSION['old_add_advertisement_data'] ?? [];
unset($_SESSION['form_add_advertisement_errors'], $_SESSION['old_add_advertisement_data']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Рекламой - CoffeeFan</title>
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
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo nl2br(htmlspecialchars($message)); ?></div>
        <?php endif; ?>

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAdTextFormBtn">
            <span class="material-icons-outlined">edit_note</span>Редактировать текст карточки
        </button>
        <div class="admin-content form-container" id="adTextFormContainer" style="display: none;">
            <h2>Редактировать текст карточки на главном баннере</h2>
            <form action="manage_advertisements.php" method="post" class="admin-form" id="adTextForm">
                <div class="form-group">
                    <label for="ad_text">Текст (например, "Новинка 2025"):</label>
                    <input type="text" id="ad_text" name="ad_text" value="<?php echo htmlspecialchars($current_ad_text_value); ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_ad_text" class="btn-save">Обновить текст</button>
                    <button type="button" class="btn-cancel" id="cancelAdTextBtn">Отмена</button>
                </div>
            </form>
        </div>

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAddAdvertisementFormBtn" style="margin-top: 20px;">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новую рекламу (баннер)
        </button>

        <div class="admin-content form-container" id="addAdvertisementFormContainer" style="display: <?php echo !empty($form_add_advertisement_errors) ? 'block' : 'none'; ?>;">
            <div id="add-form-anchor"></div>
            <h2>Добавить новую рекламу (баннер на главной)</h2>
             <?php if (!empty($form_add_advertisement_errors)): ?>
                <div class="message error">
                    <strong>Обнаружены ошибки при добавлении:</strong><br>
                    <?php foreach ($form_add_advertisement_errors as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p style="font-size:0.9em; color:#777; margin-bottom:15px;">Примечание: Только одна реклама может быть активна одновременно. Активация новой рекламы автоматически деактивирует предыдущую.</p>
            <form action="manage_advertisements.php" method="post" enctype="multipart/form-data" class="admin-form" id="addAdvertisementForm">
                <div class="form-group">
                    <label for="title_add">Заголовок:</label>
                    <input type="text" id="title_add" name="title" value="<?php echo htmlspecialchars($old_add_advertisement_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description_add">Описание:</label>
                    <textarea id="description_add" name="description" rows="4" required><?php echo htmlspecialchars($old_add_advertisement_data['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="link_add">Ссылка (URL):</label>
                    <input type="url" id="link_add" name="link" value="<?php echo htmlspecialchars($old_add_advertisement_data['link'] ?? ''); ?>" placeholder="https://example.com" required>
                </div>
                <div class="form-group">
                    <label for="image_add_form">Изображение:</label>
                    <input type="file" id="image_add_form" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active_add" value="1" <?php echo (isset($old_add_advertisement_data['is_active_add']) && $old_add_advertisement_data['is_active_add'] == 1) ? 'checked' : ''; ?>>
                        Сделать активной (заменит текущую активную рекламу)
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_advertisement" class="btn-save">Добавить рекламу</button>
                    <button type="button" class="btn-cancel" id="cancelAddAdvertisementBtn">Отмена</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список рекламных баннеров</h2>
                <div class="search-input-container">
                    <input type="text" id="advertisementsSearchInput" placeholder="Поиск по заголовку, описанию...">
                </div>
            </div>
            <table class="admin-table" id="advertisementsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                        <th>Изображение</th>
                        <th class="sortable" data-sort="title">Заголовок</th>
                        <th>Описание</th>
                        <th class="sortable" data-sort="link">Ссылка</th>
                        <th class="sortable" data-sort="is_active" data-sort-type="boolean">Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="advertisementsTableBody">
                    <?php if (!empty($advertisements_arr)): ?>
                        <?php foreach ($advertisements_arr as $ad): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ad['id']); ?></td>
                                <td>
                                    <?php if (!empty($ad['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($ad['image'],'/')); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($ad['title']); ?></td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($ad['description']); ?>">
                                    <?php echo htmlspecialchars($ad['description']); ?>
                                </td>
                                <td><a href="<?php echo htmlspecialchars($ad['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($ad['link']); ?></a></td>
                                <td>
                                    <a href="manage_advertisements.php?toggle_id=<?php echo htmlspecialchars($ad['id']); ?>" 
                                       class="status-toggle-link <?php echo $ad['is_active'] ? 'status-active' : 'status-inactive'; ?>"
                                       title="<?php echo $ad['is_active'] ? 'Нажмите, чтобы деактивировать' : 'Нажмите, чтобы активировать (деактивирует другие)'; ?>"
                                       onclick="return confirm('<?php echo $ad['is_active'] ? 'Деактивировать эту рекламу?' : 'Активировать эту рекламу? Это деактивирует любую другую активную рекламу.'; ?>');">
                                        <?php echo $ad['is_active'] ? 'Активна' : 'Неактивна'; ?>
                                    </a>
                                </td>
                                <td class="actions">
                                    <a href="manage_advertisements.php?delete_id=<?php echo htmlspecialchars($ad['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту рекламу?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noAdvertisementsRow">
                            <td colspan="7">Рекламных баннеров не найдено.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p id="advertisementsTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Рекламные баннеры по вашему запросу не найдены.</p>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const $addAdvertisementFormContainer = $('#addAdvertisementFormContainer');
    const $toggleAddAdvertisementFormBtn = $('#toggleAddAdvertisementFormBtn');
    const $cancelAddAdvertisementBtn = $('#cancelAddAdvertisementBtn');
    const $adTextFormContainer = $('#adTextFormContainer');
    const $toggleAdTextFormBtn = $('#toggleAdTextFormBtn');
    const $cancelAdTextBtn = $('#cancelAdTextBtn');

    if ($addAdvertisementFormContainer.find('.message.error').length > 0) {
        $addAdvertisementFormContainer.show();
        $toggleAddAdvertisementFormBtn.find('.material-icons-outlined').text('remove_circle_outline');
        $toggleAddAdvertisementFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Скрыть форму добавления баннера');
    }

    $toggleAddAdvertisementFormBtn.on('click', function() {
        $adTextFormContainer.slideUp(300);
        $toggleAdTextFormBtn.find('.material-icons-outlined').text('edit_note');
        $toggleAdTextFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Редактировать текст карточки');
        
        $addAdvertisementFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $addAdvertisementFormContainer.is(':visible') ? 'remove_circle_outline' : 'add_circle_outline'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $addAdvertisementFormContainer.is(':visible') ? 'Скрыть форму добавления баннера' : 'Добавить новую рекламу (баннер)'
        );
         if (!$addAdvertisementFormContainer.is(':visible')) {
            $('#addAdvertisementForm')[0].reset();
            $addAdvertisementFormContainer.find('.message.error').remove();
        }
    });
    $cancelAddAdvertisementBtn.on('click', function() {
        $addAdvertisementFormContainer.slideUp(300);
        $toggleAddAdvertisementFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddAdvertisementFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новую рекламу (баннер)');
        $('#addAdvertisementForm')[0].reset();
        $addAdvertisementFormContainer.find('.message.error').remove();
    });

    $toggleAdTextFormBtn.on('click', function() {
        $addAdvertisementFormContainer.slideUp(300);
        $toggleAddAdvertisementFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddAdvertisementFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новую рекламу (баннер)');
        
        $adTextFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $adTextFormContainer.is(':visible') ? 'remove_circle_outline' : 'edit_note'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $adTextFormContainer.is(':visible') ? 'Скрыть форму текста' : 'Редактировать текст карточки'
        );
         if (!$adTextFormContainer.is(':visible')) {
            $adTextFormContainer.find('.message.error').remove();
        }
    });
    $cancelAdTextBtn.on('click', function() {
        $adTextFormContainer.slideUp(300);
        $toggleAdTextFormBtn.find('.material-icons-outlined').text('edit_note');
        $toggleAdTextFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Редактировать текст карточки');
        $adTextFormContainer.find('.message.error').remove();
    });


    const $advertisementsTable = $('#advertisementsTable');
    const $advertisementsTableBody = $('#advertisementsTableBody');
    const $advertisementsSearchInput = $('#advertisementsSearchInput');
    const $noAdvertisementsRowPHP = $('#noAdvertisementsRow');
    const $advertisementsEmptyMessageJS = $('#advertisementsTableEmptyMessage');

    $advertisementsTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $advertisementsTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $advertisementsTableBody.find('tr:not(#noAdvertisementsRow)').get();
        rows.sort(function(a, b) {
            let valA, valB;
            if (type === 'boolean') {
                 valA = $(a).children('td').eq($th.index()).find('.status-toggle-link').text().trim().toLowerCase() === 'активна';
                 valB = $(b).children('td').eq($th.index()).find('.status-toggle-link').text().trim().toLowerCase() === 'активна';
            } else {
                valA = $(a).children('td').eq($th.index()).text().trim();
                valB = $(b).children('td').eq($th.index()).text().trim();
            }

            if (type === 'number') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else {
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $advertisementsTableBody.append(row);
        });
    });

    $advertisementsSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $advertisementsTableBody.find('tr:not(#noAdvertisementsRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                if (index !== 1 && index !== 6 && index !== 5) {
                    rowText += $(this).text().toLowerCase() + ' ';
                } else if (index === 5) { 
                    rowText += $(this).find('.status-toggle-link').text().toLowerCase() + ' ';
                }
            });
            if (rowText.includes(searchTerm)) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        if ($noAdvertisementsRowPHP.length > 0 && $advertisementsTableBody.find('tr:not(#noAdvertisementsRow):visible').length === 0 && searchTerm === "") {
            $noAdvertisementsRowPHP.show();
            $advertisementsEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $advertisementsTableBody.find('tr:not(#noAdvertisementsRow)').length > 0) {
            $noAdvertisementsRowPHP.hide();
            $advertisementsEmptyMessageJS.show();
        } else {
            $noAdvertisementsRowPHP.hide();
            $advertisementsEmptyMessageJS.hide();
        }
    });
    if ($advertisementsTableBody.find('tr:not(#noAdvertisementsRow)').length === 0 && $noAdvertisementsRowPHP.is(':visible')) {
    } else {
         $advertisementsSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>