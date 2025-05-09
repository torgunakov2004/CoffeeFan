<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$upload_dir = '../uploads/products/'; // Директория для изображений продуктов
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $title = trim($_POST['title']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $composition = trim($_POST['composition'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $country_of_origin = trim($_POST['country_of_origin'] ?? '');
        $weight_grams = filter_input(INPUT_POST, 'weight_grams', FILTER_VALIDATE_INT);
        $roast_level = trim($_POST['roast_level'] ?? '');
        $processing_method = trim($_POST['processing_method'] ?? '');
        $is_popular_add = isset($_POST['is_popular_add']) ? 1 : 0;
        $errors_add = [];

        if (empty($title)) $errors_add[] = "Название продукта обязательно.";
        if ($price === false || $price <= 0) $errors_add[] = "Некорректная цена.";
        if ($weight_grams !== null && $weight_grams <=0) $errors_add[] = "Некорректный вес.";


        $image_path_db = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid('product_', true) . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($imageFileType, $allowed_types)) {
                $errors_add[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB
                $errors_add[] = "Файл слишком большой. Максимум 5MB.";
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path_db = 'uploads/products/' . $file_name; // Путь от корня сайта для БД
                } else {
                    $errors_add[] = "Ошибка загрузки файла изображения.";
                }
            }
        } else {
            $errors_add[] = "Изображение обязательно для нового продукта.";
        }

        if (empty($errors_add)) {
            $stmt_add = $connect->prepare("INSERT INTO `products` (title, price, image, composition, features, country_of_origin, weight_grams, roast_level, processing_method, is_popular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_add) {
                $stmt_add->bind_param("sdssssissi", $title, $price, $image_path_db, $composition, $features, $country_of_origin, $weight_grams, $roast_level, $processing_method, $is_popular_add);
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
        }
        header('Location: manage_products.php');
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $stmt_get_img = $connect->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_get_img->bind_param("i", $delete_id);
    $stmt_get_img->execute();
    $img_result = $stmt_get_img->get_result();
    if($img_row = $img_result->fetch_assoc()){
        if(!empty($img_row['image']) && file_exists('../' . ltrim($img_row['image'], '/'))){
            unlink('../' . ltrim($img_row['image'], '/'));
        }
    }
    $stmt_get_img->close();

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

$stmt_products = $connect->prepare("SELECT * FROM `products` ORDER BY id DESC");
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
    <title>Управление продуктами - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Управление Продуктами</h1>
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
            <h2>Добавить новый продукт</h2>
            <form action="manage_products.php" method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="title">Название:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="price">Цена (₽):</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image">Изображение:</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="composition">Состав:</label>
                    <textarea id="composition" name="composition" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="features">Особенности:</label>
                    <textarea id="features" name="features" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="country_of_origin">Страна происхождения:</label>
                    <input type="text" id="country_of_origin" name="country_of_origin">
                </div>
                <div class="form-group">
                    <label for="weight_grams">Вес (в граммах):</label>
                    <input type="number" id="weight_grams" name="weight_grams" min="0">
                </div>
                <div class="form-group">
                    <label for="roast_level">Степень обжарки:</label>
                    <input type="text" id="roast_level" name="roast_level">
                </div>
                <div class="form-group">
                    <label for="processing_method">Метод обработки:</label>
                    <input type="text" id="processing_method" name="processing_method">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_popular_add" value="1">
                        Популярный товар
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_product" class="btn-save">Добавить продукт</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <h2>Список продуктов</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Страна</th>
                        <th>Вес</th>
                        <th>Обжарка</th>
                        <th>Популярный</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
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
                                <td><?php echo htmlspecialchars(number_format($product['price'], 2, '.', ' ')); ?> ₽</td>
                                <td><?php echo htmlspecialchars($product['country_of_origin'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['weight_grams'] ? $product['weight_grams'] . ' г' : '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['roast_level'] ?? '-'); ?></td>
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
                        <tr>
                            <td colspan="9">Продукты не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- <button id="save-popularity" style="display: none;" class="add-new-btn">Сохранить изменения популярности</button> -->
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.is-popular-checkbox').change(function() {
        const productId = $(this).data('id');
        const isPopular = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: 'update_popularity.php',
            type: 'POST',
            data: {
                id: productId,
                is_popular: isPopular
            },
            dataType: 'json', // Ожидаем JSON ответ
            success: function(response) {
                if (response.status === 'success') {
                    // Можно добавить уведомление toastr или просто лог
                    console.log('Статус популярности обновлен для продукта ID ' + productId);
                    // Если хотите перезагрузить страницу для обновления таблицы (не лучший UX, но простой)
                    // window.location.reload(); 
                } else {
                    console.error('Ошибка обновления: ' + response.message);
                    alert('Ошибка обновления статуса популярности: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX ошибка: ' + error);
                alert('Произошла ошибка при связи с сервером.');
            }
        });
    });
});
</script>
</body>
</html>