<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Функция для получения всех отзывов
function getAllReviews($connect) {
    $query = "SELECT * FROM `reviews` ORDER BY `id` DESC";
    return mysqli_query($connect, $query);
}

// Функция для изменения статуса отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $review_id = intval($_POST['review_id']);
    $status = htmlspecialchars(trim($_POST['status']));

    $stmt = $connect->prepare("UPDATE `reviews` SET `status` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $status, $review_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Статус отзыва успешно обновлён!";
    } else {
        $_SESSION['error'] = "Ошибка при обновлении статуса: " . $stmt->error;
    }

    $stmt->close();
    header('Location: manage_reviews.php');
    exit();
}

// Получение всех отзывов
$reviews = getAllReviews($connect);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Модерация отзывов</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Модерация отзывов</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Отзыв</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
            <tr>
                <td><?php echo htmlspecialchars($review['id']); ?></td>
                <td><?php echo htmlspecialchars($review['name']); ?></td>
                <td><?php echo htmlspecialchars($review['review']); ?></td>
                <td>
                    <form action="manage_reviews.php" method="post">
                        <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['id']); ?>">
                        <select name="status">
                            <option value="pending" <?php echo $review['status'] == 'pending' ? 'selected' : ''; ?>>На модерации</option>
                            <option value="approved" <?php echo $review['status'] == 'approved' ? 'selected' : ''; ?>>Одобрен</option>
                            <option value="rejected" <?php echo $review['status'] == 'rejected' ? 'selected' : ''; ?>>Отклонён</option>
                        </select>
                        <button type="submit" name="update_status">Обновить</button>
                    </form>
                </td>
                <td>
                    <a href="delete_review.php?id=<?php echo htmlspecialchars($review['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить этот отзыв?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>