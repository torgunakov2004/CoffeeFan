<?php
session_start();
require_once '../config/connect.php';

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) { return strtolower($string); }
    function mb_strtoupper($string, $encoding = null) { return strtoupper($string); }
    function mb_substr($string, $start, $length = null, $encoding = null) { return substr($string, $start, $length); }
}

$unique_countries = [];
$countries_query_sql = "SELECT DISTINCT country_of_origin FROM products WHERE country_of_origin IS NOT NULL AND country_of_origin != '' ORDER BY country_of_origin ASC";
$countries_query = mysqli_query($connect, $countries_query_sql);
if ($countries_query) {
    while ($row = mysqli_fetch_assoc($countries_query)) {
        $unique_countries[] = $row['country_of_origin'];
    }
}

$unique_weights = [];
$weights_query_sql = "SELECT DISTINCT weight_grams FROM products WHERE weight_grams IS NOT NULL AND weight_grams > 0 ORDER BY weight_grams ASC";
$weights_query = mysqli_query($connect, $weights_query_sql);
if ($weights_query) {
    while ($row = mysqli_fetch_assoc($weights_query)) {
        $unique_weights[] = $row['weight_grams'];
    }
}

$unique_roast_levels = [];
$roast_query_sql = "SELECT DISTINCT roast_level FROM products WHERE roast_level IS NOT NULL AND roast_level != '' ORDER BY roast_level ASC";
$roast_query = mysqli_query($connect, $roast_query_sql);
if ($roast_query) {
    while ($row = mysqli_fetch_assoc($roast_query)) {
        $unique_roast_levels[] = $row['roast_level'];
    }
}

$unique_processing_methods = [];
$check_column_query = mysqli_query($connect, "SHOW COLUMNS FROM `products` LIKE 'processing_method'");
if ($check_column_query && $check_column_query->num_rows == 1) {
    $processing_query_sql = "SELECT DISTINCT processing_method FROM products WHERE processing_method IS NOT NULL AND processing_method != '' ORDER BY processing_method ASC";
    $processing_query = mysqli_query($connect, $processing_query_sql);
    if ($processing_query) {
        while ($row = mysqli_fetch_assoc($processing_query)) {
            $unique_processing_methods[] = $row['processing_method'];
        }
    }
}

$initial_products_limit = 12;
$products_query_string = "SELECT * FROM `products` ORDER BY `id` DESC LIMIT ?";
$stmt_products_initial = $connect->prepare($products_query_string);
$products_result_initial = null;
$totalProducts = 0;

if ($stmt_products_initial) {
    $stmt_products_initial->bind_param("i", $initial_products_limit);
    $stmt_products_initial->execute();
    $products_result_initial = $stmt_products_initial->get_result();
    if (!$products_result_initial) {
        error_log("Ошибка выполнения запроса начальных продуктов: " . $connect->error);
    }
    $stmt_products_initial->close();

    $total_products_query = mysqli_query($connect, "SELECT COUNT(*) as total FROM `products`");
    if ($total_products_query) {
        $total_row_products = mysqli_fetch_assoc($total_products_query);
        $totalProducts = (int)$total_row_products['total'];
    }
} else {
    error_log("Ошибка подготовки запроса начальных продуктов: " . $connect->error);
}

$cart_quantities = [];
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
    $query_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart = $connect->prepare($query_cart);
    if ($stmt_cart) {
        $stmt_cart->bind_param("i", $user_id);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();
        while ($row_cart = $result_cart->fetch_assoc()) {
            $cart_quantities[$row_cart['product_id']] = $row_cart['quantity'];
        }
        $stmt_cart->close();
    } else {
        error_log("Ошибка подготовки запроса корзины: " . $connect->error);
    }
}
$has_items_in_cart = !empty($cart_quantities);

