<?php
session_start();
require_once '../config/connect.php';

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) { return strtolower($string); }
    function mb_strtoupper($string, $encoding = null) { return strtoupper($string); }
    function mb_substr($string, $start, $length = null, $encoding = null) { return substr($string, $start, $length); }
}

$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 12;

$html = '';
$has_more_products = false;
$cart_quantities_load = [];

if (isset($_SESSION['user']['id'])) {
    $user_id_load = $_SESSION['user']['id'];
    $query_cart_load = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart_load = $connect->prepare($query_cart_load);
    if ($stmt_cart_load) {
        $stmt_cart_load->bind_param("i", $user_id_load);
        $stmt_cart_load->execute();
        $result_cart_load = $stmt_cart_load->get_result();
        while ($row_cart_load = $result_cart_load->fetch_assoc()) {
            $cart_quantities_load[$row_cart_load['product_id']] = $row_cart_load['quantity'];
        }
        $stmt_cart_load->close();
    }
}

$sql_load_products = "SELECT * FROM `products` ORDER BY `id` DESC LIMIT ? OFFSET ?";
$stmt_load = $connect->prepare($sql_load_products);

if ($stmt_load) {
    $stmt_load->bind_param("ii", $limit, $offset);
    $stmt_load->execute();
    $result_load = $stmt_load->get_result();

    if ($result_load && $result_load->num_rows > 0) {
        while ($product = $result_load->fetch_assoc()) {
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
            $current_product_in_cart_qty_load = $cart_quantities_load[$product['id']] ?? 0;

            $html .= '<div class="popular product-item"
                        data-id="'. $product['id'] .'"
                        data-title="'. $data_title .'"
                        data-popular="'. ($product['is_popular'] ?? '0') .'"
                        data-price="'. floatval($product['price']) .'"
                        data-country="'. $data_country .'"
                        data-weight="'. intval($product['weight_grams'] ?? 0) .'"
                        data-roast="'. $data_roast .'"
                        data-processing="'. $data_processing .'"
                        data-stock="'. $stock_quantity .'">';
            $html .= '<div class="popular__flick-container">';
            $html .= '<div class="popular__content-wrapper">';
            $html .= '<div class="popular__image-side">';
            $html .= '<img class="popular__img" src="../' . htmlspecialchars(ltrim($product['image'], "/")) . '" alt="' . htmlspecialchars($product['title'] ?? 'Товар') . '">';
            $html .= '</div>';
            $html .= '<div class="popular__info-side">';
            $html .= '<h4>Состав:</h4><p>' . nl2br(htmlspecialchars($product['composition'] ?? 'Информация о составе отсутствует.')) . '</p>';
            $html .= '<h4>Особенности:</h4><p>' . nl2br(htmlspecialchars($product['features'] ?? 'Информация об особенностях отсутствует.')) . '</p>';
            if (!empty($product['country_of_origin'])) $html .= '<p><strong>Страна:</strong> '. htmlspecialchars($product['country_of_origin']) .'</p>';
            if (!empty($product['weight_grams'])) $html .= '<p><strong>Вес:</strong> '. htmlspecialchars($product['weight_grams']) .' г</p>';
            if (!empty($product['roast_level'])) $html .= '<p><strong>Обжарка:</strong> '. htmlspecialchars($product['roast_level']) .'</p>';
            if (!empty($product['processing_method'])) $html .= '<p><strong>Обработка:</strong> '. htmlspecialchars($product['processing_method']) .'</p>';
            $html .= '</div></div></div>';
            $html .= '<h3 class="popular__title-display">' . htmlspecialchars($product['title'] ?? 'Название товара') . '</h3>';
            $html .= '<b class="popular__price">Цена - <strong class="popular__price_dark">' . htmlspecialchars($product['price']) . '₽/</strong> ' . htmlspecialchars(floatval($product['price']) + 30) . '₽</b>';
            $html .= '<div class="product-stock-status" data-product-id-stock-status="'. $product['id'] .'">' . htmlspecialchars($stock_status_text) . '</div>';
            $html .= '<div class="popular__actions">';
            if (isset($_SESSION['user'])) {
                $disabled_add = $is_out_of_stock ? 'disabled' : '';
                $disabled_remove = $current_product_in_cart_qty_load <= 0 ? 'disabled' : '';
                $html .= '<button class="add-to-cart-btn" data-product-id="'. $product['id'] .'" '. $disabled_add .'><span class="material-icons-outlined">local_mall</span>Добавить</button>';
                $html .= '<button class="remove-from-cart-btn" data-product-id="'. $product['id'] .'" '. $disabled_remove .'><span class="material-icons-outlined">remove_shopping_cart</span>Убрать</button>';
                $html .= '<span class="product-count" id="count-'. $product['id'] .'">'. $current_product_in_cart_qty_load .'</span>';
            } else {
                $html .= '<p class="auth-message">Авторизуйтесь, чтобы добавить товар в корзину.</p>';
            }
            $html .= '</div></div>';
        }

        $total_products_after_load_query = $connect->prepare("SELECT COUNT(*) as total FROM `products`");
        if ($total_products_after_load_query) {
            $total_products_after_load_query->execute();
            $total_after_result = $total_products_after_load_query->get_result();
            $total_after_row = $total_after_result->fetch_assoc();
            if ($total_after_row && (int)$total_after_row['total'] > ($offset + $result_load->num_rows)) {
                $has_more_products = true;
            }
            $total_products_after_load_query->close();
        }
    }
    $stmt_load->close();
} else {
    error_log("Load More Products: Failed to prepare product load query: " . $connect->error);
}

echo json_encode(['html' => $html, 'has_more' => $has_more_products]);
?>