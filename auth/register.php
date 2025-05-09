<?php
    session_start();

    if (isset($_SESSION["user"]) && !empty($_SESSION["user"])) {
        header("Location: ../index.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeeFan | Регистрация</title>
    <link rel="stylesheet" href="auth_style.css?<?php echo time();?>">
</head>
<body>
    <div class="auth-container">
        <img src="../img/logo.svg" alt="CoffeeeFan Logo" class="auth-logo">
        <h1 class="auth-title">Регистрация</h1>

        <form action="../config/signup.php" method="post">
            <div>
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" placeholder="Введите ваше имя" required>
            </div>

            <div>
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" placeholder="Введите вашу фамилию" required>
            </div>

            <div>
                <label for="login_reg">Логин</label>
                <input type="text" id="login_reg" name="login" placeholder="Придумайте логин" required>
            </div>

            <div>
                <label for="email">Почта</label>
                <input type="email" id="email" name="email" placeholder="Введите вашу почту" required>
            </div>

            <div>
                <label for="password_reg">Пароль</label>
                <input type="password" id="password_reg" name="password" placeholder="Придумайте пароль (мин. 6 символов)" required>
            </div>

            <div>
                <label for="password_confirm">Подтвердите пароль</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Повторите пароль" required>
            </div>

            <?php
                if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
                    $message_class = "error"; // По умолчанию класс для ошибки
                    if (isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'success') {
                       $message_class = "success"; // Если тип 'success', меняем класс
                    }
                    echo '<p class="msg ' . $message_class . '"> ' . htmlspecialchars($_SESSION['message']) . ' </p>';
                    unset($_SESSION['message']);
                    if (isset($_SESSION['message_type'])) { // Также очищаем тип сообщения
                        unset($_SESSION['message_type']);
                    }
                }
             ?>

            <button type="submit">Зарегистрироваться</button>
            <p>
                У вас уже есть аккаунт? - <a href="authorization.php">Авторизуйтесь!</a>
            </p>
        </form>
    </div>
</body>
</html>