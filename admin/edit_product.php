<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$all_countries = [];
$countries_q = mysqli_query($connect, "SELECT DISTINCT country_of_origin FROM products WHERE country_of_origin IS NOT NULL AND country_of_origin != '' ORDER BY country_of_origin ASC");
if ($countries_q) {
    while ($row = mysqli_fetch_assoc($countries_q)) {
        $all_countries[] = $row['country_of_origin'];
    }
}

$all_roast_levels = [];
$roast_q = mysqli_query($connect, "SELECT DISTINCT roast_level FROM products WHERE roast_level IS NOT NULL AND roast_level != '' ORDER BY roast_level ASC");
if ($roast_q) {
    while ($row = mysqli_fetch_assoc($roast_q)) {
        $all_roast_levels[] = $row['roast_level'];
    }
}

$all_processing_methods = [];
$check_processing_column_q = mysqli_query($connect, "SHOW COLUMNS FROM `products` LIKE 'processing_method'");
$processing_column_exists = ($check_processing_column_q && $check_processing_column_q->num_rows == 1);
if ($processing_column_exists) {
    $processing_q = mysqli_query($connect, "SELECT DISTINCT processing_method FROM products WHERE processing_method IS NOT NULL AND processing_method != '' ORDER BY processing_method ASC");
    if ($processing_q) {
        while ($row = mysqli_fetch_assoc($processing_q)) {
            $all_processing_methods[] = $row['processing_method'];
        }
    }
}

$product_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$product_data = null;
$errors = [];
$message = '';
$message_type = 'info';
$base_url_prefix_for_links = '';
$upload_dir = '../uploads/products/';

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
    }
} else {
    $_SESSION['admin_message'] = "Некорректный ID продукта.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_products.php');
    exit();
}

