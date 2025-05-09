<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_data = null;
$errors = [];
$message = '';
$message_type = '';

if ($user_id_to_edit > 0) {
    $stmt_get = $connect->prepare("SELECT id, first_name, last_name, login, email, is_admin FROM user WHERE id = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $user_id_to_edit);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows === 1) {
            $user_data = $result_get->fetch_assoc();
        } else {
            $_SESSION['admin_message'] = "Пользователь с ID $user_id_to_edit не найден.";
            $_SESSION['admin_message_type'] = "error";
            header('Location: admin_dashboard.php');
            exit();
        }
        $stmt_get->close();
    } else {
        error_log("Edit User: Failed to prepare user select query: " . $connect->error);
        $errors[] = "Ошибка загрузки данных пользователя.";
    }
} else {
    $_SESSION['admin_message'] = "Некорректный ID пользователя.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: admin_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $login = trim($_POST['login']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (empty($first_name)) $errors[] = "Имя обязательно.";
    if (empty($last_name)) $errors[] = "Фамилия обязательна.";
    if (empty($login)) $errors[] = "Логин обязателен.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некорректный Email.";

    if ($id !== $user_id_to_edit) {
        $errors[] = "Ошибка ID пользователя.";
    }

    if (empty($errors)) {
        // Проверка на уникальность логина и email, если они были изменены
        $current_login = $user_data['login'];
        $current_email = $user_data['email'];
        
        if ($login !== $current_login) {
            $stmt_check_login = $connect->prepare("SELECT id FROM user WHERE login = ? AND id != ?");
            $stmt_check_login->bind_param("si", $login, $id);
            $stmt_check_login->execute();
            if ($stmt_check_login->get_result()->num_rows > 0) {
                $errors[] = "Этот логин уже используется.";
            }
            $stmt_check_login->close();
        }
        if ($email !== $current_email) {
            $stmt_check_email = $connect->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param("si", $email, $id);
            $stmt_check_email->execute();
            if ($stmt_check_email->get_result()->num_rows > 0) {
                $errors[] = "Этот email уже используется.";
            }
            $stmt_check_email->close();
        }

        if (empty($errors)) {
             // Нельзя снять права админа с самого себя, если это текущий активный админ
            if ($id == $_SESSION['admin']['id'] && $is_admin == 0 && $user_data['is_admin'] == 1) {
                $errors[] = "Вы не можете снять с себя права администратора.";
            } else {
                $stmt_update = $connect->prepare("UPDATE user SET first_name = ?, last_name = ?, login = ?, email = ?, is_admin = ? WHERE id = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("ssssii", $first_name, $last_name, $login, $email, $is_admin, $id);
                    if ($stmt_update->execute()) {
                        $_SESSION['admin_message'] = "Данные пользователя успешно обновлены.";
                        $_SESSION['admin_message_type'] = "success";
                        header('Location: admin_dashboard.php');
                        exit();
                    } else {
                        $errors[] = "Ошибка обновления данных пользователя: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $errors[] = "Ошибка подготовки запроса обновления: " . $connect->error;
                }
            }
        }
    }
    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пользователя - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Редактирование пользователя</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="edit_user.php?id=<?php echo $user_id_to_edit; ?>" method="post" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_data['id']); ?>">
                
                <div class="form-group">
                    <label for="first_name">Имя:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Фамилия:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($user_data['login']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_admin" value="1" <?php echo ($user_data['is_admin'] == 1) ? 'checked' : ''; ?> 
                               <?php if ($user_data['id'] == $_SESSION['admin']['id']) echo 'onclick="return confirm(\'Вы уверены, что хотите изменить свои права администратора?\');"'; ?> >
                        Администратор
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_user" class="btn-save">Сохранить изменения</button>
                    <a href="admin_dashboard.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>