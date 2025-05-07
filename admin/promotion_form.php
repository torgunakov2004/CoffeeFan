<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

$promo = [
    'id' => null,
    'title' => '',
    'description' => '',
    'image' => '',
    'conditions' => '',
    'type' => 'card',
    'link' => '',
    'is_active' => 1
];
$form_title = "Добавить новую акцию";
$is_editing = false;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Экранируем ID на всякий случай, хотя он и приведен к int
    $safe_id = mysqli_real_escape_string($connect, $id);
    $result = mysqli_query($connect, "SELECT * FROM promotions WHERE id = '$safe_id'");

    if ($result && mysqli_num_rows($result) > 0) {
        $promo = mysqli_fetch_assoc($result);
        $form_title = "Редактировать акцию: " . htmlspecialchars($promo['title']);
        $is_editing = true;
    } else {
        $_SESSION['message'] = "Ошибка: Акция с ID $id не найдена.";
        header('Location: promotions_list.php');
        exit();
    }
}

// Сообщения об ошибках валидации
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

// Данные формы при ошибке валидации
$old_data = $_SESSION['old_data'] ?? $promo; // Используем старые данные или данные из БД
unset($_SESSION['old_data']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $form_title ?> - Админ-панель</title>
</head>
<body>
    <h1><?= $form_title ?></h1>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="process_promotion.php" method="post" enctype="multipart/form-data">
        <?php if ($is_editing): ?>
            <input type="hidden" name="id" value="<?= $promo['id'] ?>">
            <input type="hidden" name="action" value="edit">
        <?php else: ?>
             <input type="hidden" name="action" value="add">
        <?php endif; ?>

        <div>
            <label for="title">Заголовок акции *</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($old_data['title']) ?>" required>
        </div>

        <div>
            <label for="description">Описание *</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($old_data['description']) ?></textarea>
        </div>

        <div>
            <label for="conditions">Краткие условия (для стикера/ромба)</label>
            <input type="text" id="conditions" name="conditions" value="<?= htmlspecialchars($old_data['conditions'] ?? '') ?>" placeholder="Например: Скидка 15%">
        </div>

        <div>
            <label for="link">Ссылка (URL)</label>
            <input type="url" id="link" name="link" value="<?= htmlspecialchars($old_data['link'] ?? '') ?>" placeholder="https://... или ../страница.php">
        </div>

        <div>
            <label for="type">Тип отображения *</label>
            <select id="type" name="type" required>
                <option value="card" <?= ($old_data['type'] === 'card') ? 'selected' : '' ?>>Карточка (Card)</option>
                <option value="collage" <?= ($old_data['type'] === 'collage') ? 'selected' : '' ?>>Элемент коллажа (Collage)</option>
            </select>
        </div>

        <div>
            <label for="image">Изображение <?= $is_editing ? '(оставьте пустым, чтобы не менять)' : '*' ?></label>
            <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/gif, image/webp">
            <?php if ($is_editing && !empty($promo['image']) && file_exists(htmlspecialchars($promo['image']))): ?>
                <p style="margin-top: 5px; margin-bottom: 5px;">Текущее изображение:</p>
                <img src="<?= htmlspecialchars($promo['image']) ?>" alt="Текущее изображение" class="current-image">
            <?php endif; ?>
        </div>

         <div>
            <label>
                <input type="checkbox" name="is_active" value="1" <?= ($old_data['is_active'] == 1) ? 'checked' : '' ?>>
                Акция активна (видна на сайте)
            </label>
        </div>

        <div>
            <button type="submit"><?= $is_editing ? 'Сохранить изменения' : 'Добавить акцию' ?></button>
            <a href="promotions_list.php" class="btn-cancel">Отмена</a>
        </div>
    </form>
    <br>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>