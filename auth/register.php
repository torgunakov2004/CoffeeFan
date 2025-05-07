<?php
    session_start();

    if (!empty($_SESSION["user"])) {
        header("Location: ../index.php");
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>ExoProduct|Регистрация</title>
    <link rel="stylesheet" href="../auth.css?<?echo time();?>">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
    </header>
    <main>
        <form action="../config/signup.php" method="post" >
            <label>Имя</label>
            <input type="text" name="name" placeholder="Введите ваше имя">
            <label>Фамилия</label>
            <input type="text" name="last_name" placeholder="Введите вашу фамилию">
            <label>Логин</label>
            <input type="text" name="login" placeholder="Введите логин">
            <label>Почта</label>
            <input type="email" name="email" placeholder="Введите вашу почту">
            <label>Пароль</label>
            <input type="password" name="password" placeholder="Введите пароль">
            <label>Подтвердите пароль</label>
            <input type="password" name="password_confirm" placeholder="Подтвердите пароль">
            <button type="submit">Зарегистрироваться</button>
            <p>
                У вас уже есть аккаунт? - <a href="authorization.php">авторизуйтесь</a>!
            </p>
            <?php 
                if (!empty($_SESSION['message'])) {
                    echo '<p class="msg"> ' . $_SESSION['message'] . ' </p>';
                }
                unset($_SESSION['message']);
             ?>
        </form>
    </main>
</body>
</html>