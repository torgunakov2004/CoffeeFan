<?php
session_start();
require_once '../config/connect.php'; // Подняться на один уровень вверх к config

// 1. Проверка авторизации
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header('Location: ../auth/authorization.php'); // Подняться и зайти в auth
    exit();
}

$user_id = $_SESSION['user']['id'];
$current_user_data = [];

// 2. Получение текущих данных пользователя из БД
$stmt_get_user = $connect->prepare("SELECT first_name, last_name, login, email, avatar FROM user WHERE id = ?");
if ($stmt_get_user) {
    $stmt_get_user->bind_param("i", $user_id);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();
    if ($result_get_user->num_rows === 1) {
        $current_user_data = $result_get_user->fetch_assoc();
         // Убедимся, что данные в сессии актуальны, особенно аватар, если он мог измениться другим путем
        $_SESSION['user']['first_name'] = $current_user_data['first_name'];
        $_SESSION['user']['last_name'] = $current_user_data['last_name'];
        $_SESSION['user']['login'] = $current_user_data['login'];
        $_SESSION['user']['email'] = $current_user_data['email'];
        $_SESSION['user']['avatar'] = $current_user_data['avatar']; // Важно для консистентности
        $_SESSION['user']['name'] = $current_user_data['first_name']; // Для совместимости
    } else {
        // Пользователь не найден в БД, хотя ID есть в сессии - некорректная ситуация
        unset($_SESSION['user']);
        if (isset($_SESSION['admin'])) unset($_SESSION['admin']); // Также очищаем админскую сессию на всякий случай
        $_SESSION['message'] = 'Произошла ошибка с вашим аккаунтом. Пожалуйста, войдите снова.';
        header('Location: ../auth/authorization.php');
        exit();
    }
    $stmt_get_user->close();
} else {
    error_log("Ошибка подготовки запроса для получения данных пользователя: " . $connect->error);
    // Для пользователя лучше не выводить die(), а показать сообщение или редирект
    $_SESSION['profile_message'] = "Ошибка сервера при загрузке данных профиля. Пожалуйста, попробуйте позже.";
    $_SESSION['profile_message_type'] = "error";
    // Можно оставить пользователя на странице, но с сообщением об ошибке,
    // либо редиректнуть, если данные критичны для отображения.
    // header('Location: ../index.php'); // Пример редиректа
    // exit();
}


