<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';
$upload_dir = '../uploads/products/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
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

$message = '';
$message_type = 'info';

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $title = trim($_POST['title']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $composition = trim($_POST['composition'] ?? '');
        $features = trim($_POST['features'] ?? '');
        
        $country_of_origin = trim($_POST['country_of_origin_new'] ?? '');
        if (empty($country_of_origin) && !empty($_POST['country_of_origin'])) {
            $country_of_origin = trim($_POST['country_of_origin']);
        }

        $weight_grams = filter_input(INPUT_POST, 'weight_grams', FILTER_VALIDATE_INT);

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
        }
        
        $is_popular_add = isset($_POST['is_popular_add']) ? 1 : 0;
        $stock_quantity_add = filter_input(INPUT_POST, 'stock_quantity_add', FILTER_VALIDATE_INT);
        $errors_add = [];

        if (empty($title)) $errors_add[] = "Название продукта обязательно.";
        if ($price === false || $price <= 0) $errors_add[] = "Некорректная цена.";
        if ($weight_grams !== null && $weight_grams < 0 && $weight_grams !== false) $errors_add[] = "Некорректный вес.";
        if ($stock_quantity_add === false || $stock_quantity_add < 0) {
            $errors_add[] = "Некорректное количество на складе. Должно быть 0 или больше.";
        }

        $image_path_db = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_name_img = uniqid('product_', true) . '_' . basename($_FILES['image']['name']);
            $target_file_img = $upload_dir . $file_name_img;
            $imageFileType_img = strtolower(pathinfo($target_file_img, PATHINFO_EXTENSION));
            $allowed_types_img = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($imageFileType_img, $allowed_types_img)) {
                $errors_add[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors_add[] = "Файл слишком большой. Максимум 5MB.";
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_img)) {
                    $image_path_db = 'uploads/products/' . $file_name_img;
                } else {
                    $errors_add[] = "Ошибка загрузки файла изображения.";
                }
            }
        } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
             if($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE){
                $errors_add[] = "Изображение обязательно для нового продукта.";
             } else {
                $errors_add[] = "Ошибка при загрузке изображения (код: " . $_FILES['image']['error'] . ").";
             }
        }

        if (empty($errors_add)) {
            $sql_insert_product = "INSERT INTO `products` (title, price, image, composition, features, country_of_origin, weight_grams, roast_level, processing_method, is_popular, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_add = $connect->prepare($sql_insert_product);
            if ($stmt_add) {
                $weight_grams_to_db = ($weight_grams === false || $weight_grams === '') ? null : $weight_grams;
                $country_of_origin_to_db = empty($country_of_origin) ? null : $country_of_origin;
                $roast_level_to_db = empty($roast_level) ? null : $roast_level;
                $processing_method_to_db = empty($processing_method) ? null : $processing_method;
                $stock_quantity_to_db_add = ($stock_quantity_add === false) ? 0 : $stock_quantity_add;

                $stmt_add->bind_param("sdssssissii", 
                    $title, $price, $image_path_db, $composition, $features, 
                    $country_of_origin_to_db, $weight_grams_to_db, $roast_level_to_db, 
                    $processing_method_to_db, $is_popular_add, $stock_quantity_to_db_add
                );

                if ($stmt_add->execute()) {
                    $_SESSION['admin_message'] = "Продукт успешно добавлен.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Ошибка добавления продукта: " . $stmt_add->error;
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
            $_SESSION['form_add_product_errors'] = $errors_add;
            $_SESSION['old_add_product_data'] = $_POST;
        }
        header('Location: manage_products.php');
        exit();
    }
}
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt_get_img = $connect->prepare("SELECT image FROM products WHERE id = ?");
    if($stmt_get_img){
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

    $stmt_delete = $connect->prepare("DELETE FROM `products` WHERE `id` = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            $_SESSION['admin_message'] = "Продукт успешно удален.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Ошибка удаления продукта: " . $stmt_delete->error;
            $_SESSION['admin_message_type'] = "error";
        }
        $stmt_delete->close();
    } else {
        $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления: " . $connect->error;
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_products.php');
    exit();
}

$stmt_products = $connect->prepare("SELECT id, title, price, image, country_of_origin, weight_grams, roast_level, is_popular, stock_quantity FROM `products` ORDER BY id DESC");
$products_arr = [];
if ($stmt_products) {
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();
    while ($row = $result_products->fetch_assoc()) {
        $products_arr[] = $row;
    }
    $stmt_products->close();
} else {
    error_log("Manage Products: Failed to prepare product list query: " . $connect->error);
}

