<?php
session_start();
require_once 'connect.php';

$login = $_POST['login'];
$password_input = $_POST['password']; // Пароль от пользователя

// Используем подготовленный запрос
$stmt = $connect->prepare("SELECT * FROM `user` WHERE `login` = ?");
if ($stmt) {
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Проверяем пароль. Так как вы использовали md5 при регистрации, сравниваем md5.
        // В будущем настоятельно рекомендуется перейти на password_hash() и password_verify().
        if (md5($password_input) === $user['password']) {
            // Пароль верный, устанавливаем сессию
            $session_data = [
                "id" => $user['id'],
                "name" => $user['first_name'], // Используем first_name для единообразия
                "first_name" => $user['first_name'],
                "last_name" => $user['last_name'],
                "login" => $user['login'],
                "email" => $user['email'],
                "avatar" => $user['avatar'], // Добавляем аватар в сессию
                "is_admin" => (bool)$user['is_admin'] // Приводим к boolean для удобства
            ];

            if ($session_data['is_admin']) {
                $_SESSION['user'] = $session_data; // Можно просто $_SESSION['user'] для админа тоже
                // $_SESSION['admin'] = $session_data; // Если вы хотите отдельную сессию для админа
                header('Location: ../admin/admin_dashboard.php');
            } else {
                $_SESSION['user'] = $session_data;
                header('Location: ../index.php');
            }
        } else {
            // Пароль неверный
            $_SESSION['message'] = 'Неверный логин или пароль';
            header('Location: ../auth/authorization.php'); // Возвращаем на страницу авторизации
        }
    } else {
        // Пользователь с таким логином не найден
        $_SESSION['message'] = 'Неверный логин или пароль';
        header('Location: ../auth/authorization.php'); // Возвращаем на страницу авторизации
    }
    $stmt->close();
} else {
    $_SESSION['message'] = 'Ошибка подготовки запроса к БД.';
    error_log('Signin prepare error: ' . $connect->error);
    header('Location: ../auth/authorization.php');
}
exit(); // Важно добавить exit после header
?>