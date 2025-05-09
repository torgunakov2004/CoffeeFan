<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$product_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$product_data = null;
$errors = [];
$message = '';
$message_type = '';

$upload_dir = '../uploads/products/'; // Та же директория, что и в manage_products.php

if ($product_id_to_edit > 0) {
    $stmt_get = $connect->prepare("SELECT * FROM products WHERE id = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $product_id_to_edit);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows === 1) {
            $product_data = $result_get->fetch_assoc();
        } else {
            $_SESSION['admin_message'] = "Продукт с ID $product_id_to_edit не найден.";
            $_SESSION['admin_message_type'] = "error";
            header('Location: manage_products.php');
            exit();
        }
        $stmt_get->close();
    } else {
        $errors[] = "Ошибка загрузки данных продукта: " . $connect->error;
        $product_data = $_POST; // Сохраняем введенные данные если была ошибка загрузки
    }
} else {
    $_SESSION['admin_message'] = "Некорректный ID продукта.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_products.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = $product_id_to_edit; // ID уже есть
    $title = trim($_POST['title']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $composition = trim($_POST['composition'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $country_of_origin = trim($_POST['country_of_origin'] ?? '');
    $weight_grams = filter_input(INPUT_POST, 'weight_grams', FILTER_VALIDATE_INT);
    $roast_level = trim($_POST['roast_level'] ?? '');
    $processing_method = trim($_POST['processing_method'] ?? '');
    $is_popular_update = isset($_POST['is_popular_update']) ? 1 : 0;
    
    $current_db_image_path = $product_data['image']; // Путь как он в БД (относительно корня сайта)
    $new_image_path_db = $current_db_image_path; // По умолчанию оставляем старую картинку

    if (empty($title)) $errors[] = "Название продукта обязательно.";
    if ($price === false || $price <= 0) $errors[] = "Некорректная цена.";
    if ($weight_grams !== null && $weight_grams < 0) $errors[] = "Некорректный вес."; // Может быть 0, если поле не обязательно

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid('product_', true) . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name; // Физический путь для сохранения
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB
            $errors[] = "Файл слишком большой. Максимум 5MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $new_image_path_db = 'uploads/products/' . $file_name; // Путь от корня сайта для БД

                // Удаляем старый файл, если он был и не дефолтный, и новый файл успешно загружен
                if (!empty($current_db_image_path) && 
                    strpos($current_db_image_path, 'default-product.png') === false && // Пример дефолтного
                    file_exists('../' . ltrim($current_db_image_path, '/'))) {
                    unlink('../' . ltrim($current_db_image_path, '/'));
                }
            } else {
                $errors[] = "Ошибка загрузки нового файла изображения.";
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Если файл был выбран, но произошла ошибка при загрузке (не UPLOAD_ERR_NO_FILE)
        $errors[] = "Ошибка при загрузке изображения (код: " . $_FILES['image']['error'] . ").";
    }

    if (empty($errors)) {
        $stmt_update = $connect->prepare("UPDATE `products` SET title = ?, price = ?, image = ?, composition = ?, features = ?, country_of_origin = ?, weight_grams = ?, roast_level = ?, processing_method = ?, is_popular = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("sdssssissii", $title, $price, $new_image_path_db, $composition, $features, $country_of_origin, $weight_grams, $roast_level, $processing_method, $is_popular_update, $id);
            if ($stmt_update->execute()) {
                $_SESSION['admin_message'] = "Продукт успешно обновлен.";
                $_SESSION['admin_message_type'] = "success";
                header('Location: manage_products.php');
                exit();
            } else {
                $errors[] = "Ошибка обновления продукта: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Ошибка подготовки запроса обновления: " . $connect->error;
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = "error";
        // Перезаполняем $product_data из POST, чтобы форма отображала введенные значения при ошибке
        $product_data = $_POST;
        $product_data['image'] = $current_db_image_path; // Оставляем текущую картинку при ошибке, если новая не загрузилась
        $product_data['id'] = $id; // id не приходит из POST в этом случае
    }
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать продукт - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Редактирование Продукта</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_products.php" class="active">Продукты</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2><?php echo htmlspecialchars($product_data['title'] ?? 'Редактирование продукта'); ?></h2>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="edit_product.php?id=<?php echo $product_id_to_edit; ?>" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_data['id']); ?>">
                
                <div class="form-group">
                    <label for="title">Название:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($product_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Цена (₽):</label>
                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product_data['price'] ?? ''); ?>" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image">Изображение (оставьте пустым, чтобы не менять):</label>
                    <?php if (!empty($product_data['image'])): ?>
                        <div class="current-image-container">
                            <img src="../<?php echo htmlspecialchars(ltrim($product_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                 <div class="form-group">
                    <label for="composition">Состав:</label>
                    <textarea id="composition" name="composition" rows="3"><?php echo htmlspecialchars($product_data['composition'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="features">Особенности:</label>
                    <textarea id="features" name="features" rows="3"><?php echo htmlspecialchars($product_data['features'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="country_of_origin">Страна происхождения:</label>
                    <input type="text" id="country_of_origin" name="country_of_origin" value="<?php echo htmlspecialchars($product_data['country_of_origin'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="weight_grams">Вес (в граммах):</label>
                    <input type="number" id="weight_grams" name="weight_grams" value="<?php echo htmlspecialchars($product_data['weight_grams'] ?? ''); ?>" min="0">
                </div>
                <div class="form-group">
                    <label for="roast_level">Степень обжарки:</label>
                    <input type="text" id="roast_level" name="roast_level" value="<?php echo htmlspecialchars($product_data['roast_level'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="processing_method">Метод обработки:</label>
                    <input type="text" id="processing_method" name="processing_method" value="<?php echo htmlspecialchars($product_data['processing_method'] ?? ''); ?>">
                </div>
                 <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular_update" value="1" <?php echo (isset($product_data['is_popular']) && $product_data['is_popular'] == 1) ? 'checked' : ''; ?>>
                        Популярный товар
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_product" class="btn-save">Сохранить изменения</button>
                    <a href="manage_products.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>