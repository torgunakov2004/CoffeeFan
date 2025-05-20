<?php
session_start();

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Редирект если уже авторизован
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeeFan | Авторизация</title>
    <link rel="stylesheet" href="auth_style.css?<?= time() ?>">
    <link rel="icon" href="../img/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="auth-container">
        <img src="../img/logo.svg" alt="CoffeeeFan Logo" class="auth-logo">
        <h1 class="auth-title">Вход в систему</h1>

        <form action="../config/singin.php" method="post" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="input-group">
                <label for="login">Логин или Email</label>
                <input type="text" id="login" name="login" placeholder="Ваш логин или email" required
                       value="<?= !empty($_SESSION['old']['login']) ? htmlspecialchars($_SESSION['old']['login']) : '' ?>">
            </div>

            <div class="input-group">
                <label for="password">Пароль</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" aria-label="Показать пароль">👁️</button>
                </div>
                <a href="#" class="forgot-password">Забыли пароль?</a>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <button type="submit" class="auth-button">Войти</button>
            
            <div class="auth-footer">
                <p>Ещё нет аккаунта? <a href="register.php">Создать аккаунт</a></p>
                <p>или <a href="../index.php">вернуться на главную</a></p>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🔒';
            });
        });
    </script>
</body>
</html>
<?php
// Очищаем старые данные формы
if (isset($_SESSION['old'])) {
    unset($_SESSION['old']);
}
?>