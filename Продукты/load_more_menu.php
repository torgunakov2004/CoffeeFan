<?php
require_once '../config/connect.php'; // Путь к файлу connect.php

$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 8; // Количество загружаемых за раз

$query_sql = "SELECT * FROM `menu` ORDER BY `id` ASC LIMIT ? OFFSET ?";
$stmt = $connect->prepare($query_sql);
$html = '';
$has_more = false;

if ($stmt) {
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $html .= '<div class="menu">';
            $html .= '  <div class="menu__img-container">';
            $image_path_from_root = ltrim($item['image'], '/'); // Убираем возможный ведущий слэш
            $html .= '      <img class="menu__img" src="../' . htmlspecialchars($image_path_from_root) . '" alt="' . htmlspecialchars($item['title']) . '">';
            $html .= '  </div>';
            $html .= '  <div class="menu__content">';
            $html .= '      <h3 class="menu__title">' . htmlspecialchars($item['title']) . '</h3>';
            $html .= '      <b class="menu__price">' . htmlspecialchars($item['price']) . ' ₽</b>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        // Проверяем, есть ли еще пункты меню для загрузки
        $total_after_load_query = $connect->prepare("SELECT COUNT(*) as total FROM `menu` WHERE id > ? ORDER BY id ASC");
        if ($total_after_load_query) {
            // Найдем максимальный ID из загруженных сейчас
            $last_id = 0;
            $result->data_seek($result->num_rows - 1); // Перемещаемся к последнему элементу
            $last_item_row = $result->fetch_assoc();
            if($last_item_row) {
                $last_id = $last_item_row['id'];
            }

            $total_after_load_query->bind_param("i", $last_id);
            $total_after_load_query->execute();
            $total_after_result = $total_after_load_query->get_result();
            $total_after_row = $total_after_result->fetch_assoc();
            if ($total_after_row && $total_after_row['total'] > 0) {
                $has_more = true;
            }
            $total_after_load_query->close();
        }


    }
    $stmt->close();
} else {
    error_log("Ошибка подготовки запроса load_more_menu: " . $connect->error);
    // Можно вернуть ошибку в JSON, если нужно
}

echo json_encode(['html' => $html, 'has_more' => $has_more]);
?>