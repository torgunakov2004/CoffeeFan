<?php
session_start();
require_once '../config/connect.php'; // Подключение к БД

// --- Настройки ---
$upload_dir = '../uploads/promotions/'; // Папка для загрузки изображений акций
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

// --- Проверка прав админа ---
if (!isset($_SESSION['admin'])) {
     $_SESSION['message'] = "Ошибка: Доступ запрещен.";
     header('Location: admin_login.php');
     exit();
}

// --- Обработка добавления/редактирования (POST из формы) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {

    $action = $_POST['action'];
    $id = ($action === 'edit' && isset($_POST['id'])) ? (int)$_POST['id'] : null;

    // Получаем и экранируем данные из формы
    $title = mysqli_real_escape_string($connect, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connect, trim($_POST['description'] ?? ''));
    $conditions = mysqli_real_escape_string($connect, trim($_POST['conditions'] ?? ''));
    $link = mysqli_real_escape_string($connect, trim($_POST['link'] ?? ''));
    $type = mysqli_real_escape_string($connect, $_POST['type'] ?? 'card');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    $old_data = $_POST; // Сохраняем введенные данные для формы в случае ошибки

    // Валидация
    if (empty($title)) $errors[] = "Заголовок обязателен для заполнения.";
    if (empty($description)) $errors[] = "Описание обязательно для заполнения.";
    if (!in_array($type, ['card', 'collage'])) $errors[] = "Недопустимый тип отображения.";
    // Добавьте другие проверки, если нужно

    $image_path_in_db = null; // Путь к файлу для записи в БД
    $old_image_path = null; // Физический путь к старому файлу для удаления

    // Получаем путь к старому изображению, если редактируем
    if ($action === 'edit' && $id) {
        $safe_id = mysqli_real_escape_string($connect, $id);
        $result_old = mysqli_query($connect, "SELECT image FROM promotions WHERE id = '$safe_id'");
        if ($row_old = mysqli_fetch_assoc($result_old)) {
            $image_path_in_db = $row_old['image']; // Путь как он хранится в БД
            $old_image_path = $row_old['image']; // Этот же путь используем для удаления файла
        }
    }

    // Обработка загрузки файла
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];

        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Недопустимый тип файла изображения. Разрешены: JPG, PNG, GIF, WEBP.";
        }
        if ($file['size'] > $max_file_size) {
            $errors[] = "Файл изображения слишком большой. Максимальный размер: 5 MB.";
        }

        if (empty($errors)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('promo_', true) . '.' . strtolower($ext);
            $target_path = $upload_dir . $unique_name; // Физический путь для сохранения

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) { // Пытаемся создать папку
                     $errors[] = "Не удалось создать директорию для загрузки: " . $upload_dir;
                }
            }

             if (empty($errors) && is_writable($upload_dir)) { // Проверяем права на запись
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $image_path_in_db = '../uploads/promotions/' . $unique_name; // Путь для записи в БД

                    // Удаляем старый файл, если он был и отличается от нового
                    if ($old_image_path && $old_image_path !== $image_path_in_db && file_exists($old_image_path)) {
                        @unlink($old_image_path);
                    }
                } else {
                    $errors[] = "Не удалось переместить загруженный файл.";
                }
            } elseif(empty($errors)) {
                 $errors[] = "Директория для загрузки недоступна для записи: " . $upload_dir;
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Ошибка при загрузке файла изображения (код: " . $_FILES['image']['error'] . ").";
    } elseif ($action === 'add' && (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)) {
        $errors[] = "Изображение обязательно при добавлении новой акции.";
    }


    // Если есть ошибки, возвращаемся на форму
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_data'] = $old_data;
        $redirect_url = ($action === 'edit' && $id) ? 'promotion_form.php?id=' . $id : 'promotion_form.php';
        header('Location: ' . $redirect_url);
        exit();
    }

    // Сохраняем в БД
    if ($action === 'edit' && $id) { // Редактирование
        $safe_id = mysqli_real_escape_string($connect, $id);
        $image_sql_part = ($image_path_in_db !== $old_image_path || $old_image_path === null) ? ", image = '$image_path_in_db'" : ""; // Обновляем картинку только если она изменилась

        $sql = "UPDATE promotions SET
                    title = '$title',
                    description = '$description',
                    conditions = '$conditions',
                    link = '$link',
                    type = '$type',
                    is_active = '$is_active'
                    $image_sql_part
                WHERE id = '$safe_id'";

    } else { // Добавление
        $sql = "INSERT INTO promotions (title, description, image, conditions, link, type, is_active, created_at)
                VALUES ('$title', '$description', '$image_path_in_db', '$conditions', '$link', '$type', '$is_active', NOW())";
    }

    if (mysqli_query($connect, $sql)) {
        $_SESSION['message'] = ($action === 'edit') ? "Акция успешно обновлена." : "Акция успешно добавлена.";
    } else {
        $_SESSION['message'] = "Ошибка при сохранении акции: " . mysqli_error($connect);
         // Сохраняем данные для формы при ошибке БД
        $_SESSION['old_data'] = $old_data;
        $redirect_url = ($action === 'edit' && $id) ? 'promotion_form.php?id=' . $id : 'promotion_form.php';
        header('Location: ' . $redirect_url);
        exit();
    }

    header('Location: promotions_list.php');
    exit();

}
// --- Обработка удаления (POST из формы) ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $safe_id = mysqli_real_escape_string($connect, $id);

    // Получаем путь к картинке
    $image_path = null;
    $result_img = mysqli_query($connect, "SELECT image FROM promotions WHERE id = '$safe_id'");
    if ($row_img = mysqli_fetch_assoc($result_img)) {
        $image_path = $row_img['image'];
    }

    // Удаляем запись
    $sql = "DELETE FROM promotions WHERE id = '$safe_id'";
    if (mysqli_query($connect, $sql)) {
        $_SESSION['message'] = "Акция успешно удалена.";
        // Удаляем файл
        if ($image_path && file_exists($image_path)) {
            @unlink($image_path);
        }
    } else {
        $_SESSION['message'] = "Ошибка при удалении акции: " . mysqli_error($connect);
    }

    header('Location: promotions_list.php');
    exit();
}
// --- Обработка переключения статуса (POST из формы) ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_POST['id'])) {
     $id = (int)$_POST['id'];
     $safe_id = mysqli_real_escape_string($connect, $id);

     // Получаем текущий статус
     $current_status = 0;
     $result_status = mysqli_query($connect, "SELECT is_active FROM promotions WHERE id = '$safe_id'");
     if ($row_status = mysqli_fetch_assoc($result_status)) {
         $current_status = $row_status['is_active'];
     }

     // Переключаем
     $new_status = $current_status == 1 ? 0 : 1;

     // Обновляем
     $sql = "UPDATE promotions SET is_active = '$new_status' WHERE id = '$safe_id'";
     if (mysqli_query($connect, $sql)) {
        $_SESSION['message'] = "Статус акции успешно изменен.";
    } else {
        $_SESSION['message'] = "Ошибка при изменении статуса: " . mysqli_error($connect);
    }

     header('Location: promotions_list.php');
     exit();
}

// Если действие не распознано
else {
    header('Location: promotions_list.php');
    exit();
}

mysqli_close($connect); // Закрываем соединение
?>