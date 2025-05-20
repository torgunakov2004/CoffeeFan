<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$item_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$menu_item_data = null;
$errors = [];
$message = '';
$message_type = '';

$upload_dir = '../uploads/menu/';

if ($item_id_to_edit > 0) {
    $stmt_get = $connect->prepare("SELECT * FROM menu WHERE id = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $item_id_to_edit);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows === 1) {
            $menu_item_data = $result_get->fetch_assoc();
        } else {
            $_SESSION['admin_message'] = "Пункт меню с ID $item_id_to_edit не найден.";
            $_SESSION['admin_message_type'] = "error";
            header('Location: manage_menu.php');
            exit();
        }
        $stmt_get->close();
    } else {
        $errors[] = "Ошибка загрузки данных пункта меню: " . $connect->error;
        $menu_item_data = $_POST;
    }
} else {
    $_SESSION['admin_message'] = "Некорректный ID пункта меню.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_menu.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_menu_item'])) {
    $id = $item_id_to_edit;
    $title = trim($_POST['title']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $is_popular_update = isset($_POST['is_popular_update']) ? 1 : 0;
    
    $current_db_image_path = $menu_item_data['image']; 
    $new_image_path_db = $current_db_image_path; 

    if (empty($title)) $errors[] = "Название пункта меню обязательно.";
    if ($price === false || $price < 0) $errors[] = "Некорректная цена.";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid('menu_', true) . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name; 
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB
            $errors[] = "Файл слишком большой. Максимум 2MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $new_image_path_db = 'uploads/menu/' . $file_name; 
                if (!empty($current_db_image_path) && file_exists('../' . ltrim($current_db_image_path, '/'))) {
                    unlink('../' . ltrim($current_db_image_path, '/'));
                }
            } else {
                $errors[] = "Ошибка загрузки нового файла изображения.";
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Ошибка при загрузке изображения (код: " . $_FILES['image']['error'] . ").";
    }

    if (empty($errors)) {
        $stmt_update = $connect->prepare("UPDATE `menu` SET title = ?, price = ?, image = ?, is_popular = ? WHERE id = ?");
if ($stmt_update) {
    $stmt_update->bind_param("sdsii", $title, $price, $new_image_path_db, $is_popular_update, $id);
            if ($stmt_update->execute()) {
                $_SESSION['admin_message'] = "Пункт меню успешно обновлен.";
                $_SESSION['admin_message_type'] = "success";
                header('Location: manage_menu.php');
                exit();
            } else {
                $errors[] = "Ошибка обновления пункта меню: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Ошибка подготовки запроса обновления: " . $connect->error;
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = "error";
        $menu_item_data = $_POST;
        $menu_item_data['image'] = $current_db_image_path;
        $menu_item_data['id'] = $id;
    }
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пункт меню - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Редактирование Пункта Меню</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_menu.php" class="active">Меню</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2><?php echo htmlspecialchars($menu_item_data['title'] ?? 'Редактирование'); ?></h2>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="edit_menu_item.php?id=<?php echo $item_id_to_edit; ?>" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($menu_item_data['id']); ?>">
                
                <div class="form-group">
                    <label for="title_edit">Название:</label>
                    <input type="text" id="title_edit" name="title" value="<?php echo htmlspecialchars($menu_item_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price_edit">Цена (₽):</label>
                    <input type="number" id="price_edit" name="price" value="<?php echo htmlspecialchars($menu_item_data['price'] ?? ''); ?>" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image_edit">Изображение (оставьте пустым, чтобы не менять):</label>
                    <?php if (!empty($menu_item_data['image'])): ?>
                        <div class="current-image-container">
                            <img src="../<?php echo htmlspecialchars(ltrim($menu_item_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_edit" name="image" accept="image/*">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular_update" value="1" <?php echo (isset($menu_item_data['is_popular']) && $menu_item_data['is_popular'] == 1) ? 'checked' : ''; ?>>
                        Популярный пункт
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_menu_item" class="btn-save">Сохранить изменения</button>
                    <a href="manage_menu.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>