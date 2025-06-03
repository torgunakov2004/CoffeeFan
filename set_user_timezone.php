<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timezone'])) {
    $ianaTimezone = trim($_POST['timezone']);
    $response = ['status' => 'error', 'message' => 'Недопустимый часовой пояс.'];

    // Валидация полученной таймзоны
    if (in_array($ianaTimezone, timezone_identifiers_list())) {
        $_SESSION['user_timezone_js'] = $ianaTimezone; // Используем другое имя сессии
        $response = ['status' => 'success', 'timezone_set' => $ianaTimezone];
    } else {
        error_log("Invalid timezone received from client: " . $ianaTimezone);
        // Можно не отправлять ошибку клиенту, чтобы не беспокоить,
        // просто в сессии останется старое значение или будет использован дефолт
    }
    echo json_encode($response);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Некорректный запрос.']);
}
exit();
?>