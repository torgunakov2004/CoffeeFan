<?php
// Устанавливаем временную зону по умолчанию для всех функций даты/времени PHP на UTC
date_default_timezone_set('UTC');

$db_host = 'localhost';
$db_user = 'root'; // Замените на ваше имя пользователя БД, если оно другое
$db_pass = '';     // Замените на ваш пароль БД, если он есть
$db_name = 'profile';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Включаем режим сообщений об ошибках

try {
    $connect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    // mysqli_connect не выбрасывает исключение сам по себе при ошибке подключения,
    // он возвращает false и генерирует E_WARNING.
    // Проверка $connect обязательна.
    if (!$connect) {
        // mysqli_connect_error() возвращает описание ошибки
        throw new Exception("Ошибка подключения к базе данных: " . mysqli_connect_error());
    }

    mysqli_set_charset($connect, "utf8mb4");

    // Устанавливаем часовой пояс для текущей сессии MySQL на UTC
    if (!mysqli_query($connect, "SET time_zone = '+00:00'")) {
        throw new Exception("Ошибка установки часового пояса для сессии MySQL: " . mysqli_error($connect));
    }
    
} catch (Exception $e) {
    error_log("Ошибка в config/connect.php: " . $e->getMessage());
    // На публичном сайте лучше не выводить die() с деталями ошибки,
    // а показать дружелюбное сообщение или перенаправить на страницу ошибки.
    // Для локальной разработки die() может быть полезен.
    die('Произошла критическая ошибка конфигурации сайта. Пожалуйста, попробуйте позже или обратитесь в поддержку.');
}


// Вспомогательная функция для отображения даты в таймзоне пользователя
function display_date_in_user_timezone($date_string_utc, $default_timezone = 'Asia/Irkutsk') {
    if (empty($date_string_utc) || $date_string_utc === '0000-00-00 00:00:00') {
        return 'N/A';
    }

    $target_timezone_str = $default_timezone;

    // 1. Пытаемся взять из сессии пользователя (если он зарегистрирован и установил в профиле)
    //    Этот блок оставляем на случай, если вы решите добавить настройку в профиле
    if (isset($_SESSION['user']['timezone']) && !empty($_SESSION['user']['timezone'])) {
        if (in_array($_SESSION['user']['timezone'], timezone_identifiers_list())) {
            $target_timezone_str = $_SESSION['user']['timezone'];
        }
    } 
    // 2. Пытаемся взять из сессии (куда мог записать JS)
    elseif (isset($_SESSION['user_timezone_js']) && !empty($_SESSION['user_timezone_js'])) { // Используем другое имя для сессии от JS
        if (in_array($_SESSION['user_timezone_js'], timezone_identifiers_list())) {
            $target_timezone_str = $_SESSION['user_timezone_js'];
        }
    }
    // 3. Если ничего нет, используем $default_timezone (Иркутск)

    try {
        $datetime_utc = new DateTime($date_string_utc, new DateTimeZone('UTC'));
        $target_timezone_obj = new DateTimeZone($target_timezone_str);
        $datetime_user_tz = $datetime_utc->setTimezone($target_timezone_obj);
        return $datetime_user_tz->format('d.m.Y H:i');
    } catch (Exception $e) {
        error_log("Timezone conversion error: " . $e->getMessage() . " for date " . $date_string_utc . " and tz " . $target_timezone_str);
        // Возвращаем дату, отформатированную в UTC или как есть, если конвертация не удалась
        return date('d.m.Y H:i (UTC)', strtotime($date_string_utc)); 
    }
}
?>