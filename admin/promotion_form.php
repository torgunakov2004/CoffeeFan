<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');

$promo_data = [
    'id' => null,
    'title' => '',
    'description' => '',
    'image' => '',
    'conditions' => '',
    'type' => 'card',
    'link' => '',
    'is_active' => 1
];
$form_action_title = "Добавить новую акцию";
$form_submit_button_text = "Добавить акцию";
$is_editing = false;
$form_action_type = 'add';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_to_edit = (int)$_GET['id'];
    $stmt_get_promo = $connect->prepare("SELECT * FROM promotions WHERE id = ?");
    if ($stmt_get_promo) {
        $stmt_get_promo->bind_param("i", $id_to_edit);
        $stmt_get_promo->execute();
        $result_promo = $stmt_get_promo->get_result();
        if ($result_promo->num_rows === 1) {
            $promo_data = $result_promo->fetch_assoc();
            $form_action_title = "Редактировать акцию: " . htmlspecialchars($promo_data['title']);
            $form_submit_button_text = "Сохранить изменения";
            $is_editing = true;
            $form_action_type = 'edit';
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
}

$errors_form = $_SESSION['form_errors'] ?? [];
$old_form_data = $_SESSION['old_form_data'] ?? $promo_data;
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

            <form action="process_promotion.php" method="post" enctype="multipart/form-data" class="admin-form">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($old_form_data['id']); ?>">
                <?php endif; ?>
                <input type="hidden" name="action" value="<?php echo $form_action_type; ?>">

                <div class="form-group">
                    <label for="title">Заголовок акции *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($old_form_data['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Полное описание *</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($old_form_data['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="conditions">Краткие условия (для отображения на карточке, необязательно)</label>
                    <input type="text" id="conditions" name="conditions" value="<?php echo htmlspecialchars($old_form_data['conditions'] ?? ''); ?>" placeholder="Например: Скидка 15% до конца недели">
                </div>

                <div class="form-group">
                    <label for="link">Ссылка (URL, необязательно)</label>
                    <input type="url" id="link" name="link" value="<?php echo htmlspecialchars($old_form_data['link'] ?? ''); ?>" placeholder="https://example.com/promo-page">
                </div>

                <div class="form-group">
                    <label for="type">Тип отображения на сайте *</label>
                    <select id="type" name="type" required>
                        <option value="card" <?php echo ($old_form_data['type'] === 'card') ? 'selected' : ''; ?>>Карточка (стандартная)</option>
                        <option value="collage" <?php echo ($old_form_data['type'] === 'collage') ? 'selected' : ''; ?>>Элемент коллажа (для главной страницы)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Изображение <?php echo $is_editing ? '(оставьте пустым, чтобы не менять)' : '*'; ?></label>
                    <?php if ($is_editing && !empty($promo_data['image'])): ?>
                        <div class="current-image-container">
                            <p>Текущее изображение:</p>
                            <img src="../<?php echo htmlspecialchars(ltrim($promo_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/gif, image/webp" <?php echo !$is_editing ? 'required' : ''; ?>>
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
        </div>
    </div>
</body>
</html>