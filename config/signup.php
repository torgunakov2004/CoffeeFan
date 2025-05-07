<?php
session_start();
require_once 'connect.php';

$name = $_POST['name'];
$last_name = $_POST['last_name'];
$login = $_POST['login'];
$email = $_POST['email'];
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

// Путь к аватару по умолчанию для новых пользователей
// Убедитесь, что этот файл существует в папке img на один уровень выше папки config
// Или используйте NULL, если хотите, чтобы аватар был не задан
$default_avatar = '../img/default-avatar.jpg'; // ИЛИ $default_avatar = NULL;

if ($password === $password_confirm) {
    $password = md5($password); // Все еще не рекомендуется, но оставляем как есть для совместимости

    // Подготовка запроса для безопасности
    $stmt = $connect->prepare("INSERT INTO `user` (`first_name`, `last_name`, `login`, `email`, `password`, `avatar`) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssss", $name, $last_name, $login, $email, $password, $default_avatar);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Регистрация прошла успешно!';
            header('Location: ../auth/authorization.php');
        } else {
            // Более детальная ошибка для отладки (можно убрать на продакшене)
            $_SESSION['message'] = 'Ошибка при регистрации: ' . $stmt->error;
            error_log('Signup error: ' . $stmt->error); // Логируем ошибку
            header('Location: ../auth/register.php');
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Ошибка подготовки запроса к БД.';
        error_log('Signup prepare error: ' . $connect->error); // Логируем ошибку
        header('Location: ../auth/register.php');
    }
} else {
    $_SESSION['message'] = 'Пароли не совпадают';
    header('Location: ../auth/register.php');
}
exit(); // Важно добавить exit после header
?>