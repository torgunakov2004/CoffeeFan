<?php
session_start();
unset($_SESSION['admin']); // Удаляем специфичную сессию админа
unset($_SESSION['user']);  // Также удаляем общую сессию пользователя, если она использовалась для админа
session_destroy();         // На всякий случай, для полной очистки
header('Location: admin_login.php');
exit();
?>