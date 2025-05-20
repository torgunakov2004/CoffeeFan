<?php
session_start();
require_once 'connect.php';

ob_start();

// CSRF-защита
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Недействительный CSRF-токен";
    header('Location: ../auth/authorization.php');
    exit();
}

// Базовые проверки
if (empty($_POST['login']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Заполните все поля";
    header('Location: ../auth/authorization.php');
    exit();
}

// Сохраняем логин для повторного отображения
$_SESSION['old'] = ['login' => $_POST['login']];

try {
    $stmt = $connect->prepare("SELECT * FROM user WHERE login = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $_POST['login'], $_POST['login']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Неверный логин/email или пароль";
        header('Location: ../auth/authorization.php');
        exit();
    }

    $user = $result->fetch_assoc();

    // Проверка пароля
    if (!password_verify($_POST['password'], $user['password'])) {
        $_SESSION['error'] = "Неверный логин/email или пароль";
        header('Location: ../auth/authorization.php');
        exit();
    }

    // Создание сессии
    $_SESSION['user'] = [
        "id" => $user['id'],
        "name" => htmlspecialchars($user['first_name']),
        "last_name" => htmlspecialchars($user['last_name']),
        "login" => htmlspecialchars($user['login']),
        "email" => htmlspecialchars($user['email']),
        "avatar" => $user['avatar'],
        "is_admin" => (bool)$user['is_admin']
    ];

    unset($_SESSION['old']);

    // Редирект
    if ($user['is_admin'] == 1) {
        $_SESSION['admin'] = $_SESSION['user'];
        header('Location: ../admin/admin_dashboard.php');
    } else {
        header('Location: ../index.php');
    }

} catch (mysqli_sql_exception $e) {
    error_log("Ошибка авторизации: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка сервера. Пожалуйста, попробуйте позже.";
    header('Location: ../auth/authorization.php');
} finally {
    ob_end_flush();
}
?>