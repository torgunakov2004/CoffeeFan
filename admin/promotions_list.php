<?php
session_start();
require_once '../config/connect.php'; // Подключение к БД

// Проверка авторизации администратора
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Получаем все акции из базы данных
$promotions_result = mysqli_query($connect, "SELECT id, title, image, conditions, link, type, is_active FROM promotions ORDER BY created_at DESC");

// Сообщения об успехе/ошибке после операций
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Удаляем сообщение после показа
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление акциями - Админ-панель</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        img.thumbnail { max-width: 80px; max-height: 60px; height: auto; display: block; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Управление акциями</h1>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'спешно') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <a href="promotion_form.php" class="add-btn">Добавить новую акцию</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Изображение</th>
                <th>Заголовок</th>
                <th>Условия</th>
                <th>Тип</th>
                <th>Активно</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($promotions_result) > 0): ?>
                <?php while ($promo = mysqli_fetch_assoc($promotions_result)): ?>
                    <tr>
                        <td><?= $promo['id'] ?></td>
                        <td>
                            <?php if (!empty($promo['image']) && file_exists(htmlspecialchars($promo['image']))): ?>
                                <img src="<?= htmlspecialchars($promo['image']) ?>" alt="Превью" class="thumbnail">
                            <?php else: ?>
                                Нет фото
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($promo['title']) ?></td>
                        <td><?= htmlspecialchars($promo['conditions'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($promo['type']) ?></td>
                        <td>
                             <form action="process_promotion.php?action=toggle" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                <input type="checkbox" name="is_active" value="1" <?= $promo['is_active'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="<?= $promo['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $promo['is_active'] ? 'Да' : 'Нет' ?>
                                </span>
                             </form>
                        </td>
                        <td>
                            <a href="promotion_form.php?id=<?= $promo['id'] ?>" class="action-btn edit-btn">Редакт.</a>
                            <form action="process_promotion.php?action=delete" method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту акцию? Это действие необратимо.');">
                                <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                <button type="submit" class="action-btn delete-btn">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Акций пока нет.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>