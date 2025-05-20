<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';
$upload_dir = '../uploads/recipes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_recipe'])) {
    $title = trim($_POST['title']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $errors_add = [];

    if (empty($title)) $errors_add[] = "Название рецепта обязательно.";
    if (empty($ingredients)) $errors_add[] = "Ингредиенты обязательны.";
    if (empty($instructions)) $errors_add[] = "Инструкции обязательны.";

    $image_path_db = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid('recipe_', true) . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($imageFileType, $allowed_types)) {
            $errors_add[] = "Допускаются только JPG, JPEG, PNG, GIF, WEBP файлы изображений.";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors_add[] = "Файл слишком большой. Максимум 3MB.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path_db = 'uploads/recipes/' . $file_name;
            } else {
                $errors_add[] = "Ошибка загрузки файла изображения.";
            }
        }
    } else {
        $errors_add[] = "Изображение обязательно для нового рецепта.";
    }

    if (empty($errors_add)) {
        $stmt_add = $connect->prepare("INSERT INTO `recipes` (title, ingredients, instructions, image) VALUES (?, ?, ?, ?)");
        if ($stmt_add) {
            $stmt_add->bind_param("ssss", $title, $ingredients, $instructions, $image_path_db);
            if ($stmt_add->execute()) {
                $_SESSION['admin_message'] = "Рецепт успешно добавлен.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Ошибка добавления рецепта: " . $stmt_add->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_add->close();
        } else {
             $_SESSION['admin_message'] = "Ошибка подготовки запроса добавления: " . $connect->error;
             $_SESSION['admin_message_type'] = "error";
        }
    } else {
        $_SESSION['admin_message'] = implode("<br>", $errors_add);
        $_SESSION['admin_message_type'] = "error";
    }
    header('Location: manage_recipes.php');
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $success = true;

    $stmt_delete_saved = $connect->prepare("DELETE FROM `saved_recipes` WHERE `recipe_id` = ?");
    if ($stmt_delete_saved) {
        $stmt_delete_saved->bind_param("i", $delete_id);
        if (!$stmt_delete_saved->execute()) {
            $_SESSION['admin_message'] = "Ошибка удаления связанных сохраненных рецептов: " . $stmt_delete_saved->error;
            $_SESSION['admin_message_type'] = "error";
            $success = false;
        }
        $stmt_delete_saved->close();
    } else {
        $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления сохраненных рецептов: " . $connect->error;
        $_SESSION['admin_message_type'] = "error";
        $success = false;
    }

    if ($success) {
        $stmt_get_img = $connect->prepare("SELECT image FROM recipes WHERE id = ?");
        if ($stmt_get_img) {
            $stmt_get_img->bind_param("i", $delete_id);
            $stmt_get_img->execute();
            $img_result = $stmt_get_img->get_result();
            if($img_row = $img_result->fetch_assoc()){
                if(!empty($img_row['image']) && file_exists('../' . ltrim($img_row['image'], '/'))){
                    unlink('../' . ltrim($img_row['image'], '/'));
                }
            }
            $stmt_get_img->close();
        } else {
            error_log("Manage Recipes: Failed to prepare image select query for delete: " . $connect->error);
        }

        $stmt_delete_recipe = $connect->prepare("DELETE FROM `recipes` WHERE `id` = ?");
        if ($stmt_delete_recipe) {
            $stmt_delete_recipe->bind_param("i", $delete_id);
            if ($stmt_delete_recipe->execute()) {
                if ($stmt_delete_recipe->affected_rows > 0) {
                     $_SESSION['admin_message'] = "Рецепт и все связанные с ним сохранения успешно удалены.";
                     $_SESSION['admin_message_type'] = "success";
                } else {
                    $_SESSION['admin_message'] = "Рецепт не найден (возможно, уже удален).";
                    $_SESSION['admin_message_type'] = "info";
                }
            } else {
                $_SESSION['admin_message'] = "Ошибка удаления рецепта: " . $stmt_delete_recipe->error;
                $_SESSION['admin_message_type'] = "error";
            }
            $stmt_delete_recipe->close();
        } else {
            $_SESSION['admin_message'] = "Ошибка подготовки запроса удаления рецепта: " . $connect->error;
            $_SESSION['admin_message_type'] = "error";
        }
    }
    
    header('Location: manage_recipes.php');
    exit();
}

