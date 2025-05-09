<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$upload_dir = '../uploads/menu/'; 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu_item'])) {
    $title = trim($_POST['title']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $is_popular = isset($_POST['is_popular_add']) ? 1 : 0;
    $errors_add = [];

    if (empty($title)) $errors_add[] = "Название пункта меню обязательно.";
    if ($price === false || $price < 0) $errors_add[] = "Некорректная цена."; // 0 может быть валидной ценой

    $image_path_db = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid('menu_', true) . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors_add[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB
            $errors_add[] = "Файл слишком большой. Максимум 2MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path_db = 'uploads/menu/' . $file_name; 
            } else {
                $errors_add[] = "Ошибка загрузки файла изображения.";
            }
        }
    } else {
        $errors_add[] = "Изображение обязательно для нового пункта меню.";
    }

    if (empty($errors_add)) {
        $stmt_add = $connect->prepare("INSERT INTO `menu` (title, price, image, is_popular) VALUES (?, ?, ?, ?)");
        if ($stmt_add) {
            $stmt_add->bind_param("sdsi", $title, $price, $image_path_db, $is_popular);
            if ($stmt_add->execute()) {
                $_SESSION['admin_message'] = "Пункт меню успешно добавлен.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Ошибка добавления пункта меню: " . $stmt_add->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_add->close();
        } else {
             $_SESSION['admin_message'] = "Ошибка подготовки запроса добавления: " . $connect->error;
             $_SESSION['admin_message_type'] = "error";
        }
    } else {
        $_SESSION['admin_message'] = implode("<br>", $errors_add);
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_menu.php');
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $stmt_get_img = $connect->prepare("SELECT image FROM menu WHERE id = ?");
    $stmt_get_img->bind_param("i", $delete_id);
    $stmt_get_img->execute();
    $img_result = $stmt_get_img->get_result();
    if($img_row = $img_result->fetch_assoc()){
        if(!empty($img_row['image']) && file_exists('../' . ltrim($img_row['image'], '/'))){
            unlink('../' . ltrim($img_row['image'], '/'));
        }
    }
    $stmt_get_img->close();

    $stmt_delete = $connect->prepare("DELETE FROM `menu` WHERE `id` = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            $_SESSION['admin_message'] = "Пункт меню успешно удален.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Ошибка удаления пункта меню: " . $stmt_delete->error;
            $_SESSION['admin_message_type'] = "error";
        }
        $stmt_delete->close();
    } else {
        $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления: " . $connect->error;
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_menu.php');
    exit();
}

$stmt_menu_items = $connect->prepare("SELECT * FROM `menu` ORDER BY id DESC");
$menu_items_arr = [];
if ($stmt_menu_items) {
    $stmt_menu_items->execute();
    $result_menu_items = $stmt_menu_items->get_result();
    while ($row = $result_menu_items->fetch_assoc()) {
        $menu_items_arr[] = $row;
    }
    $stmt_menu_items->close();
} else {
    error_log("Manage Menu: Failed to prepare menu list query: " . $connect->error);
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
    <title>Управление Меню - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Управление Меню</h1>
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
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="admin-content">
            <h2>Добавить новый пункт меню</h2>
            <form action="manage_menu.php" method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="title">Название:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="price">Цена (₽):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image_add">Изображение:</label>
                    <input type="file" id="image_add" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular_add" value="1">
                        Популярный пункт
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_menu_item" class="btn-save">Добавить пункт</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <h2>Список пунктов меню</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Популярный</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($menu_items_arr)): ?>
                        <?php foreach ($menu_items_arr as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['id']); ?></td>
                                <td>
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($item['image'],'/')); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($item['price'], 2, '.', ' ')); ?> ₽</td>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="is-popular-menu-checkbox" data-id="<?php echo $item['id']; ?>" <?php echo $item['is_popular'] ? 'checked' : ''; ?>>
                                </td>
                                <td class="actions">
                                    <a href="edit_menu_item.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="edit-btn">Редакт.</a>
                                    <a href="manage_menu.php?delete_id=<?php echo htmlspecialchars($item['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить этот пункт меню?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Пункты меню не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.is-popular-menu-checkbox').change(function() {
        const itemId = $(this).data('id');
        const isPopular = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: 'toggle_popular_menu.php', 
            type: 'POST',
            data: {
                id: itemId,
                is_popular: isPopular
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('Статус популярности обновлен для пункта меню ID ' + itemId);
                } else {
                    console.error('Ошибка обновления: ' + response.message);
                    alert('Ошибка обновления статуса популярности: ' + response.message);
                    $(this).prop('checked', !isPopular); // Возвращаем чекбокс в исходное состояние
                }
            }.bind(this), // Привязываем this для доступа к чекбоксу в коллбэке
            error: function(xhr, status, error) {
                console.error('AJAX ошибка: ' + error);
                alert('Произошла ошибка при связи с сервером.');
                $(this).prop('checked', !isPopular); // Возвращаем чекбокс
            }.bind(this)
        });
    });
});
</script>
</body>
</html>