// 3. Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $errors = [];

        if (empty($first_name)) {
            $errors[] = "Имя не может быть пустым.";
        }
        if (empty($last_name)) {
            $errors[] = "Фамилия не может быть пустой.";
        }

        // Путь к аватару, который будет сохранен в БД (относительно корня сайта)
        $avatar_path_to_db_root_relative = $current_user_data['avatar'] ?? null;

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir_from_root = 'uploads/avatars/'; // Путь от корня сайта
            $upload_dir_relative_to_script = '../' . $upload_dir_from_root; // Путь от текущего скрипта (profile/)

            if (!is_dir($upload_dir_relative_to_script)) {
                if (!mkdir($upload_dir_relative_to_script, 0777, true) && !is_dir($upload_dir_relative_to_script)) {
                    $errors[] = 'Не удалось создать директорию для загрузки аватаров.';
                }
            }
            
            if (empty($errors)) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_file_size = 5 * 1024 * 1024; // 5MB

                $file_name = $_FILES['avatar']['name'];
                $file_tmp_name = $_FILES['avatar']['tmp_name'];
                $file_size = $_FILES['avatar']['size'];
                $file_type = mime_content_type($file_tmp_name); // Более надежный способ определения типа

                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Недопустимый тип файла аватара. Разрешены JPG, PNG, GIF.";
                } elseif ($file_size > $max_file_size) {
                    $errors[] = "Файл аватара слишком большой. Максимальный размер 5MB.";
                } else {
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    // Дополнительная проверка расширения, хотя mime_content_type надежнее
                    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $errors[] = "Недопустимое расширение файла аватара.";
                    } else {
                        $new_file_name = uniqid('avatar_', true) . '.' . $file_extension;
                        $destination_relative_to_script = $upload_dir_relative_to_script . $new_file_name;
                        
                        // Новый путь для БД (от корня сайта)
                        $new_avatar_path_for_db = $upload_dir_from_root . $new_file_name;

                        if (move_uploaded_file($file_tmp_name, $destination_relative_to_script)) {
                            // Удаляем старый аватар, если он не дефолтный
                            $old_avatar_from_root = $current_user_data['avatar'] ?? null;
                            if (!empty($old_avatar_from_root) &&
                                strpos($old_avatar_from_root, 'default-avatar.jpg') === false &&
                                strpos($old_avatar_from_root, 'icons8.png') === false &&
                                file_exists('../' . ltrim($old_avatar_from_root, '/'))) { // Проверяем от текущего скрипта
                                unlink('../' . ltrim($old_avatar_from_root, '/'));
                            }
                            $avatar_path_to_db_root_relative = $new_avatar_path_for_db;
                        } else {
                            $errors[] = "Ошибка загрузки нового аватара.";
                        }
                    }
                }
            }
        }

        if (empty($errors)) {
            $update_stmt = $connect->prepare("UPDATE user SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("sssi", $first_name, $last_name, $avatar_path_to_db_root_relative, $user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['profile_message'] = "Профиль успешно обновлен!";
                    $_SESSION['profile_message_type'] = "success";

                    // Обновляем данные в сессии
                    $_SESSION['user']['first_name'] = $first_name;
                    $_SESSION['user']['name'] = $first_name; 
                    $_SESSION['user']['last_name'] = $last_name;
                    $_SESSION['user']['avatar'] = $avatar_path_to_db_root_relative;

                    // Обновляем $current_user_data для немедленного отображения на этой же странице
                    $current_user_data['first_name'] = $first_name;
                    $current_user_data['last_name'] = $last_name;
                    $current_user_data['avatar'] = $avatar_path_to_db_root_relative;

                } else {
                    $_SESSION['profile_message'] = "Ошибка обновления профиля: " . $update_stmt->error;
                    $_SESSION['profile_message_type'] = "error";
                }
                $update_stmt->close();
            } else {
                $_SESSION['profile_message'] = "Ошибка подготовки запроса обновления: " . $connect->error;
                $_SESSION['profile_message_type'] = "error";
            }
        } else {
            $_SESSION['profile_message'] = implode("<br>", $errors);
            $_SESSION['profile_message_type'] = "error";
        }
        header('Location: profile.php#info'); // Редирект к вкладке "Основная информация"
        exit();

    } elseif (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $password_errors = [];

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $password_errors[] = "Все поля для смены пароля должны быть заполнены.";
        } elseif (strlen($new_password) < 6) {
            $password_errors[] = "Новый пароль должен быть не менее 6 символов.";
        } elseif ($new_password !== $confirm_password) {
            $password_errors[] = "Новый пароль и его подтверждение не совпадают.";
        } else {
            $stmt_check_pass = $connect->prepare("SELECT password FROM user WHERE id = ?");
            if ($stmt_check_pass) {
                $stmt_check_pass->bind_param("i", $user_id);
                $stmt_check_pass->execute();
                $result_check_pass = $stmt_check_pass->get_result();
                $user_pass_data = $result_check_pass->fetch_assoc();
                $stmt_check_pass->close();

                // ВАЖНО: Замените MD5 на password_verify() когда перейдете на password_hash()
                if ($user_pass_data && md5($old_password) === $user_pass_data['password']) {
                    // ВАЖНО: Замените MD5 на password_hash()
                    $hashed_new_password = md5($new_password); 
                    
                    $stmt_update_pass = $connect->prepare("UPDATE user SET password = ? WHERE id = ?");
                    if ($stmt_update_pass) {
                        $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
                        if ($stmt_update_pass->execute()) {
                            $_SESSION['profile_message'] = "Пароль успешно изменен!";
                            $_SESSION['profile_message_type'] = "success";
                        } else {
                             $password_errors[] = "Ошибка смены пароля: " . $stmt_update_pass->error;
                        }
                        $stmt_update_pass->close();
                    } else {
                        $password_errors[] = "Ошибка подготовки запроса смены пароля: " . $connect->error;
                    }
                } else {
                    $password_errors[] = "Старый пароль введен неверно.";
                }
            } else {
                $password_errors[] = "Ошибка проверки старого пароля: " . $connect->error;
            }
        }
        if (!empty($password_errors)) {
            $_SESSION['profile_message'] = implode("<br>", $password_errors);
            $_SESSION['profile_message_type'] = "error";
        }
        header('Location: profile.php#password'); // Редирект к вкладке "Смена пароля"
        exit();
    }
}

