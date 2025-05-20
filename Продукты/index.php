<?php
session_start();
require_once '../config/connect.php';

// Проверка, включено ли расширение mbstring
if (!function_exists('mb_strtolower')) {
    echo "<!-- FATAL ERROR: MBSTRING EXTENSION IS NOT ENABLED. PLEASE ENABLE IT IN PHP.INI -->";
    // Можно добавить die() или другую обработку, если mbstring критичен
}

// --- Получение данных для фильтра "Страна" ---
$unique_countries = [];
$countries_query_sql = "SELECT DISTINCT country_of_origin FROM products WHERE country_of_origin IS NOT NULL AND country_of_origin != '' ORDER BY country_of_origin ASC";
$countries_query = mysqli_query($connect, $countries_query_sql);

// echo "<!-- Debug: SQL for Countries: " . htmlspecialchars($countries_query_sql) . " -->\n";
if (!$countries_query) {
    // echo "<!-- Debug: Error in countries_query: " . mysqli_error($connect) . " -->\n";
}

// echo "<!-- Debugging unique_countries: START -->\n";
if ($countries_query) {
    while ($row = mysqli_fetch_assoc($countries_query)) {
        // echo "<!-- Debug: Row Country from DB: '" . htmlspecialchars($row['country_of_origin']) . "' -->\n";
        $unique_countries[] = $row['country_of_origin'];
    }
}
// echo "<!-- Debugging unique_countries: END -->\n";

// --- Получение данных для фильтра "Вес" ---
$unique_weights = [];
$weights_query_sql = "SELECT DISTINCT weight_grams FROM products WHERE weight_grams IS NOT NULL AND weight_grams > 0 ORDER BY weight_grams ASC";
$weights_query = mysqli_query($connect, $weights_query_sql);
// echo "<!-- Debug: SQL for Weights: " . htmlspecialchars($weights_query_sql) . " -->\n";
if ($weights_query) {
    while ($row = mysqli_fetch_assoc($weights_query)) {
        $unique_weights[] = $row['weight_grams'];
    }
}

// --- Получение данных для фильтра "Обжарка" ---
$unique_roast_levels = [];
$roast_query_sql = "SELECT DISTINCT roast_level FROM products WHERE roast_level IS NOT NULL AND roast_level != '' ORDER BY roast_level ASC";
$roast_query = mysqli_query($connect, $roast_query_sql);
// echo "<!-- Debug: SQL for Roast Levels: " . htmlspecialchars($roast_query_sql) . " -->\n";
if (!$roast_query) {
    // echo "<!-- Debug: Error in roast_query: " . mysqli_error($connect) . " -->\n";
}
// echo "<!-- Debugging unique_roast_levels: START -->\n";
if ($roast_query) {
    while ($row = mysqli_fetch_assoc($roast_query)) {
        // echo "<!-- Debug: Row Roast Level from DB: '" . htmlspecialchars($row['roast_level']) . "' -->\n";
        $unique_roast_levels[] = $row['roast_level'];
    }
}
// echo "<!-- Debugging unique_roast_levels: END -->\n";

// --- Получение данных для фильтра "Метод обработки" ---
$unique_processing_methods = [];
$check_column_query = mysqli_query($connect, "SHOW COLUMNS FROM `products` LIKE 'processing_method'");
if ($check_column_query && $check_column_query->num_rows == 1) {
    $processing_query_sql = "SELECT DISTINCT processing_method FROM products WHERE processing_method IS NOT NULL AND processing_method != '' ORDER BY processing_method ASC";
    $processing_query = mysqli_query($connect, $processing_query_sql);
    // echo "<!-- Debug: SQL for Processing Methods: " . htmlspecialchars($processing_query_sql) . " -->\n";
    if (!$processing_query) {
        // echo "<!-- Debug: Error in processing_query: " . mysqli_error($connect) . " -->\n";
    }
    // echo "<!-- Debugging unique_processing_methods: START -->\n";
    if ($processing_query) {
        while ($row = mysqli_fetch_assoc($processing_query)) {
            // echo "<!-- Debug: Row Processing Method from DB: '" . htmlspecialchars($row['processing_method']) . "' -->\n";
            $unique_processing_methods[] = $row['processing_method'];
        }
    }
    // echo "<!-- Debugging unique_processing_methods: END -->\n";
} else {
    // echo "<!-- Debug: Column 'processing_method' not found in 'products' table or error checking. -->\n";
}