$initial_menu_limit = 8;
$menu_query_sql = "SELECT * FROM `menu` ORDER BY `id` ASC LIMIT ?";
$stmt_menu = $connect->prepare($menu_query_sql);
$menuItems = null;
$totalMenuItems = 0;
if ($stmt_menu) {
    $stmt_menu->bind_param("i", $initial_menu_limit);
    $stmt_menu->execute();
    $menuItems = $stmt_menu->get_result();
    if (!$menuItems) { error_log("Ошибка выполнения запроса меню (начальная загрузка): " . $connect->error); }
    $stmt_menu->close();
    $total_menu_query = mysqli_query($connect, "SELECT COUNT(*) as total FROM `menu`");
    if ($total_menu_query) { $total_row_menu = mysqli_fetch_assoc($total_menu_query); $totalMenuItems = (int)$total_row_menu['total']; }
} else { error_log("Ошибка подготовки запроса меню: " . $connect->error); }

$reviews_query_sql = "SELECT r.*, u.avatar as user_avatar_path 
                      FROM `reviews` r 
                      LEFT JOIN `user` u ON r.user_id = u.id 
                      WHERE r.`status` = 'approved' 
                      ORDER BY r.`id` DESC 
                      LIMIT 3";
$reviews = mysqli_query($connect, $reviews_query_sql);
if (!$reviews) { error_log("Ошибка выполнения запроса отзывов на странице Продукты: " . mysqli_error($connect)); $reviews = false;}

