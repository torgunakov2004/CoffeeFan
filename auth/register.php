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
    <title>CoffeeeFan | Регистрация</title>
    <link rel="stylesheet" href="auth_style.css?<?= time() ?>">
    <link rel="icon" href="../img/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="auth-container">
        <img src="../img/logo.svg" alt="CoffeeeFan Logo" class="auth-logo">
        <h1 class="auth-title">Создать аккаунт</h1>

        <form action="../config/signup.php" method="post" autocomplete="on" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="name-fields">
                <div class="input-group">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name" placeholder="Иван" required
                           value="<?= !empty($_SESSION['old']['name']) ? htmlspecialchars($_SESSION['old']['name']) : '' ?>">
                </div>

                <div class="input-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Иванов" required
                           value="<?= !empty($_SESSION['old']['last_name']) ? htmlspecialchars($_SESSION['old']['last_name']) : '' ?>">
                </div>
            </div>

            <div class="input-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" placeholder="ivanov" required
                       value="<?= !empty($_SESSION['old']['login']) ? htmlspecialchars($_SESSION['old']['login']) : '' ?>">
                <div class="hint">Только латинские буквы и цифры</div>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="ivan@example.com" required
                       value="<?= !empty($_SESSION['old']['email']) ? htmlspecialchars($_SESSION['old']['email']) : '' ?>">
            </div>

            <div class="input-group">
                <label for="password">Пароль</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8">
                    <button type="button" class="toggle-password" aria-label="Показать пароль">👁️</button>
                </div>
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <div class="hint">Минимум 8 символов</div>
                </div>
            </div>

            <div class="input-group">
                <label for="password_confirm">Подтверждение пароля</label>
                <div class="password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                    <button type="button" class="toggle-password" aria-label="Показать пароль">👁️</button>
                </div>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="terms-agreement">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">Я согласен с <a href="../list_footer/terms.php" target="_blank">условиями использования</a></label>
            </div>

            <button type="submit" class="auth-button">Зарегистрироваться</button>
            
            <div class="auth-footer">
                <p>Уже есть аккаунт? <a href="authorization.php">Войти</a></p>
                <p>или <a href="../index.php">вернуться на главную</a></p>
            </div>
        </form>
    </div>

    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🔒';
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.querySelector('.strength-bar');

        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            strengthBar.style.width = `${strength}%`;
            strengthBar.style.backgroundColor = getStrengthColor(strength);
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length > 0) strength += Math.min(25, password.length * 5);
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 40;
            return Math.min(100, strength);
        }

        function getStrengthColor(strength) {
            if (strength < 40) return '#e74c3c';
            if (strength < 70) return '#f39c12';
            return '#2ecc71';
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirm = document.getElementById('password_confirm');
            
            if (password.value !== confirm.value) {
                e.preventDefault();
                // Создаем или находим элемент для отображения ошибки
                let errorDiv = document.querySelector('.password-mismatch-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'alert error password-mismatch-error';
                    confirm.parentNode.insertBefore(errorDiv, confirm.nextSibling);
                }
                errorDiv.textContent = 'Пароли не совпадают';
            }
        });

        // Убираем сообщение об ошибке при изменении пароля
        document.getElementById('password_confirm').addEventListener('input', function() {
            const errorDiv = document.querySelector('.password-mismatch-error');
            if (errorDiv) {
                errorDiv.remove();
            }
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