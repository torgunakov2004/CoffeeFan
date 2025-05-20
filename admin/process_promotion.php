<?php
session_start();
require_once '../config/connect.php';

$upload_dir_promotions = '../uploads/promotions/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024; 

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
     $_SESSION['admin_message'] = "Доступ запрещен.";
     $_SESSION['admin_message_type'] = "error";
     header('Location: admin_login.php');
     exit();
}

if (!is_dir($upload_dir_promotions)) {
    if (!mkdir($upload_dir_promotions, 0777, true) && !is_dir($upload_dir_promotions)) {
        $_SESSION['admin_message'] = "Не удалось создать директорию для загрузки: " . $upload_dir_promotions;
        $_SESSION['admin_message_type'] = "error";
        header('Location: promotions_list.php');
        exit();
    }
}


function sanitize_promo_filename($filename) {
    $filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    return strtolower($filename);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ($_GET['action'] ?? null);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : null);

    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $conditions = trim($_POST['conditions'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $errors = [];

        if (empty($title)) $errors[] = "Заголовок обязателен.";
        if (empty($description)) $errors[] = "Описание обязательно.";
        
        $image_path_for_db = null;
        $old_image_path_from_db = null;

        if ($action === 'edit' && $id) {
            $stmt_old_img = $connect->prepare("SELECT image FROM promotions WHERE id = ?");
            if ($stmt_old_img) {
                $stmt_old_img->bind_param("i", $id);
                $stmt_old_img->execute();
                $res_old_img = $stmt_old_img->get_result();
                if ($row_old_img = $res_old_img->fetch_assoc()) {
                    $old_image_path_from_db = $row_old_img['image'];
                    $image_path_for_db = $old_image_path_from_db; 
                }
                $stmt_old_img->close();
            } else {
                 $errors[] = "Ошибка получения старого изображения: " . $connect->error;
            }
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];
            $file_type = mime_content_type($file_tmp_name);

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WEBP.";
            } elseif ($file_size > $max_file_size) {
                $errors[] = "Файл слишком большой. Макс: 5MB.";
            } else {
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid('promo_', true) . '.' . sanitize_promo_filename($file_extension);
                $target_physical_path = $upload_dir_promotions . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $target_physical_path)) {
                    $image_path_for_db = 'uploads/promotions/' . $new_file_name; 
                    if ($action === 'edit' && !empty($old_image_path_from_db) && $old_image_path_from_db !== $image_path_for_db) {
                         if(file_exists('../' . ltrim($old_image_path_from_db, '/'))) {
                            unlink('../' . ltrim($old_image_path_from_db, '/'));
                         }
                    }
                } else {
                    $errors[] = "Ошибка перемещения загруженного файла.";
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Ошибка при загрузке файла (код: " . $_FILES['image']['error'] . ").";
        } elseif ($action === 'add' && (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)) {
            $errors[] = "Изображение обязательно для новой акции.";
        }

        if (!empty($errors)) {
            if ($action === 'add') {
                $_SESSION['form_add_errors'] = $errors;
                $_SESSION['old_add_form_data'] = $_POST;
                 header('Location: promotions_list.php#add-form-anchor');
            } else {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $_POST;
                header('Location: promotion_form.php?id=' . $id);
            }
            exit();
        }

        if ($action === 'edit' && $id) {
            $stmt_update = $connect->prepare("UPDATE promotions SET title = ?, description = ?, image = ?, conditions = ?, link = ?, is_active = ? WHERE id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("sssssii", $title, $description, $image_path_for_db, $conditions, $link, $is_active, $id);
                if ($stmt_update->execute()) {
                    $_SESSION['admin_message'] = "Акция успешно обновлена.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Ошибка обновления акции: " . $stmt_update->error;
                    $_SESSION['admin_message_type'] = "error";
                }
                $stmt_update->close();
            } else {
                 $_SESSION['admin_message'] = "Ошибка подготовки запроса обновления: " . $connect->error;
                 $_SESSION['admin_message_type'] = "error";
            }
        } elseif ($action === 'add') {
            $stmt_insert = $connect->prepare("INSERT INTO promotions (title, description, image, conditions, link, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssssi", $title, $description, $image_path_for_db, $conditions, $link, $is_active);
                if ($stmt_insert->execute()) {
                    $_SESSION['admin_message'] = "Акция успешно добавлена.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Ошибка добавления акции: " . $stmt_insert->error;
                    $_SESSION['admin_message_type'] = "error";
                }
                $stmt_insert->close();
            } else {
                $_SESSION['admin_message'] = "Ошибка подготовки запроса добавления: " . $connect->error;
                $_SESSION['admin_message_type'] = "error";
            }
        }
        header('Location: promotions_list.php');
        exit();

    } elseif ($action === 'delete' && $id) {
        $stmt_get_img_del = $connect->prepare("SELECT image FROM promotions WHERE id = ?");
        $image_to_delete = null;
        if($stmt_get_img_del){
            $stmt_get_img_del->bind_param("i", $id);
            $stmt_get_img_del->execute();
            $res_img_del = $stmt_get_img_del->get_result();
            if($row_img_del = $res_img_del->fetch_assoc()){
                $image_to_delete = $row_img_del['image'];
            }
            $stmt_get_img_del->close();
        }

        $stmt_delete = $connect->prepare("DELETE FROM promotions WHERE id = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $id);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['admin_message'] = "Акция успешно удалена.";
                    $_SESSION['admin_message_type'] = "success";
                    if (!empty($image_to_delete) && file_exists('../' . ltrim($image_to_delete, '/'))) {
                        unlink('../' . ltrim($image_to_delete, '/'));
                    }
                } else {
                    $_SESSION['admin_message'] = "Акция не найдена или уже удалена.";
                    $_SESSION['admin_message_type'] = "error";
                }
            } else {
                $_SESSION['admin_message'] = "Ошибка удаления акции: " . $stmt_delete->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_delete->close();
        } else {
             $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления: " . $connect->error;
             $_SESSION['admin_message_type'] = "error";
        }
        header('Location: promotions_list.php');
        exit();

    } elseif ($action === 'toggle_status' && $id && isset($_POST['current_status'])) {
        $current_status = (int)$_POST['current_status'];
        $new_status = $current_status == 1 ? 0 : 1;
        $stmt_toggle = $connect->prepare("UPDATE promotions SET is_active = ? WHERE id = ?");
        if($stmt_toggle){
            $stmt_toggle->bind_param("ii", $new_status, $id);
            if ($stmt_toggle->execute()) {
                $_SESSION['admin_message'] = "Статус акции успешно изменен.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Ошибка изменения статуса: " . $stmt_toggle->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_toggle->close();
        } else {
             $_SESSION['admin_message'] = "Ошибка подготовки запроса изменения статуса: " . $connect->error;
             $_SESSION['admin_message_type'] = "error";
        }
        header('Location: promotions_list.php');
        exit();
    }
}

$_SESSION['admin_message'] = "Недопустимое действие или отсутствуют параметры.";
$_SESSION['admin_message_type'] = "error";
header('Location: promotions_list.php');
exit();
?>