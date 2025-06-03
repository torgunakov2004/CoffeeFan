<?php
session_start();
require_once 'connect.php';

ob_start();

// CSRF-защита
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Недействительный CSRF-токен";
    header('Location: ../auth/register.php');
    exit();
}

// Валидация данных
$required_fields = ['name', 'last_name', 'login', 'email', 'password', 'password_confirm', 'terms'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Все поля обязательны для заполнения";
        header('Location: ../auth/register.php');
        exit();
    }
}

// Очистка данных
$name = htmlspecialchars(trim($_POST['name']));
$last_name = htmlspecialchars(trim($_POST['last_name']));
$login = htmlspecialchars(trim($_POST['login']));
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Некорректный email";
    header('Location: ../auth/register.php');
    exit();
}

// Проверка паролей
if ($_POST['password'] !== $_POST['password_confirm']) {
    $_SESSION['error'] = "Пароли не совпадают";
    header('Location: ../auth/register.php');
    exit();
}

// Проверка сложности пароля
if (strlen($_POST['password']) < 8) {
    $_SESSION['error'] = "Пароль должен содержать минимум 8 символов";
    header('Location: ../auth/register.php');
    exit();
}

// Сохраняем введенные данные для повторного отображения
$_SESSION['old'] = [
    'name' => $name,
    'last_name' => $last_name,
    'login' => $login,
    'email' => $email
];

try {
    // Проверка уникальности логина и email
    $stmt = $connect->prepare("SELECT id FROM user WHERE login = ? OR email = ?");
    $stmt->bind_param("ss", $login, $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Логин или email уже заняты";
        header('Location: ../auth/register.php');
        exit();
    }

    // Хеширование пароля
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Подготовленный запрос для регистрации
    $stmt = $connect->prepare("INSERT INTO user (first_name, last_name, login, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $last_name, $login, $email, $password_hash);
    $stmt->execute();

    // Успешная регистрация
    $_SESSION['success'] = "Регистрация прошла успешно!";
    unset($_SESSION['old']); // Очищаем сохраненные данные
    header('Location: ../auth/authorization.php');
    
} catch (mysqli_sql_exception $e) {
    error_log("Ошибка регистрации: " . $e->getMessage());
    $_SESSION['error'] = "Произошла ошибка. Пожалуйста, попробуйте позже.";
    header('Location: ../auth/register.php');
} finally {
    ob_end_flush();
}
?>