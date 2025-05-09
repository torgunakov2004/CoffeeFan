// config/signin.php
<?php
session_start();
require_once 'connect.php';

$login = $_POST['login'];
$password = $_POST['password'];

$stmt = $connect->prepare("SELECT * FROM `user` WHERE `login` = ?");
if ($stmt) {
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (md5($password) === $user['password']) {
            if ($user['is_admin'] == 1) {
                $_SESSION['admin'] = [ // Сессия для админа
                    "id" => $user['id'],
                    "name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "login" => $user['login'],
                    "email" => $user['email'],
                    "avatar" => $user['avatar'], // Добавляем аватар для админа
                    "is_admin" => true
                ];
                 // Также создадим сессию user для унификации проверки на других страницах
                $_SESSION['user'] = [
                    "id" => $user['id'],
                    "first_name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "login" => $user['login'],
                    "email" => $user['email'],
                    "avatar" => $user['avatar'], // Добавляем аватар
                    "is_admin" => true,
                    "name" => $user['first_name'] // Для совместимости с кодом, где используется $_SESSION['user']['name']
                ];
                header('Location: ../admin/admin_dashboard.php');
                exit();
            } else {
                $_SESSION['user'] = [
                    "id" => $user['id'],
                    "first_name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "login" => $user['login'],
                    "email" => $user['email'],
                    "avatar" => $user['avatar'], // <--- ВОТ ЭТО НУЖНО ДОБАВИТЬ
                    "is_admin" => false, // Явно указываем, что не админ
                    "name" => $user['first_name'] // Для совместимости с кодом, где используется $_SESSION['user']['name']
                ];
                header('Location: ../index.php');
                exit();
            }
        } else {
            $_SESSION['message'] = 'Не верный логин или пароль';
            header('Location: ../auth/authorization.php'); // Редирект на обычную авторизацию
            exit();
        }
    } else {
        $_SESSION['message'] = 'Не верный логин или пароль';
        header('Location: ../auth/authorization.php'); // Редирект на обычную авторизацию
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['message'] = 'Ошибка подготовки запроса: ' . $connect->error;
    header('Location: ../auth/authorization.php');
    exit();
}
?>