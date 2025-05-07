<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    mysqli_query($connect, "UPDATE `user` SET `first_name` = '$first_name', `last_name` = '$last_name', `login` = '$login', `email` = '$email', `is_admin` = '$is_admin' WHERE `id` = '$id'");
    header('Location: admin_dashboard.php');
}

$id = $_GET['id'];
$user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `user` WHERE `id` = '$id'"));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пользователя</title>
</head>
<body>
    <h1>Редактировать пользователя</h1>
    <form action="" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <label>Имя</label>
        <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" required>
        <label>Фамилия</label>
        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" required>
        <label>Логин</label>
        <input type="text" name="login" value="<?php echo $user['login']; ?>" required>
        <label>Email</label>
        <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
        <label>Администратор</label>
        <input type="checkbox" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
        <button type="submit">Сохранить</button>
    </form>
    <a href="admin_dashboard.php">Назад</a>
</body>
</html>