$base_web_path = '..'; 
if (function_exists('display_date_in_user_timezone') === false) {
    function display_date_in_user_timezone($date_string_utc, $default_timezone = 'Asia/Irkutsk') {
        if (empty($date_string_utc) || $date_string_utc === '0000-00-00 00:00:00') { return 'N/A'; }
        $target_timezone_str = $default_timezone;
        if (isset($_SESSION['user']['timezone']) && !empty($_SESSION['user']['timezone'])) {
            if (in_array($_SESSION['user']['timezone'], timezone_identifiers_list())) { $target_timezone_str = $_SESSION['user']['timezone'];}
        } elseif (isset($_SESSION['user_timezone_js']) && !empty($_SESSION['user_timezone_js'])) {
            if (in_array($_SESSION['user_timezone_js'], timezone_identifiers_list())) { $target_timezone_str = $_SESSION['user_timezone_js'];}
        }
        try {
            $datetime_utc = new DateTime($date_string_utc, new DateTimeZone('UTC'));
            $target_timezone_obj = new DateTimeZone($target_timezone_str);
            $datetime_user_tz = $datetime_utc->setTimezone($target_timezone_obj);
            return $datetime_user_tz->format('d.m.Y H:i');
        } catch (Exception $e) {
            error_log("Timezone conversion error: " . $e->getMessage() . " for date " . $date_string_utc . " and tz " . $target_timezone_str);
            return date('d.m.Y H:i (UTC)', strtotime($date_string_utc)); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffeee shop - Продукты</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <?php
        $current_page_is_produkty = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <main>
        <section id="popular-section" class="section-main">
            <div class="container">
                <h3 class="section-subtitle">Кофе натурального зернового</h3>
                <div class="search-filter-products-container">
                    <div class="main-filter-bar">
                        <div class="search-container filter-item-main-search">
                            <label for="search-product-input" class="filter-label sr-only">Поиск:</label>
                            <input type="text" id="search-product-input" placeholder="Поиск по названию..." class="search-input main-search-input">
                            <img src="../img/icon_2.png" alt="Поиск" class="search-icon">
                        </div>
                        <div class="filter-item-toggle-button">
                            <button id="toggle-filters-btn" class="btn-filter-toggle">
                                <span class="material-icons-outlined">filter_list</span>
                                Фильтры
                                <span class="filter-arrow">▼</span>
                            </button>
                        </div>
                    </div>
                    <div id="advanced-filters-panel" class="advanced-filters-panel" style="display: none;">
                        <div class="search-filter-controls-advanced">
                            <div class="filter-item">
                                <label for="product-filter-type" class="filter-label">Тип:</label>
                                <select id="product-filter-type" class="product-filter-select">
                                    <option value="all">Все продукты</option>
                                    <option value="popular">Популярные</option>
                                    <option value="not_popular">Обычные</option>
                                </select>
                            </div>
                            <div class="filter-item price-filter">
                                <label class="filter-label">Цена (₽):</label>
                                <div class="price-inputs">
                                    <input type="number" id="price-from" class="price-input" placeholder="От" min="0" step="any">
                                    <span>-</span>
                                    <input type="number" id="price-to" class="price-input" placeholder="До" min="0" step="any">
                                </div>
                            </div>
                            <div class="filter-item">
                                <label for="filter-country" class="filter-label">Страна:</label>
                                <select id="filter-country" class="product-filter-select">
                                    <option value="all">Все страны</option>
                                    <?php if (!empty($unique_countries)): ?>
                                        <?php foreach ($unique_countries as $country_value): ?>
                                            <?php $processed_country_value = mb_strtolower(trim($country_value), 'UTF-8'); ?>
                                            <option value="<?= htmlspecialchars($processed_country_value) ?>"><?= htmlspecialchars($country_value) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="filter-weight" class="filter-label">Вес (грамм):</label>
                                <select id="filter-weight" class="product-filter-select">
                                    <option value="all">Любой вес</option>
                                    <?php foreach ($unique_weights as $weight): ?>
                                        <option value="<?= intval($weight) ?>"><?= intval($weight) ?> г</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="filter-roast" class="filter-label">Обжарка:</label>
                                <select id="filter-roast" class="product-filter-select">
                                    <option value="all">Любая обжарка</option>
                                    <?php if (!empty($unique_roast_levels)): ?>
                                        <?php foreach ($unique_roast_levels as $roast_value): ?>
                                            <?php $processed_roast_value = mb_strtolower(trim($roast_value), 'UTF-8'); ?>
                                            <option value="<?= htmlspecialchars($processed_roast_value) ?>"><?= htmlspecialchars($roast_value) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php if (!empty($unique_processing_methods)): ?>
                            <div class="filter-item">
                                <label for="filter-processing" class="filter-label">Метод обработки:</label>
                                <select id="filter-processing" class="product-filter-select">
                                    <option value="all">Любой метод</option>
                                    <?php foreach ($unique_processing_methods as $method_value): ?>
                                        <?php $processed_method_value = mb_strtolower(trim($method_value), 'UTF-8'); ?>
                                        <option value="<?= htmlspecialchars($processed_method_value) ?>"><?= htmlspecialchars($method_value) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="filter-item filter-item-reset-button">
                                <button id="reset-filters-btn" class="btn-secondary">Сбросить все фильтры</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="popular-wrap" id="products-container">
                    <?php if ($products_result_initial && mysqli_num_rows($products_result_initial) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($products_result_initial)): ?>
                            <?php
                                $title_val = $product['title'] ?? '';
                                $country_val = $product['country_of_origin'] ?? '';
                                $roast_val = $product['roast_level'] ?? '';
                                $processing_val = $product['processing_method'] ?? '';
                                $data_title = htmlspecialchars(mb_strtolower(trim($title_val), 'UTF-8'));
                                $data_country = htmlspecialchars(mb_strtolower(trim($country_val), 'UTF-8'));
                                $data_roast = htmlspecialchars(mb_strtolower(trim($roast_val), 'UTF-8'));
                                $data_processing = htmlspecialchars(mb_strtolower(trim($processing_val), 'UTF-8'));
                                $stock_quantity = (int)($product['stock_quantity'] ?? 0);
                                $stock_status_text = '';
                                $is_out_of_stock = false;
                                if ($stock_quantity <= 0) {
                                    $stock_status_text = 'Нет в наличии';
                                    $is_out_of_stock = true;
                                } elseif ($stock_quantity <= 50) {
                                    $stock_status_text = "Мало в наличии (" . $stock_quantity . ")";
                                } else {
                                    $stock_status_text = 'В наличии';
                                }
                                $current_product_in_cart_qty = $cart_quantities[$product['id']] ?? 0;
                            ?>
                            <div class="popular product-item"
                                data-id="<?= $product['id'] ?>"
                                data-title="<?= $data_title ?>"
                                data-popular="<?= $product['is_popular'] ?? '0' ?>"
                                data-price="<?= floatval($product['price']) ?>"
                                data-country="<?= $data_country ?>"
                                data-weight="<?= intval($product['weight_grams'] ?? 0) ?>"
                                data-roast="<?= $data_roast ?>"
                                data-processing="<?= $data_processing ?>"
                                data-stock="<?= $stock_quantity ?>"
                            >
                                <div class="popular__flick-container">
                                    <div class="popular__content-wrapper">
                                        <div class="popular__image-side">
                                            <img class="popular__img" src="../<?php echo htmlspecialchars(ltrim($product['image'], '/')); ?>" alt="<?php echo htmlspecialchars($product['title'] ?? 'Товар'); ?>">
                                        </div>
                                        <div class="popular__info-side">
                                            <h4>Состав:</h4>
                                            <p><?= nl2br(htmlspecialchars($product['composition'] ?? 'Информация о составе отсутствует.')) ?></p>
                                            <h4>Особенности:</h4>
                                            <p><?= nl2br(htmlspecialchars($product['features'] ?? 'Информация об особенностях отсутствует.')) ?></p>
                                            <?php if (!empty($product['country_of_origin'])): ?><p><strong>Страна:</strong> <?= htmlspecialchars($product['country_of_origin']) ?></p><?php endif; ?>
                                            <?php if (!empty($product['weight_grams'])): ?><p><strong>Вес:</strong> <?= htmlspecialchars($product['weight_grams']) ?> г</p><?php endif; ?>
                                            <?php if (!empty($product['roast_level'])): ?><p><strong>Обжарка:</strong> <?= htmlspecialchars($product['roast_level']) ?></p><?php endif; ?>
                                            <?php if (!empty($product['processing_method'])): ?><p><strong>Обработка:</strong> <?= htmlspecialchars($product['processing_method']) ?></p><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <h3 class="popular__title-display"><?= htmlspecialchars($product['title'] ?? 'Название товара') ?></h3>
                                <b class="popular__price">Цена - <strong class="popular__price_dark"><?= htmlspecialchars($product['price']) ?>₽/</strong> <?= htmlspecialchars(floatval($product['price']) + 30) ?>₽</b>
                                <div class="product-stock-status" data-product-id-stock-status="<?= $product['id'] ?>">
                                    <?php echo htmlspecialchars($stock_status_text); ?>
                                </div>
                                <div class="popular__actions">
                                    <?php if (isset($_SESSION['user'])): ?>
                                        <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>" <?php if ($is_out_of_stock) echo 'disabled'; ?>>
                                            <span class="material-icons-outlined">local_mall</span>Добавить
                                        </button>
                                        <button class="remove-from-cart-btn" data-product-id="<?= $product['id'] ?>" <?php if ($current_product_in_cart_qty <= 0) echo 'disabled'; ?>>
                                            <span class="material-icons-outlined">remove_shopping_cart</span>Убрать
                                        </button>
                                        <span class="product-count" id="count-<?= $product['id'] ?>">
                                            <?= $current_product_in_cart_qty ?>
                                        </span>
                                    <?php else: ?>
                                        <p class="auth-message">Авторизуйтесь, чтобы добавить товар в корзину.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-products-message no-products-message-initial">Товары не найдены.</p>
                    <?php endif; ?>
                </div>
                <?php if ($totalProducts > $initial_products_limit): ?>
                <div class="load-more-container" style="text-align: center; margin-top: 30px;">
                    <button id="load-more-products-btn" class="btn-primary">Еще...</button>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <section id="menu-section" class="section-main">
            <div class="container">
                <h3 class="section-subtitle">Меню кофейни</h3>
                <div class="menu-wrap" id="menu-items-container">
                    <?php if ($menuItems && $menuItems->num_rows > 0): ?>
                        <?php while ($item = $menuItems->fetch_assoc()): ?>
                            <div class="menu">
                                <div class="menu__img-container">
                                <img class="menu__img" src="../<?php echo htmlspecialchars(ltrim($item['image'], '/')); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                                <div class="menu__content">
                                    <h3 class="menu__title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="menu__description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                    <b class="menu__price"><?php echo htmlspecialchars($item['price']); ?> ₽</b>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-products-message" id="no-menu-message">Пункты меню не найдены.</p>
                    <?php endif; ?>
                </div>
                <?php if ($totalMenuItems > $initial_menu_limit): ?>
                <div class="load-more-container" style="text-align: center; margin-top: 30px;">
                    <button id="load-more-menu-btn" class="btn-primary">Еще...</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="cafe-menu-location-info">
                    <p>
                        Понравилось наше меню? Ждем вас в нашей уютной кофейне! 
                        Узнать адрес и посмотреть, как к нам добраться, можно на странице 
                        <a href="../Контакты/index.php#map-section" class="highlighted-link">Контакты</a>.
                    </p>
                </div>
        </section>
        <section id="testimonial-section" class="section-main testimonial-carousel">
            <div class="container">
                <h3 class="section-subtitle">Последние отзывы</h3>
                <div class="testimonial-wrap">
                    <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                            <div class="testimonial">
                                <div class="testimonial-data">
                                    <?php
                                    $display_initials_prod = true;
                                    $avatar_to_display_src_prod = '';
                                    if (!empty($review['user_avatar_path'])) {
                                        $path_to_user_avatar_from_script_prod = '../' . ltrim($review['user_avatar_path'], '/');
                                        $full_server_path_avatar_check = $_SERVER['DOCUMENT_ROOT'] . ($base_web_path ?? '') . '/' . ltrim($review['user_avatar_path'], '/');
                                        if (file_exists($full_server_path_avatar_check)) {
                                            $avatar_to_display_src_prod = htmlspecialchars($path_to_user_avatar_from_script_prod);
                                            $display_initials_prod = false;
                                        }
                                    }
                                    if ($display_initials_prod) {
                                        $initial_prod = '';
                                        if (!empty($review['name'])) {
                                            $initial_prod = htmlspecialchars(mb_strtoupper(mb_substr($review['name'], 0, 1, 'UTF-8')));
                                        }
                                        echo '<div class="testimonial__img_initials">' . $initial_prod . '</div>';
                                    } else {
                                        echo '<img class="testimonial__img" src="' . $avatar_to_display_src_prod . '" alt="Аватар ' . htmlspecialchars($review['name']) . '">';
                                    }
                                    ?>
                                    <div>
                                        <h3 class="testimonial__name"><?php echo htmlspecialchars($review['name']); ?></h3>
                                        <?php if (!empty($review['created_at'])): ?>
                                            <p class="testimonial__date_products"><?php echo display_date_in_user_timezone($review['created_at']); ?></p>
                                        <?php endif; ?>
                                        <p class="testimonial__text section__text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                        <?php if (isset($review['rating']) && $review['rating'] > 0): ?>
                                            <div class="testimonial__rating_products">
                                                <?php for ($s_i = 1; $s_i <= 5; $s_i++): ?>
                                                    <span class="star <?php echo ($s_i <= $review['rating']) ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-products-message">Отзывов пока нет.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include_once '../footer.php'; ?>
    <script>
    $(document).ready(function() {
        function updateProductStockStatus(productId, stockQuantity) {
            const stockStatusElement = $(`.product-stock-status[data-product-id-stock-status="${productId}"]`);
            const addButton = $(`.add-to-cart-btn[data-product-id="${productId}"]`);
            let statusText = '';
            let addClass = '';
            let removeClasses = 'status-in-stock status-low-stock status-out-of-stock';
            let disabled = false;
            if (stockQuantity <= 0) {
                statusText = 'Нет в наличии';
                addClass = 'status-out-of-stock';
                disabled = true;
            } else if (stockQuantity <= 50) {
                statusText = `Мало в наличии (${stockQuantity})`;
                addClass = 'status-low-stock';
            } else {
                statusText = 'В наличии';
                addClass = 'status-in-stock';
            }
            if (stockStatusElement.length) {
                stockStatusElement.text(statusText).removeClass(removeClasses).addClass(addClass);
            }
            if (addButton.length) {
                addButton.prop('disabled', disabled);
            }
        }
        function updateRemoveButtonState(productId, cartQuantity) {
            const removeButton = $(`.remove-from-cart-btn[data-product-id="${productId}"]`);
            if (removeButton.length) {
                removeButton.prop('disabled', cartQuantity <= 0);
            }
        }
        $('.add-to-cart-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var countElement = $('#count-' + productId);
            var $button = $(this);
            $button.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: 'add_to_cart.php',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if(countElement.length) { countElement.text(response.quantity); }
                        toastr.success(response.message || 'Товар добавлен в корзину');
                        $('.header-action__cart-1').addClass('active');
                        if (typeof response.stock_quantity !== 'undefined') {
                            updateProductStockStatus(productId, response.stock_quantity);
                            $(`.product-item[data-id="${productId}"]`).data('stock', response.stock_quantity);
                        }
                        updateRemoveButtonState(productId, response.quantity);
                    } else {
                        toastr.error(response.message || 'Не удалось добавить товар.');
                        if (typeof response.stock_quantity !== 'undefined' && response.stock_quantity <= 0) {
                            updateProductStockStatus(productId, 0);
                             $(`.product-item[data-id="${productId}"]`).data('stock', 0);
                        } else if (typeof response.stock_quantity !== 'undefined' ) {
                             $button.prop('disabled', false);
                        }
                        if (typeof response.quantity !== 'undefined') {
                            updateRemoveButtonState(productId, response.quantity);
                        } else {
                             $button.prop('disabled', false); 
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error('Ошибка сервера при добавлении товара.');
                    $button.prop('disabled', false);
                }
            });
        });
        $('.remove-from-cart-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var countElement = $('#count-' + productId);
            var $thisRemoveButton = $(this);
            if ($thisRemoveButton.prop('disabled')) {
                return;
            }
            $.ajax({
                type: 'POST',
                url: 'remove_from_cart.php',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if(countElement.length) { countElement.text(response.quantity); }
                        toastr.info(response.message || 'Количество товара обновлено');
                        if (typeof response.stock_quantity !== 'undefined') {
                            updateProductStockStatus(productId, response.stock_quantity);
                             $(`.product-item[data-id="${productId}"]`).data('stock', response.stock_quantity);
                        }
                        updateRemoveButtonState(productId, response.quantity);
                        let totalCartItems = 0;
                        $('.product-count').each(function() { totalCartItems += parseInt($(this).text()) || 0; });
                        if (totalCartItems === 0) { $('.header-action__cart-1').removeClass('active'); }
                        else { $('.header-action__cart-1').addClass('active');}
                    } else {
                        toastr.error(response.message || 'Не удалось обновить товар.');
                    }
                },
                error: function(xhr) {
                    toastr.error('Ошибка сервера при удалении товара.');
                }
            });
        });
        $('.popular__flick-container').on('click', function() { $(this).closest('.popular').toggleClass('info-visible'); });
        const searchProductInput = $('#search-product-input');
        const productFilterTypeSelect = $('#product-filter-type');
        const priceFromInput = $('#price-from');
        const priceToInput = $('#price-to');
        const filterCountrySelect = $('#filter-country');
        const filterWeightSelect = $('#filter-weight');
        const filterRoastSelect = $('#filter-roast');
        const filterProcessingSelect = $('#filter-processing');
        const resetFiltersBtn = $('#reset-filters-btn');
        const toggleFiltersBtn = $('#toggle-filters-btn');
        const advancedFiltersPanel = $('#advanced-filters-panel');
        const filterArrow = toggleFiltersBtn.find('.filter-arrow');
        const popularWrap = $('.popular-wrap');
        function applyProductSearchAndFilter() {
            const searchTerm = searchProductInput.val().toLowerCase().trim();
            const filterTypeValue = productFilterTypeSelect.val();
            let priceFrom = parseFloat(priceFromInput.val());
            let priceTo = parseFloat(priceToInput.val());
            const selectedCountry = filterCountrySelect.val();
            const selectedWeight = filterWeightSelect.val();
            const selectedRoast = filterRoastSelect.val();    
            const selectedProcessing = filterProcessingSelect.length ? filterProcessingSelect.val() : 'all';
            if (isNaN(priceFrom) || priceFrom < 0) priceFrom = 0;
            if (isNaN(priceTo) || priceTo < 0 || priceToInput.val().trim() === '') priceTo = Infinity;
            if (priceFrom > priceTo && priceTo !== Infinity) { [priceFrom, priceTo] = [priceTo, priceFrom]; }
            let visibleProductsCount = 0;
            $('#products-container .product-item').each(function() {
                const productCard = $(this);
                const title = productCard.data('title').toString(); 
                const isPopular = productCard.data('popular').toString();
                const productPrice = parseFloat(productCard.data('price'));
                const productCountry = productCard.data('country').toString(); 
                const productWeight = productCard.data('weight').toString();   
                const productRoast = productCard.data('roast').toString();    
                const productProcessing = productCard.data('processing').toString(); 
                let matchesSearch = searchTerm === "" || title.includes(searchTerm);
                let matchesFilterType = filterTypeValue === 'all' || (filterTypeValue === 'popular' && isPopular === '1') || (filterTypeValue === 'not_popular' && isPopular === '0');
                let matchesPrice = !isNaN(productPrice) && (productPrice >= priceFrom && productPrice <= priceTo);
                let matchesCountry = selectedCountry === 'all' || productCountry === selectedCountry;
                let matchesWeight = selectedWeight === 'all' || productWeight === selectedWeight;
                let matchesRoast = selectedRoast === 'all' || productRoast === selectedRoast;
                let matchesProcessing = selectedProcessing === 'all' || productProcessing === selectedProcessing;
                if (matchesSearch && matchesFilterType && matchesPrice && matchesCountry && matchesWeight && matchesRoast && matchesProcessing) {
                    productCard.show();
                    visibleProductsCount++;
                } else {
                    productCard.hide();
                }
            });
            const jsNoProductsMessage = popularWrap.find('.no-products-message-js');
            const initialNoProductsMessage = popularWrap.find('.no-products-message-initial');
            if ($('#products-container .product-item').length === 0 && initialNoProductsMessage.length > 0) {
            } else {
                if (visibleProductsCount === 0) {
                    if (jsNoProductsMessage.length === 0) {
                        popularWrap.append('<p class="no-products-message no-products-message-js">Товары по вашему запросу не найдены.</p>');
                    } else {
                        jsNoProductsMessage.show();
                    }
                } else {
                    jsNoProductsMessage.remove();
                }
            }
        }
        const filterControls = [ searchProductInput, productFilterTypeSelect, priceFromInput, priceToInput, filterCountrySelect, filterWeightSelect, filterRoastSelect ];
        if (filterProcessingSelect.length) { filterControls.push(filterProcessingSelect); }
        filterControls.forEach(control => { control.on('input change', applyProductSearchAndFilter); });
        resetFiltersBtn.on('click', function() {
            searchProductInput.val(''); productFilterTypeSelect.val('all'); priceFromInput.val(''); priceToInput.val('');
            filterCountrySelect.val('all'); filterWeightSelect.val('all'); filterRoastSelect.val('all');
            if (filterProcessingSelect.length) { filterProcessingSelect.val('all');}
            applyProductSearchAndFilter();
        });
        toggleFiltersBtn.on('click', function() {
            advancedFiltersPanel.slideToggle(300); $(this).toggleClass('active');
            filterArrow.html($(this).hasClass('active') ? '▲' : '▼');
        });
        if ($('#products-container .product-item').length > 0 || popularWrap.find('.no-products-message-initial').length === 0) { applyProductSearchAndFilter(); }
        
        let initialMenuItemsCount = $('#menu-items-container .menu').length;
        let menuOffset = initialMenuItemsCount;
        const menuLimit = 8;
        $('#load-more-menu-btn').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).text('Загрузка...');
            $.ajax({
                url: 'load_more_menu.php', type: 'POST', data: { offset: menuOffset, limit: menuLimit }, dataType: 'json',
                success: function(response) {
                    if (response.html && response.html.trim() !== '') {
                        $('#menu-items-container').append(response.html);
                        const addedItemsCount = $(response.html).filter('.menu').length;
                        menuOffset += addedItemsCount; 
                    }
                    if (response.has_more) { button.prop('disabled', false).text('Еще...'); } 
                    else { button.hide(); }
                    if ($('#no-menu-message').length && $('#menu-items-container .menu').length > 0) { $('#no-menu-message').remove(); }
                },
                error: function() { toastr.error('Не удалось загрузить дополнительные пункты меню.'); button.prop('disabled', false).text('Еще...'); }
            });
        });

        let productsOffset = $('#products-container .product-item').length;
        const productsLimit = 12;
        $('#load-more-products-btn').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).text('Загрузка...');
            $.ajax({
                url: 'load_more_products.php', 
                type: 'POST',
                data: {
                    offset: productsOffset,
                    limit: productsLimit
                },
                dataType: 'json',
                success: function(response) {
                    if (response.html && response.html.trim() !== '') {
                        const $newItems = $(response.html);
                        $('#products-container').append($newItems);
                        $newItems.each(function() {
                            const productId = $(this).data('id');
                            const stockQuantity = parseInt($(this).data('stock'));
                            const cartQuantity = parseInt($(this).find('.product-count').text()) || 0;
                            updateProductStockStatus(productId, stockQuantity);
                            updateRemoveButtonState(productId, cartQuantity);
                        });
                        const addedItemsCount = $newItems.filter('.product-item').length;
                        productsOffset += addedItemsCount; 
                        applyProductSearchAndFilter();
                    }
                    if (response.has_more) {
                        button.prop('disabled', false).text('Еще...');
                    } else {
                        button.hide(); 
                    }
                    const initialNoProductsMsg = popularWrap.find('.no-products-message-initial');
                    if(initialNoProductsMsg.length > 0 && $('#products-container .product-item').length > 0){
                        initialNoProductsMsg.remove();
                    }
                },
                error: function(xhr) {
                    toastr.error('Не удалось загрузить дополнительные продукты.');
                    button.prop('disabled', false).text('Еще...');
                }
            });
        });
        $('#products-container .product-item').each(function() {
            const productId = $(this).data('id');
            const stockQuantity = parseInt($(this).data('stock'));
            const cartQuantity = parseInt($(this).find('.product-count').text()) || 0;
            updateProductStockStatus(productId, stockQuantity);
            updateRemoveButtonState(productId, cartQuantity);
        });
    }); 
    </script>
</body>
</html>