if (empty($errors) && $product_data === null) {
    $errors[] = "Не удалось загрузить данные продукта для редактирования.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = $product_id_to_edit;
    $title = trim($_POST['title']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $composition = trim($_POST['composition'] ?? '');
    $features = trim($_POST['features'] ?? '');
    
    $country_of_origin = trim($_POST['country_of_origin_new'] ?? '');
    if (empty($country_of_origin) && !empty($_POST['country_of_origin'])) {
        $country_of_origin = trim($_POST['country_of_origin']);
    }

    $weight_grams = filter_input(INPUT_POST, 'weight_grams', FILTER_VALIDATE_INT);
    $stock_quantity_update = filter_input(INPUT_POST, 'stock_quantity_update', FILTER_VALIDATE_INT);

    $roast_level = trim($_POST['roast_level_new'] ?? '');
    if (empty($roast_level) && !empty($_POST['roast_level'])) {
        $roast_level = trim($_POST['roast_level']);
    }
    
    $processing_method = null;
    if ($processing_column_exists) {
        $processing_method_input = trim($_POST['processing_method_new'] ?? '');
        if (empty($processing_method_input) && !empty($_POST['processing_method'])) {
            $processing_method_input = trim($_POST['processing_method']);
        }
        $processing_method = !empty($processing_method_input) ? $processing_method_input : null;
    } else {
        $processing_method = $product_data['processing_method'] ?? null;
    }

    $is_popular_update = isset($_POST['is_popular_update']) ? 1 : 0;
    
    $current_db_image_path = $product_data['image']; 
    $new_image_path_db = $current_db_image_path; 

    if (empty($title)) $errors[] = "Название продукта обязательно.";
    if ($price === false || $price <= 0) $errors[] = "Некорректная цена.";
    if ($weight_grams !== null && $weight_grams < 0 && $weight_grams !== false) $errors[] = "Некорректный вес.";
    if ($stock_quantity_update === false || $stock_quantity_update < 0) {
        $errors[] = "Некорректное количество на складе.";
    }


    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name_img_edit = uniqid('product_', true) . '_' . basename($_FILES['image']['name']);
        $target_file_img_edit = $upload_dir . $file_name_img_edit;
        $imageFileType_img_edit = strtolower(pathinfo($target_file_img_edit, PATHINFO_EXTENSION));
        $allowed_types_img_edit = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType_img_edit, $allowed_types_img_edit)) {
            $errors[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { 
            $errors[] = "Файл слишком большой. Максимум 5MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_img_edit)) {
                $new_image_path_db = 'uploads/products/' . $file_name_img_edit;
                if (!empty($current_db_image_path) && 
                    strpos($current_db_image_path, 'default-product.png') === false && 
                    file_exists('../' . ltrim($current_db_image_path, '/'))) {
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
        $sql_update_product = "UPDATE `products` SET title = ?, price = ?, image = ?, composition = ?, features = ?, country_of_origin = ?, weight_grams = ?, roast_level = ?, processing_method = ?, is_popular = ?, stock_quantity = ? WHERE id = ?";
        $stmt_update = $connect->prepare($sql_update_product);
        if ($stmt_update) {
            $weight_grams_to_db_edit = ($weight_grams === false || $weight_grams === '') ? null : $weight_grams;
            $country_of_origin_to_db_edit = empty($country_of_origin) ? null : $country_of_origin;
            $roast_level_to_db_edit = empty($roast_level) ? null : $roast_level;
            $processing_method_to_db_edit = empty($processing_method) ? null : $processing_method;
            $stock_quantity_to_db_update = ($stock_quantity_update === false) ? 0 : $stock_quantity_update;


            $stmt_update->bind_param("sdssssissiii", 
                $title, $price, $new_image_path_db, $composition, $features, 
                $country_of_origin_to_db_edit, $weight_grams_to_db_edit, $roast_level_to_db_edit, 
                $processing_method_to_db_edit, $is_popular_update, $stock_quantity_to_db_update, $id
            );
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
        $form_data_on_error = $_POST;
        $form_data_on_error['id'] = $id;
        $form_data_on_error['image'] = $new_image_path_db; 
        $product_data = array_merge($product_data ?: [], $form_data_on_error); 
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
            <img class="header__logo" src="<?php echo $base_url_prefix_for_links; ?>../img/logo.svg" alt="CoffeeFan Logo">
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
                <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo nl2br($message); ?></div>
            <?php endif; ?>
            <?php if (empty($message) && !empty($errors)): ?>
                <div class="message error"><?php echo implode("<br>", array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>

            <?php if ($product_data): ?>
            <form action="edit_product.php?id=<?php echo $product_id_to_edit; ?>" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_data['id']); ?>">
                
                <div class="form-group">
                    <label for="title_edit_form">Название:</label>
                    <input type="text" id="title_edit_form" name="title" value="<?php echo htmlspecialchars($product_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price_edit_form">Цена (₽):</label>
                    <input type="number" id="price_edit_form" name="price" value="<?php echo htmlspecialchars($product_data['price'] ?? ''); ?>" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image_edit_form">Изображение (оставьте пустым, чтобы не менять):</label>
                    <?php if (!empty($product_data['image'])): ?>
                        <div class="current-image-container">
                            <img src="../<?php echo htmlspecialchars(ltrim($product_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_edit_form" name="image" accept="image/*">
                </div>
                 <div class="form-group">
                    <label for="composition_edit_form">Состав:</label>
                    <textarea id="composition_edit_form" name="composition" rows="3"><?php echo htmlspecialchars($product_data['composition'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="features_edit_form">Особенности:</label>
                    <textarea id="features_edit_form" name="features" rows="3"><?php echo htmlspecialchars($product_data['features'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="country_of_origin_edit">Страна происхождения:</label>
                    <select id="country_of_origin_edit" name="country_of_origin">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>" <?php echo (isset($product_data['country_of_origin']) && $product_data['country_of_origin'] == $country) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="country_of_origin_new" 
                           value="<?php echo (!empty($product_data['country_of_origin']) && !in_array($product_data['country_of_origin'], $all_countries)) ? htmlspecialchars($product_data['country_of_origin']) : ''; ?>" 
                           placeholder="Или введите/измените страну" style="margin-top: 5px;">
                </div>
                <div class="form-group">
                    <label for="weight_grams_edit_form">Вес (в граммах):</label>
                    <input type="number" id="weight_grams_edit_form" name="weight_grams" value="<?php echo htmlspecialchars($product_data['weight_grams'] ?? ''); ?>" min="0">
                </div>
                <div class="form-group">
                    <label for="roast_level_edit">Степень обжарки:</label>
                    <select id="roast_level_edit" name="roast_level">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_roast_levels as $level): ?>
                            <option value="<?php echo htmlspecialchars($level); ?>" <?php echo (isset($product_data['roast_level']) && $product_data['roast_level'] == $level) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($level); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="roast_level_new" 
                           value="<?php echo (!empty($product_data['roast_level']) && !in_array($product_data['roast_level'], $all_roast_levels)) ? htmlspecialchars($product_data['roast_level']) : ''; ?>" 
                           placeholder="Или введите/измените степень" style="margin-top: 5px;">
                </div>
                <?php if ($processing_column_exists): ?>
                <div class="form-group">
                    <label for="processing_method_edit">Метод обработки:</label>
                    <select id="processing_method_edit" name="processing_method">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_processing_methods as $method): ?>
                            <option value="<?php echo htmlspecialchars($method); ?>" <?php echo (isset($product_data['processing_method']) && $product_data['processing_method'] == $method) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($method); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="processing_method_new" 
                           value="<?php echo (!empty($product_data['processing_method']) && !in_array($product_data['processing_method'], $all_processing_methods)) ? htmlspecialchars($product_data['processing_method']) : ''; ?>" 
                           placeholder="Или введите/измените метод" style="margin-top: 5px;">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="stock_quantity_update_form">Количество на складе:</label>
                    <input type="number" id="stock_quantity_update_form" name="stock_quantity_update" value="<?php echo htmlspecialchars($product_data['stock_quantity'] ?? '0'); ?>" min="0" required>
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
            <?php else: ?>
                <p>Данные продукта не могут быть загружены для редактирования.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>