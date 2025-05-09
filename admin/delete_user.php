<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $user_id_to_delete = (int)$_POST['id'];

    if ($user_id_to_delete === 0) {
        $_SESSION['admin_message'] = "Некорректный ID пользователя для удаления.";
        $_SESSION['admin_message_type'] = "error";
    } elseif ($user_id_to_delete == $_SESSION['admin']['id']) {
        $_SESSION['admin_message'] = "Вы не можете удалить свой собственный аккаунт администратора.";
        $_SESSION['admin_message_type'] = "error";
    } else {
        $stmt_delete = $connect->prepare("DELETE FROM user WHERE id = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $user_id_to_delete);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['admin_message'] = "Пользователь успешно удален.";
                    $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Пользователь с указанным ID не найден или уже удален.";
                    $_SESSION['admin_message_type'] = "error";
                }
            } else {
                $_SESSION['admin_message'] = "Ошибка удаления пользователя: " . $stmt_delete->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_delete->close();
        } else {
             $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления: " . $connect->error;
             $_SESSION['admin_message_type'] = "error";
        }
    }
} else {
    $_SESSION['admin_message'] = "Недопустимый запрос.";
    $_SESSION['admin_message_type'] = "error";
}

header('Location: admin_dashboard.php');
exit();
?>