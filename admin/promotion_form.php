<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_message'] = "Ошибка: Не указан ID акции для редактирования.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: promotions_list.php');
    exit();
}

$id_to_edit = (int)$_GET['id'];
$promo_data_db = null; 
$form_action_title = "Редактировать акцию";
$form_submit_button_text = "Сохранить изменения";
$form_action_type = 'edit';

$stmt_get_promo = $connect->prepare("SELECT * FROM promotions WHERE id = ?");
if ($stmt_get_promo) {
    $stmt_get_promo->bind_param("i", $id_to_edit);
    $stmt_get_promo->execute();
    $result_promo = $stmt_get_promo->get_result();
    if ($result_promo->num_rows === 1) {
        $promo_data_db = $result_promo->fetch_assoc();
        $form_action_title = "Редактировать акцию: " . htmlspecialchars($promo_data_db['title']);
    } else {
        $_SESSION['admin_message'] = "Ошибка: Акция с ID $id_to_edit не найдена.";
        $_SESSION['admin_message_type'] = "error";
        header('Location: promotions_list.php');
        exit();
    }
    $stmt_get_promo->close();
} else {
    $_SESSION['admin_message'] = "Ошибка подготовки запроса: " . $connect->error;
    $_SESSION['admin_message_type'] = "error";
    header('Location: promotions_list.php');
    exit();
}

$errors_form = $_SESSION['form_errors'] ?? [];
$old_form_data = $_SESSION['old_form_data'] ?? $promo_data_db; 
unset($_SESSION['form_errors'], $_SESSION['old_form_data']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $form_action_title; ?> - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1><?php echo $form_action_title; ?></h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="promotions_list.php" class="active">Акции</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2>Данные акции</h2>

            <?php if (!empty($errors_form)): ?>
                <div class="message error">
                    <strong>Обнаружены ошибки:</strong><br>
                    <?php foreach ($errors_form as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($promo_data_db): ?>
            <form action="process_promotion.php" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($old_form_data['id']); ?>">
                <input type="hidden" name="action" value="<?php echo $form_action_type; ?>">

                <div class="form-group">
                    <label for="title_edit">Заголовок акции *</label>
                    <input type="text" id="title_edit" name="title" value="<?php echo htmlspecialchars($old_form_data['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description_edit">Полное описание *</label>
                    <textarea id="description_edit" name="description" rows="5" required><?php echo htmlspecialchars($old_form_data['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="conditions_edit">Краткие условия (для карточки, необязательно)</label>
                    <input type="text" id="conditions_edit" name="conditions" value="<?php echo htmlspecialchars($old_form_data['conditions'] ?? ''); ?>" placeholder="Например: Скидка 15% до конца недели">
                </div>

                <div class="form-group">
                    <label for="link_edit">Ссылка (URL, необязательно)</label>
                    <input type="url" id="link_edit" name="link" value="<?php echo htmlspecialchars($old_form_data['link'] ?? ''); ?>" placeholder="https://example.com/promo-page">
                </div>

                <div class="form-group">
                    <label for="image_edit">Изображение (оставьте пустым, чтобы не менять)</label>
                    <?php if (!empty($promo_data_db['image'])): ?>
                        <div class="current-image-container">
                            <p>Текущее изображение:</p>
                            <img src="../<?php echo htmlspecialchars(ltrim($promo_data_db['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_edit" name="image" accept="image/jpeg, image/png, image/gif, image/webp">
                </div>

                 <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo (isset($old_form_data['is_active']) && $old_form_data['is_active'] == 1) ? 'checked' : ''; ?>>
                        Акция активна (видна на сайте)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save"><?php echo $form_submit_button_text; ?></button>
                    <a href="promotions_list.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
            <?php else: ?>
                <p>Не удалось загрузить данные для редактирования акции.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>