<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$recipe_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$recipe_data = null;
$errors = [];
$message = '';
$message_type = '';

$upload_dir = '../uploads/recipes/';

if ($recipe_id_to_edit > 0) {
    $stmt_get = $connect->prepare("SELECT * FROM recipes WHERE id = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $recipe_id_to_edit);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows === 1) {
            $recipe_data = $result_get->fetch_assoc();
        } else {
            $_SESSION['admin_message'] = "Рецепт с ID $recipe_id_to_edit не найден.";
            $_SESSION['admin_message_type'] = "error";
            header('Location: manage_recipes.php');
            exit();
        }
        $stmt_get->close();
    } else {
        $errors[] = "Ошибка загрузки данных рецепта: " . $connect->error;
        $recipe_data = $_POST; 
    }
} else {
    $_SESSION['admin_message'] = "Некорректный ID рецепта.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: manage_recipes.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recipe'])) {
    $id = $recipe_id_to_edit;
    $title = trim($_POST['title']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    
    $current_db_image_path = $recipe_data['image']; 
    $new_image_path_db = $current_db_image_path; 

    if (empty($title)) $errors[] = "Название рецепта обязательно.";
    if (empty($ingredients)) $errors[] = "Ингредиенты обязательны.";
    if (empty($instructions)) $errors[] = "Инструкции обязательны.";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid('recipe_', true) . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name; 
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) { // 3MB
            $errors[] = "Файл слишком большой. Максимум 3MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $new_image_path_db = 'uploads/recipes/' . $file_name; 
                if (!empty($current_db_image_path) && file_exists('../' . ltrim($current_db_image_path, '/'))) {
                    unlink('../' . ltrim($current_db_image_path, '/'));
                }
            } else {
                $errors[] = "Ошибка загрузки нового файла изображения.";
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Ошибка при загрузке изображения (код: " . $_FILES['image']['error'] . ").";
    }

    if (empty($errors)) {
        $stmt_update = $connect->prepare("UPDATE `recipes` SET title = ?, ingredients = ?, instructions = ?, image = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("ssssi", $title, $ingredients, $instructions, $new_image_path_db, $id);
            if ($stmt_update->execute()) {
                $_SESSION['admin_message'] = "Рецепт успешно обновлен.";
                $_SESSION['admin_message_type'] = "success";
                header('Location: manage_recipes.php');
                exit();
            } else {
                $errors[] = "Ошибка обновления рецепта: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Ошибка подготовки запроса обновления: " . $connect->error;
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = "error";
        $recipe_data = $_POST;
        $recipe_data['image'] = $current_db_image_path;
        $recipe_data['id'] = $id;
    }
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать Рецепт - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Редактирование Рецепта</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Главная</a></li>
                    <li><a href="manage_recipes.php" class="active">Рецепты</a></li>
                    <li class="logout-link"><a href="logout.php">Выход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-content">
            <h2><?php echo htmlspecialchars($recipe_data['title'] ?? 'Редактирование'); ?></h2>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="edit_recipe.php?id=<?php echo $recipe_id_to_edit; ?>" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($recipe_data['id']); ?>">
                
                <div class="form-group">
                    <label for="title_edit">Название:</label>
                    <input type="text" id="title_edit" name="title" value="<?php echo htmlspecialchars($recipe_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="ingredients_edit">Ингредиенты:</label>
                    <textarea id="ingredients_edit" name="ingredients" rows="4" required><?php echo htmlspecialchars($recipe_data['ingredients'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="instructions_edit">Инструкции:</label>
                    <textarea id="instructions_edit" name="instructions" rows="6" required><?php echo htmlspecialchars($recipe_data['instructions'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_edit">Изображение (оставьте пустым, чтобы не менять):</label>
                    <?php if (!empty($recipe_data['image'])): ?>
                        <div class="current-image-container">
                            <img src="../<?php echo htmlspecialchars(ltrim($recipe_data['image'],'/')); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_edit" name="image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_recipe" class="btn-save">Сохранить изменения</button>
                    <a href="manage_recipes.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>