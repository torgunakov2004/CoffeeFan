<?php
    session_start();

    if (!empty($_SESSION["user"])) {
        header("Location: ../index.php");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ExoProduct|Регистрация</title>
    <link rel="stylesheet" href="../auth.css?<?echo time();?>">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
    </header>
    <main class="cab">
        <form action="../config/singin.php" method="post">
            <label>Логин</label>
            <input type="text" name="login" placeholder="Введите логин">
            <label>Пароль</label>
            <input type="password" name="password" placeholder="Введите пароль">
            <button type="submit">Войти</button>
            <p>
                У вас нет аккаунта? - <a href="register.php">зарегиструйтесь</a>!
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