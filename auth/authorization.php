<?php
    session_start();
    if (isset($_SESSION["user"]) && !empty($_SESSION["user"])) { // Более строгая проверка
        header("Location: ../index.php");
        exit(); 
    }
?>

<!DOCTYPE html>
<html lang="ru"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Добавлен viewport для адаптивности -->
    <title>CoffeeeFan | Авторизация</title> <!-- Изменено название -->
    <link rel="stylesheet" href="auth_style.css?<?php echo time();?>"> <!-- Путь к новому CSS -->
</head>
<body>
    <div class="auth-container">
        <img src="../img/logo.svg" alt="CoffeeeFan Logo" class="auth-logo"> <!-- Логотип -->
        <h1 class="auth-title">Авторизация</h1>

        <form action="../config/singin.php" method="post">
            <div> <!-- Обертка для label + input -->
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" placeholder="Введите ваш логин" required> <!-- Добавлен required и id -->
            </div>

            <div> <!-- Обертка для label + input -->
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required> <!-- Добавлен required и id -->
            </div>

            <?php
                if (isset($_SESSION['message']) && !empty($_SESSION['message'])) { // Проверка на существование и непустоту
                    // Предполагаем, что сообщение об ошибке
                    echo '<p class="msg error"> ' . htmlspecialchars($_SESSION['message']) . ' </p>'; // Добавлен htmlspecialchars
                    unset($_SESSION['message']);
                }
             ?>

            <button type="submit">Войти</button>
            <p>
                У вас нет аккаунта? - <a href="register.php">Зарегистрируйтесь!</a>
            </p>
        </form>
    </div>
</body>
</html>