$form_add_product_errors = $_SESSION['form_add_product_errors'] ?? [];
$old_add_product_data = $_SESSION['old_add_product_data'] ?? [];
unset($_SESSION['form_add_product_errors'], $_SESSION['old_add_product_data']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление продуктами - CoffeeFan</title>
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

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAddProductFormBtn">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новый продукт
        </button>

        <div class="admin-content form-container" id="addProductFormContainer" style="display: <?php echo !empty($form_add_product_errors) ? 'block' : 'none'; ?>;">
             <div id="add-form-anchor"></div>
            <h2>Добавить новый продукт</h2>
            <?php if (!empty($form_add_product_errors)): ?>
                <div class="message error">
                    <strong>Обнаружены ошибки при добавлении:</strong><br>
                    <?php foreach ($form_add_product_errors as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="manage_products.php" method="post" enctype="multipart/form-data" class="admin-form" id="addProductForm">
                <div class="form-group">
                    <label for="title_add_prod">Название:</label>
                    <input type="text" id="title_add_prod" name="title" value="<?php echo htmlspecialchars($old_add_product_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price_add_prod">Цена (₽):</label>
                    <input type="number" id="price_add_prod" name="price" value="<?php echo htmlspecialchars($old_add_product_data['price'] ?? ''); ?>" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image_add_form_prod_file">Изображение:</label>
                    <input type="file" id="image_add_form_prod_file" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="composition_add_prod">Состав:</label>
                    <textarea id="composition_add_prod" name="composition" rows="3"><?php echo htmlspecialchars($old_add_product_data['composition'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="features_add_prod">Особенности:</label>
                    <textarea id="features_add_prod" name="features" rows="3"><?php echo htmlspecialchars($old_add_product_data['features'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="country_of_origin_add_prod">Страна происхождения:</label>
                    <select id="country_of_origin_add_prod" name="country_of_origin">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>" <?php echo (isset($old_add_product_data['country_of_origin']) && $old_add_product_data['country_of_origin'] == $country) ? 'selected' : ''; ?>><?php echo htmlspecialchars($country); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="country_of_origin_new" value="<?php echo htmlspecialchars($old_add_product_data['country_of_origin_new'] ?? ''); ?>" placeholder="Или введите новую страну" style="margin-top: 5px;">
                </div>
                <div class="form-group">
                    <label for="weight_grams_add_prod">Вес (в граммах):</label>
                    <input type="number" id="weight_grams_add_prod" name="weight_grams" value="<?php echo htmlspecialchars($old_add_product_data['weight_grams'] ?? ''); ?>" min="0">
                </div>
                <div class="form-group">
                    <label for="roast_level_add_prod">Степень обжарки:</label>
                    <select id="roast_level_add_prod" name="roast_level">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_roast_levels as $level): ?>
                            <option value="<?php echo htmlspecialchars($level); ?>" <?php echo (isset($old_add_product_data['roast_level']) && $old_add_product_data['roast_level'] == $level) ? 'selected' : ''; ?>><?php echo htmlspecialchars($level); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="roast_level_new" value="<?php echo htmlspecialchars($old_add_product_data['roast_level_new'] ?? ''); ?>" placeholder="Или введите новую степень" style="margin-top: 5px;">
                </div>
                <?php if ($processing_column_exists): ?>
                <div class="form-group">
                    <label for="processing_method_add_prod">Метод обработки:</label>
                    <select id="processing_method_add_prod" name="processing_method">
                        <option value="">-- Не выбрано --</option>
                        <?php foreach ($all_processing_methods as $method): ?>
                            <option value="<?php echo htmlspecialchars($method); ?>" <?php echo (isset($old_add_product_data['processing_method']) && $old_add_product_data['processing_method'] == $method) ? 'selected' : ''; ?>><?php echo htmlspecialchars($method); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="processing_method_new" value="<?php echo htmlspecialchars($old_add_product_data['processing_method_new'] ?? ''); ?>" placeholder="Или введите новый метод" style="margin-top: 5px;">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="stock_quantity_add_prod">Количество на складе:</label>
                    <input type="number" id="stock_quantity_add_prod" name="stock_quantity_add" value="<?php echo htmlspecialchars($old_add_product_data['stock_quantity_add'] ?? '0'); ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular_add" value="1" <?php echo (isset($old_add_product_data['is_popular_add']) && $old_add_product_data['is_popular_add'] == 1) ? 'checked' : ''; ?>>
                        Популярный товар
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_product" class="btn-save">Добавить продукт</button>
                    <button type="button" class="btn-cancel" id="cancelAddProductBtn">Отмена</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список продуктов</h2>
                <div class="search-input-container">
                    <input type="text" id="productSearchInput" placeholder="Поиск по названию, стране, обжарке...">
                </div>
            </div>
            <table class="admin-table" id="productsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                        <th>Изображение</th>
                        <th class="sortable" data-sort="title">Название</th>
                        <th class="sortable" data-sort="price" data-sort-type="number">Цена</th>
                        <th class="sortable" data-sort="stock_quantity" data-sort-type="number">Наличие</th>
                        <th class="sortable" data-sort="country_of_origin">Страна</th>
                        <th class="sortable" data-sort="weight_grams" data-sort-type="number">Вес</th>
                        <th class="sortable" data-sort="is_popular" data-sort-type="boolean">Популярный</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php if (!empty($products_arr)): ?>
                        <?php foreach ($products_arr as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($product['image'],'/')); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float)$product['price'], 2, '.', ' ')); ?> ₽</td>
                                <td style="text-align: center; <?php echo ($product['stock_quantity'] ?? 0) <= 0 ? 'color: red; font-weight: bold;' : ''; ?>">
                                    <?php echo htmlspecialchars($product['stock_quantity'] ?? 0); ?> шт.
                                </td>
                                <td><?php echo htmlspecialchars($product['country_of_origin'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars(isset($product['weight_grams']) && $product['weight_grams'] !== null ? $product['weight_grams'] . ' г' : '-'); ?></td>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="is-popular-checkbox" data-id="<?php echo $product['id']; ?>" <?php echo $product['is_popular'] ? 'checked' : ''; ?>>
                                </td>
                                <td class="actions">
                                    <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="edit-btn">Редакт.</a>
                                    <a href="manage_products.php?delete_id=<?php echo htmlspecialchars($product['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить этот продукт?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noProductsRow">
                            <td colspan="9">Продукты не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p id="productsTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Продукты по вашему запросу не найдены.</p>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const $addProductFormContainer = $('#addProductFormContainer');
    const $toggleAddProductFormBtn = $('#toggleAddProductFormBtn');
    const $cancelAddProductBtn = $('#cancelAddProductBtn');

    if ($addProductFormContainer.find('.message.error').length > 0) {
        $addProductFormContainer.show();
        $toggleAddProductFormBtn.find('.material-icons-outlined').text('remove_circle_outline');
        $toggleAddProductFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Скрыть форму');
    }

    $toggleAddProductFormBtn.on('click', function() {
        $addProductFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $addProductFormContainer.is(':visible') ? 'remove_circle_outline' : 'add_circle_outline'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $addProductFormContainer.is(':visible') ? 'Скрыть форму' : 'Добавить новый продукт'
        );
        if (!$addProductFormContainer.is(':visible')) {
            $('#addProductForm')[0].reset();
            $addProductFormContainer.find('.message.error').remove();
        }
    });
    $cancelAddProductBtn.on('click', function() {
        $addProductFormContainer.slideUp(300);
        $toggleAddProductFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddProductFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новый продукт');
        $('#addProductForm')[0].reset();
        $addProductFormContainer.find('.message.error').remove();
    });

    $('#productsTableBody').on('change', '.is-popular-checkbox', function() {
        const $checkbox = $(this);
        const productId = $checkbox.data('id');
        const isPopular = $checkbox.is(':checked') ? 1 : 0;
        $.ajax({
            url: 'update_popularity.php',
            type: 'POST',
            data: { id: productId, is_popular: isPopular },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('Статус популярности обновлен для продукта ID ' + productId);
                } else {
                    console.error('Ошибка обновления: ' + response.message);
                    alert('Ошибка обновления статуса популярности: ' + response.message);
                    $checkbox.prop('checked', !isPopular); 
                }
            }.bind(this),
            error: function(xhr, status, error) {
                console.error('AJAX ошибка: ' + error);
                alert('Произошла ошибка при связи с сервером.');
                $checkbox.prop('checked', !isPopular);
            }.bind(this)
        });
    });

    const $productsTable = $('#productsTable');
    const $productsTableBody = $('#productsTableBody');
    const $productSearchInput = $('#productSearchInput');
    const $noProductsRowPHP = $('#noProductsRow'); 
    const $productsEmptyMessageJS = $('#productsTableEmptyMessage');

    $productsTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        if (!column) return;
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $productsTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $productsTableBody.find('tr:not(#noProductsRow)').get();
        
        const colIndex = $th.index();

        rows.sort(function(a, b) {
            let valA = $(a).children('td').eq(colIndex).text().trim();
            let valB = $(b).children('td').eq(colIndex).text().trim();

            if (type === 'number') {
                valA = parseFloat(valA.replace(/[^0-9,.]/g, '').replace(',', '.')) || 0;
                valB = parseFloat(valB.replace(/[^0-9,.]/g, '').replace(',', '.')) || 0;
            } else if (type === 'boolean') {
                valA = $(a).children('td').eq(colIndex).find('input[type="checkbox"]').is(':checked');
                valB = $(b).children('td').eq(colIndex).find('input[type="checkbox"]').is(':checked');
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $productsTableBody.append(row);
        });
    });

    $productSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $productsTableBody.find('tr:not(#noProductsRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                const th = $productsTable.find('thead th').eq(index);
                if (th.data('sort') === 'title' || th.data('sort') === 'country_of_origin' || th.data('sort') === 'roast_level' || th.data('sort') === 'id') {
                     rowText += $(this).text().toLowerCase() + ' ';
                } else if (index !== 1 && index !== 7 && index !== 8) { 
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
        if ($noProductsRowPHP.length > 0 && $productsTableBody.find('tr:not(#noProductsRow):visible').length === 0 && searchTerm === "") {
            $noProductsRowPHP.show();
            $productsEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $productsTableBody.find('tr:not(#noProductsRow)').length > 0) {
            $noProductsRowPHP.hide();
            $productsEmptyMessageJS.show();
        } else {
            $noProductsRowPHP.hide();
            $productsEmptyMessageJS.hide();
        }
    });
    if ($productsTableBody.find('tr:not(#noProductsRow)').length === 0 && $noProductsRowPHP.is(':visible')) {
    } else {
         $productSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>