$products_query_string = "SELECT * FROM `products`";
$products_result = mysqli_query($connect, $products_query_string);
if (!$products_result) {
    error_log("Ошибка выполнения запроса продуктов: " . mysqli_error($connect));
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

// --- Получение пунктов меню для начальной загрузки ---
$initial_menu_limit = 8;
$menu_query_sql = "SELECT * FROM `menu` ORDER BY `id` ASC LIMIT ?"; // Загружаем первые 8, отсортированные по ID (или по другому полю, если нужно)
$stmt_menu = $connect->prepare($menu_query_sql);
$menuItems = null; // Инициализируем переменную
$totalMenuItems = 0; // Общее количество пунктов меню

if ($stmt_menu) {
    $stmt_menu->bind_param("i", $initial_menu_limit);
    $stmt_menu->execute();
    $menuItems = $stmt_menu->get_result();
    if (!$menuItems) {
        error_log("Ошибка выполнения запроса меню (начальная загрузка): " . $connect->error);
    }
    $stmt_menu->close();

    // Получаем общее количество пунктов меню для определения, нужно ли показывать кнопку "Еще"
    $total_menu_query = mysqli_query($connect, "SELECT COUNT(*) as total FROM `menu`");
    if ($total_menu_query) {
        $total_row = mysqli_fetch_assoc($total_menu_query);
        $totalMenuItems = (int)$total_row['total'];
    }
} else {
    error_log("Ошибка подготовки запроса меню: " . $connect->error);
}

$reviews_query_sql = "SELECT r.*, u.avatar as user_avatar_path 
                      FROM `reviews` r 
                      LEFT JOIN `user` u ON r.user_id = u.id 
                      WHERE r.`status` = 'approved' 
                      ORDER BY r.`id` DESC 
                      LIMIT 3";
$reviews = mysqli_query($connect, $reviews_query_sql);

if (!$reviews) {
    error_log("Ошибка выполнения запроса отзывов на странице Продукты: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffeee shop - Продукты</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <?php
        $current_page_is_faq = true; 
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
                                            <?php
                                                $processed_country_value = function_exists('mb_strtolower') ? mb_strtolower(trim($country_value), 'UTF-8') : strtolower(trim($country_value));
                                            ?>
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
                                            <?php
                                                $processed_roast_value = function_exists('mb_strtolower') ? mb_strtolower(trim($roast_value), 'UTF-8') : strtolower(trim($roast_value));
                                            ?>
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
                                        <?php
                                            $processed_method_value = function_exists('mb_strtolower') ? mb_strtolower(trim($method_value), 'UTF-8') : strtolower(trim($method_value));
                                        ?>
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

                <div class="popular-wrap">
                    <?php if ($products_result && mysqli_num_rows($products_result) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                            <?php
                                $use_mb = function_exists('mb_strtolower');

                                $title_val = $product['title'] ?? '';
                                $country_val = $product['country_of_origin'] ?? '';
                                $roast_val = $product['roast_level'] ?? '';
                                $processing_val = $product['processing_method'] ?? '';

                                $data_title = htmlspecialchars($use_mb ? mb_strtolower(trim($title_val), 'UTF-8') : strtolower(trim($title_val)));
                                $data_country = htmlspecialchars($use_mb ? mb_strtolower(trim($country_val), 'UTF-8') : strtolower(trim($country_val)));
                                $data_roast = htmlspecialchars($use_mb ? mb_strtolower(trim($roast_val), 'UTF-8') : strtolower(trim($roast_val)));
                                $data_processing = htmlspecialchars($use_mb ? mb_strtolower(trim($processing_val), 'UTF-8') : strtolower(trim($processing_val)));
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
                                            <?php if (!empty($product['country_of_origin'])): ?>
                                                <p><strong>Страна:</strong> <?= htmlspecialchars($product['country_of_origin']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($product['weight_grams'])): ?>
                                                <p><strong>Вес:</strong> <?= htmlspecialchars($product['weight_grams']) ?> г</p>
                                            <?php endif; ?>
                                            <?php if (!empty($product['roast_level'])): ?>
                                                <p><strong>Обжарка:</strong> <?= htmlspecialchars($product['roast_level']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($product['processing_method'])): ?>
                                                <p><strong>Обработка:</strong> <?= htmlspecialchars($product['processing_method']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <h3 class="popular__title-display"><?= htmlspecialchars($product['title'] ?? 'Название товара') ?></h3>
                                <b class="popular__price">Цена - <strong class="popular__price_dark"><?= htmlspecialchars($product['price']) ?>₽/</strong> <?= htmlspecialchars(floatval($product['price']) + 30) ?>₽</b>
                                <div class="popular__actions">
                                    <?php if (isset($_SESSION['user'])): ?>
                                        <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                            <span class="material-icons-outlined">local_mall</span>Добавить
                                        </button>
                                        <button class="remove-from-cart-btn" data-product-id="<?= $product['id'] ?>">
                                            <span class="material-icons-outlined">remove_shopping_cart</span>Убрать
                                        </button>
                                        <span class="product-count" id="count-<?= $product['id'] ?>">
                                            <?= $cart_quantities[$product['id']] ?? 0 ?>
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
            </div>
        </section>

        <!-- Остальной HTML (меню, отзывы, футер) без изменений -->
        <section id="menu-section" class="section-main">
            <div class="container">
                <h3 class="section-subtitle">Меню кофейни</h3>
                <div class="menu-wrap" id="menu-items-container"> <?php // Добавлен ID ?>
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
                        <p class="no-products-message" id="no-menu-message">Пункты меню не найдены.</p> <?php // Добавлен ID ?>
                    <?php endif; ?>
                </div> <?php // Конец menu-wrap ?>

                <?php if ($totalMenuItems > $initial_menu_limit): // Показываем кнопку, только если есть еще пункты для загрузки ?>
                <div class="load-more-container" style="text-align: center; margin-top: 30px;">
                    <button id="load-more-menu-btn" class="btn-primary">Еще...</button>
                </div>
                <?php endif; ?>
                </div>
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
                                        if (file_exists($path_to_user_avatar_from_script_prod)) {
                                            $avatar_to_display_src_prod = htmlspecialchars($path_to_user_avatar_from_script_prod);
                                            $display_initials_prod = false;
                                        }
                                    }
                                    if ($display_initials_prod) {
                                        $initial_prod = '';
                                        if (!empty($review['name'])) {
                                            if (function_exists('mb_strtoupper') && function_exists('mb_substr')) {
                                                $initial_prod = htmlspecialchars(mb_strtoupper(mb_substr($review['name'], 0, 1, 'UTF-8')));
                                            } elseif (function_exists('strtoupper') && function_exists('substr')) {
                                                $initial_prod = htmlspecialchars(strtoupper(substr($review['name'], 0, 1)));
                                            }
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
        // --- Логика корзины ---
        $('.add-to-cart-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var countElement = $('#count-' + productId);
            $.ajax({
                type: 'POST',
                url: 'add_to_cart.php', // Помним, что логика тут перепутана с remove
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if(countElement.length) {
                            countElement.text(response.quantity);
                        }
                        toastr.success(response.message || 'Товар добавлен в корзину');
                        $('.header-action__cart-1').addClass('active');
                    } else {
                        toastr.error(response.message || 'Не удалось добавить товар.');
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка AJAX (Продукты, "добавление" по факту уменьшение):', xhr.responseText);
                    toastr.error('Ошибка сервера при "добавлении" товара.');
                }
            });
        });

        $('.remove-from-cart-btn').on('click', function() {
            var productId = $(this).data('product-id');
            var countElement = $('#count-' + productId);
            $.ajax({
                type: 'POST',
                url: 'remove_from_cart.php', // Помним, что логика тут перепутана с add
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if(countElement.length) {
                            countElement.text(response.quantity);
                        }
                        toastr.success(response.message || 'Количество товара обновлено');
                        
                        if (response.cart_empty === true || (response.quantity === 0 && checkAllProductPageCartCountsZero())) {
                                $('.header-action__cart-1').removeClass('active');
                        } else {
                                $('.header-action__cart-1').addClass('active');
                        }
                    } else {
                        toastr.error(response.message || 'Не удалось обновить товар.');
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка AJAX (Продукты, "удаление" по факту добавление):', xhr.responseText);
                    toastr.error('Ошибка сервера при "удалении" товара.');
                }
            });
        });

        function checkAllProductPageCartCountsZero() {
            var allZero = true;
            $('.product-count').each(function() { 
                if (parseInt($(this).text()) > 0) {
                    allZero = false;
                    return false; 
                }
            });
            return allZero;
        }

        // --- Переворот карточки товара ---
        $('.popular__flick-container').on('click', function() {
            $(this).closest('.popular').toggleClass('info-visible');
        });
        
        // --- Фильтрация и поиск товаров ---
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

        const productItemsList = $('.popular-wrap .product-item');
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
            if (priceFrom > priceTo && priceTo !== Infinity) {
                [priceFrom, priceTo] = [priceTo, priceFrom];
            }

            // Отладочные логи (можно закомментировать после проверки)
            // console.log("--- APPLYING FILTERS ---");
            // console.log("Search Term:", "'" + searchTerm + "'");
            // console.log("Filter Type:", "'" + filterTypeValue + "'");
            // console.log("Price From:", priceFrom, "Price To:", priceTo);
            // console.log("Selected Country:", "'" + selectedCountry + "'");
            // console.log("Selected Weight:", "'" + selectedWeight + "'");
            // console.log("Selected Roast:", "'" + selectedRoast + "'");
            // console.log("Selected Processing:", "'" + selectedProcessing + "'");

            let visibleProductsCount = 0;
            productItemsList.each(function(index) {
                const productCard = $(this);
                const productId = productCard.data('id');
                const title = productCard.data('title').toString(); 
                const isPopular = productCard.data('popular').toString();
                const productPrice = parseFloat(productCard.data('price'));
                const productCountry = productCard.data('country').toString(); 
                const productWeight = productCard.data('weight').toString();   
                const productRoast = productCard.data('roast').toString();    
                const productProcessing = productCard.data('processing').toString(); 

                // if(index < 1) { // Логируем только для первой карточки
                //     console.log(`Product ID ${productId} ('${title}'):`);
                //     console.log(`  Data-Country: '${productCountry}', Data-Roast: '${productRoast}', Data-Processing: '${productProcessing}'`);
                // }

                let matchesSearch = searchTerm === "" || title.includes(searchTerm);
                let matchesFilterType = filterTypeValue === 'all' ||
                                        (filterTypeValue === 'popular' && isPopular === '1') ||
                                        (filterTypeValue === 'not_popular' && isPopular === '0');
                let matchesPrice = !isNaN(productPrice) && (productPrice >= priceFrom && productPrice <= priceTo);
                
                let matchesCountry = selectedCountry === 'all' || productCountry === selectedCountry;
                let matchesWeight = selectedWeight === 'all' || productWeight === selectedWeight;
                let matchesRoast = selectedRoast === 'all' || productRoast === selectedRoast;
                let matchesProcessing = selectedProcessing === 'all' || productProcessing === selectedProcessing;

                // if(index < 1) { // Логируем результат сравнения для первой карточки
                //     console.log(`  Match Country: ${matchesCountry} (Comparing '${productCountry}' with '${selectedCountry}')`);
                //     console.log(`  Match Roast: ${matchesRoast} (Comparing '${productRoast}' with '${selectedRoast}')`);
                //     console.log(`  Match Processing: ${matchesProcessing} (Comparing '${productProcessing}' with '${selectedProcessing}')`);
                // }

                if (matchesSearch && matchesFilterType && matchesPrice && matchesCountry && matchesWeight && matchesRoast && matchesProcessing) {
                    productCard.show();
                    visibleProductsCount++;
                } else {
                    productCard.hide();
                }
            });

            // console.log("Visible products count:", visibleProductsCount);

            const jsNoProductsMessage = popularWrap.find('.no-products-message-js');
            const initialNoProductsMessage = popularWrap.find('.no-products-message-initial');

            if (productItemsList.length === 0 && initialNoProductsMessage.length > 0) {
                // Если изначально нет товаров (сообщение от PHP), ничего не делаем с JS сообщением
            } else {
                if (visibleProductsCount === 0) {
                    if (jsNoProductsMessage.length === 0) {
                        popularWrap.append('<p class="no-products-message no-products-message-js">Товары по вашему запросу не найдены.</p>');
                    }
                } else {
                    jsNoProductsMessage.remove();
                }
            }
        }
        
        const filterControls = [
            searchProductInput, 
            productFilterTypeSelect, 
            priceFromInput, 
            priceToInput, 
            filterCountrySelect,
            filterWeightSelect,
            filterRoastSelect
        ];
        if (filterProcessingSelect.length) {
            filterControls.push(filterProcessingSelect);
        }

        filterControls.forEach(control => {
            control.on('input change', applyProductSearchAndFilter);
        });

        resetFiltersBtn.on('click', function() {
            searchProductInput.val('');
            productFilterTypeSelect.val('all');
            priceFromInput.val('');
            priceToInput.val('');
            filterCountrySelect.val('all');
            filterWeightSelect.val('all');
            filterRoastSelect.val('all');
            if (filterProcessingSelect.length) {
                filterProcessingSelect.val('all');
            }
            applyProductSearchAndFilter();
        });

        toggleFiltersBtn.on('click', function() {
            advancedFiltersPanel.slideToggle(300);
            $(this).toggleClass('active');
            if ($(this).hasClass('active')) {
                filterArrow.html('▲');
            } else {
                filterArrow.html('▼');
            }
        });
        
        // Первоначальное применение фильтров при загрузке страницы
        if (productItemsList.length > 0 || popularWrap.find('.no-products-message-initial').length === 0) {
            applyProductSearchAndFilter(); 
        }

        // --- Логика для "Загрузить еще" меню ---
        // PHP должен передать $initial_menu_limit в JavaScript.
        // Предположим, что PHP вставил это значение в data-атрибут кнопки или в скрытое поле.
        // Для простоты, если вы не передали это значение из PHP, установим его здесь вручную,
        // но лучше передавать из PHP, чтобы JS знал, сколько уже загружено.
        // В PHP коде мы использовали переменную $initial_menu_limit для отображения кнопки,
        // так что если кнопка есть, значит $initial_menu_limit известно.
        // Можно также посчитать количество .menu элементов при загрузке страницы.

        let initialMenuItemsCount = $('#menu-items-container .menu').length;
        let menuOffset = initialMenuItemsCount;
        const menuLimit = 8; // Сколько загружать за раз

        $('#load-more-menu-btn').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).text('Загрузка...');

            $.ajax({
                url: 'load_more_menu.php', // Убедитесь, что путь правильный
                type: 'POST',
                data: {
                    offset: menuOffset,
                    limit: menuLimit
                },
                dataType: 'json',
                success: function(response) {
                    if (response.html && response.html.trim() !== '') {
                        $('#menu-items-container').append(response.html);
                        // Считаем, сколько реально было добавлено карточек (на случай если вернулось меньше, чем limit)
                        const addedItemsCount = $(response.html).filter('.menu').length;
                        menuOffset += addedItemsCount; 
                    }

                    if (response.has_more) {
                        button.prop('disabled', false).text('Еще...');
                    } else {
                        button.hide(); // Скрываем кнопку, если больше нет элементов
                    }
                    
                    // Если изначально не было пунктов меню и мы что-то загрузили, убираем сообщение
                    if ($('#no-menu-message').length && $('#menu-items-container .menu').length > 0) {
                        $('#no-menu-message').remove();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка загрузки меню:", error);
                    console.error("XHR Status:", status);
                    console.error("XHR Response:", xhr.responseText);
                    toastr.error('Не удалось загрузить дополнительные пункты меню.');
                    button.prop('disabled', false).text('Еще...');
                }
            });
        });

    }); // Конец $(document).ready
    </script>
</body>
</html>