$stmt_recipes = $connect->prepare("SELECT * FROM `recipes` ORDER BY id DESC");
$recipes_arr = [];
if ($stmt_recipes) {
    $stmt_recipes->execute();
    $result_recipes = $stmt_recipes->get_result();
    while ($row = $result_recipes->fetch_assoc()) {
        $recipes_arr[] = $row;
    }
    $stmt_recipes->close();
} else {
    error_log("Manage Recipes: Failed to prepare recipe list query: " . $connect->error);
}

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Рецептами - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <img class="header__logo" src="<?php echo $base_url_prefix_for_links; ?>../img/logo.svg" alt="CoffeeFan Logo">
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="manage_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">Заказы</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAddRecipeFormBtn">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новый рецепт
        </button>

        <div class="admin-content form-container" id="addRecipeFormContainer" style="display: none;">
            <h2>Добавить новый рецепт</h2>
            <form action="manage_recipes.php" method="post" enctype="multipart/form-data" class="admin-form" id="addRecipeForm">
                <div class="form-group">
                    <label for="title">Название:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="ingredients">Ингредиенты:</label>
                    <textarea id="ingredients" name="ingredients" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="instructions">Инструкции:</label>
                    <textarea id="instructions" name="instructions" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image_add">Изображение:</label>
                    <input type="file" id="image_add" name="image" accept="image/*" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_recipe" class="btn-save">Добавить рецепт</button>
                    <button type="button" class="btn-cancel" id="cancelAddRecipeBtn">Отмена</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список рецептов</h2>
                <div class="search-input-container">
                    <input type="text" id="recipeSearchInput" placeholder="Поиск по названию...">
                </div>
            </div>
            <table class="admin-table" id="recipesTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                        <th>Изображение</th>
                        <th class="sortable" data-sort="title">Название</th>
                        <th>Ингредиенты</th>
                        <th>Инструкции</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="recipesTableBody">
                    <?php if (!empty($recipes_arr)): ?>
                        <?php foreach ($recipes_arr as $recipe): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipe['id']); ?></td>
                                <td>
                                    <?php if (!empty($recipe['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($recipe['image'],'/')); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($recipe['title']); ?></td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($recipe['ingredients']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($recipe['ingredients'], 0, 100) . (mb_strlen($recipe['ingredients']) > 100 ? '...' : '')); ?>
                                </td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($recipe['instructions']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($recipe['instructions'], 0, 100) . (mb_strlen($recipe['instructions']) > 100 ? '...' : '')); ?>
                                </td>
                                <td class="actions">
                                    <a href="edit_recipe.php?id=<?php echo htmlspecialchars($recipe['id']); ?>" class="edit-btn">Редакт.</a>
                                    <a href="manage_recipes.php?delete_id=<?php echo htmlspecialchars($recipe['id']); ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить этот рецепт?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noRecipesRow">
                            <td colspan="6">Рецепты не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p id="recipesTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Рецепты по вашему запросу не найдены.</p>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const $addRecipeFormContainer = $('#addRecipeFormContainer');
    const $toggleAddRecipeFormBtn = $('#toggleAddRecipeFormBtn');
    const $cancelAddRecipeBtn = $('#cancelAddRecipeBtn');

    $toggleAddRecipeFormBtn.on('click', function() {
        $addRecipeFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $addRecipeFormContainer.is(':visible') ? 'remove_circle_outline' : 'add_circle_outline'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $addRecipeFormContainer.is(':visible') ? 'Скрыть форму' : 'Добавить новый рецепт'
        );
    });
    $cancelAddRecipeBtn.on('click', function() {
        $addRecipeFormContainer.slideUp(300);
        $toggleAddRecipeFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddRecipeFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новый рецепт');
        $('#addRecipeForm')[0].reset();
    });

    const $recipesTable = $('#recipesTable');
    const $recipesTableBody = $('#recipesTableBody');
    const $recipeSearchInput = $('#recipeSearchInput');
    const $noRecipesRowPHP = $('#noRecipesRow');
    const $recipesEmptyMessageJS = $('#recipesTableEmptyMessage');

    $recipesTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $recipesTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $recipesTableBody.find('tr:not(#noRecipesRow)').get();
        rows.sort(function(a, b) {
            let valA = $(a).children('td').eq($th.index()).text().trim();
            let valB = $(b).children('td').eq($th.index()).text().trim();
            if (type === 'number') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $recipesTableBody.append(row);
        });
    });

    $recipeSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $recipesTableBody.find('tr:not(#noRecipesRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                if (index !== 1 && index !== 5) { 
                    rowText += $(this).text().toLowerCase() + ' ';
                }
            });
            if (rowText.includes(searchTerm)) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        if ($noRecipesRowPHP.length > 0 && $recipesTableBody.find('tr:not(#noRecipesRow):visible').length === 0 && searchTerm === "") {
            $noRecipesRowPHP.show();
            $recipesEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $recipesTableBody.find('tr:not(#noRecipesRow)').length > 0) {
            $noRecipesRowPHP.hide();
            $recipesEmptyMessageJS.show();
        } else {
            $noRecipesRowPHP.hide();
            $recipesEmptyMessageJS.hide();
        }
    });
    if ($recipesTableBody.find('tr:not(#noRecipesRow)').length === 0 && $noRecipesRowPHP.is(':visible')) {
    } else {
         $recipeSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>