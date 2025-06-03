<?php
session_start();
require_once '../config/connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8; // Значение по умолчанию

if ($page < 1) $page = 1;
if ($limit < 1) $limit = 8;

$offset = ($page - 1) * $limit;

$query = "SELECT * FROM `menu` ORDER BY `id` ASC LIMIT ? OFFSET ?";
$stmt = $connect->prepare($query);

if ($stmt) {
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '';
    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $html .= '<div class="menu">';
            $html .= '  <div class="menu__img-container">';
            $html .= '      <img class="menu__img" src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['title']) . '">';
            $html .= '  </div>';
            $html .= '  <div class="menu__content">';
            $html .= '      <h3 class="menu__title">' . htmlspecialchars($item['title']) . '</h3>';
            if (!empty($item['description'])) {
                 $html .= '  <p class="menu__description">' . htmlspecialchars($item['description']) . '</p>';
            }
            $html .= '      <b class="menu__price">' . htmlspecialchars($item['price']) . ' ₽</b>';
            $html .= '  </div>';
            $html .= '</div>';
        }
        echo json_encode(['status' => 'success', 'html' => $html]);
    } else {
        echo json_encode(['status' => 'nomore', 'message' => 'Больше нет элементов.']);
    }
    $stmt->close();
} else {
    error_log("Ошибка подготовки запроса для загрузки меню: " . $connect->error);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка сервера при загрузке меню.']);
}

mysqli_close($connect);
?>