// 4. Получение информации для хедера (корзина)
$cart_quantities = [];
$has_items_in_cart = false;
if (isset($_SESSION['user']['id'])) { 
    $user_id_for_cart = $_SESSION['user']['id'];
    $query_cart_header = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart_header = $connect->prepare($query_cart_header);
    if ($stmt_cart_header) {
        $stmt_cart_header->bind_param("i", $user_id_for_cart);
        $stmt_cart_header->execute();
        $result_cart_header = $stmt_cart_header->get_result();
        while ($row_cart_header = $result_cart_header->fetch_assoc()) {
            $cart_quantities[$row_cart_header['product_id']] = $row_cart_header['quantity'];
        }
        $stmt_cart_header->close();
    }
    $has_items_in_cart = !empty($cart_quantities);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeeFan - Мой профиль</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="profile_style_v2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <main class="profile-page-main-v2">
        <div class="profile-card-container">
            <div class="profile-card">
                <div class="profile-card-header">
                    <?php
                        $avatar_display_profile = '../img/default-avatar.jpg';
                        if (!empty($current_user_data['avatar'])) {
                            $path_check_profile_page = '../' . ltrim($current_user_data['avatar'], '/');
                            if (file_exists($path_check_profile_page)) {
                                $avatar_display_profile = htmlspecialchars($path_check_profile_page);
                            }
                        }
                    ?>
                    <img src="<?php echo $avatar_display_profile; ?>" alt="Аватар пользователя" class="profile-card-avatar">
                    <div class="profile-card-userinfo">
                        <h1 class="profile-card-name"><?php echo htmlspecialchars($current_user_data['first_name'] ?? ''); ?> <?php echo htmlspecialchars($current_user_data['last_name'] ?? ''); ?></h1>
                        <p class="profile-card-login">@<?php echo htmlspecialchars($current_user_data['login'] ?? ''); ?></p>
                    </div>
                </div>

                <?php if (isset($_SESSION['profile_message'])): ?>
                    <div class="profile-message-v2 <?php echo htmlspecialchars($_SESSION['profile_message_type'] ?? 'success'); ?>">
                        <?php echo $_SESSION['profile_message']; // Здесь htmlspecialchars не нужен, если ошибки содержат <br> ?>
                    </div>
                    <?php unset($_SESSION['profile_message'], $_SESSION['profile_message_type']); ?>
                <?php endif; ?>

                <div class="profile-card-tabs">
                    <button class="tab-button active" data-tab="info">Основная информация</button>
                    <button class="tab-button" data-tab="password">Смена пароля</button>
                </div>

                <div class="profile-card-content">
                    <div id="info" class="tab-content active">
                        <h2 class="tab-content-title">Редактировать данные</h2>
                        <form action="profile.php" method="post" enctype="multipart/form-data" class="profile-form-v2">
                            <div class="form-group-v2">
                                <label for="first_name">Имя:</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($current_user_data['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group-v2">
                                <label for="last_name">Фамилия:</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($current_user_data['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group-v2">
                                <label for="email_display">Email (нельзя изменить):</label>
                                <input type="email" id="email_display" name="email_display" value="<?php echo htmlspecialchars($current_user_data['email'] ?? ''); ?>" readonly class="readonly-input-v2">
                            </div>
                            <div class="form-group-v2 avatar-upload-group">
                                <label for="avatar">Изменить аватар (JPG, PNG, GIF до 5MB):</label>
                                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif" class="file-input-v2">
                            </div>
                            <div class="form-button-container-v2">
                                <button type="submit" name="update_profile" class="btn-primary-v2 profile-submit-btn-v2">Сохранить данные</button>
                            </div>
                        </form>
                    </div>

                    <div id="password" class="tab-content">
                        <h2 class="tab-content-title">Изменить пароль</h2>
                        <form action="profile.php" method="post" class="profile-form-v2">
                             <div class="form-group-v2">
                                 <label for="old_password">Старый пароль:</label>
                                 <input type="password" id="old_password" name="old_password" required>
                             </div>
                             <div class="form-group-v2">
                                 <label for="new_password">Новый пароль (мин. 6 символов):</label>
                                 <input type="password" id="new_password" name="new_password" minlength="6" required>
                             </div>
                             <div class="form-group-v2">
                                 <label for="confirm_password">Подтвердите новый пароль:</label>
                                 <input type="password" id="confirm_password" name="confirm_password" required>
                             </div>
                             <div class="form-button-container-v2">
                                <button type="submit" name="change_password" class="btn-primary-v2 profile-submit-btn-v2">Сменить пароль</button>
                             </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.profile-card-tabs .tab-button');
            const tabContents = document.querySelectorAll('.profile-card-content .tab-content');
            let activeTabId = 'info'; // Вкладка по умолчанию

            // Проверяем хеш в URL при загрузке
            if (window.location.hash) {
                const hash = window.location.hash.substring(1); // Удаляем #
                const potentialTabButton = document.querySelector(`.tab-button[data-tab="${hash}"]`);
                const potentialTabContent = document.getElementById(hash);
                if (potentialTabButton && potentialTabContent) { // Проверяем, что такие элементы существуют
                    activeTabId = hash;
                }
            }

            function setActiveTab(tabId) {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                const currentTabButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
                const currentTabContent = document.getElementById(tabId);

                if (currentTabButton) currentTabButton.classList.add('active');
                if (currentTabContent) currentTabContent.classList.add('active');
            }

            setActiveTab(activeTabId); // Устанавливаем активную вкладку при загрузке

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault(); // Предотвращаем стандартное поведение (переход по якорю, который может "дергать" страницу)
                    const targetTabContentId = tab.getAttribute('data-tab');
                    setActiveTab(targetTabContentId);
                    // Обновляем хеш в URL без перезагрузки страницы для лучшего UX
                    // (если пользователь обновит страницу, он останется на той же вкладке)
                    history.pushState(null, null, '#' + targetTabContentId);
                });
            });
        });
    </script>
</body>
</html>