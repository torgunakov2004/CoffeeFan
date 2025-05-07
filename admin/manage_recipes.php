<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Функция для получения всех рецептов
function getAllRecipes($connect) {
    $query = "SELECT * FROM `recipes`";
    return mysqli_query($connect, $query);
}

// Получение всех рецептов
$recipes = getAllRecipes($connect);

// Обработка удаления рецепта
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    mysqli_query($connect, "DELETE FROM `recipes` WHERE `id` = '$delete_id'");
    header('Location: manage_recipes.php');
    exit();
}

// Обработка добавления нового рецепта
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_recipe'])) {
    $title = $_POST['title'];
    $ingredients = $_POST['ingredients'];
    $instructions = $_POST['instructions'];
    $image = $_FILES['image']['name'];
    $target = "../uploads/" . basename($image);

    // Перемещение загруженного файла в папку uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Сохранение рецепта в базе данных
        $query = "INSERT INTO `recipes` (`title`, `ingredients`, `instructions`, `image`) VALUES ('$title', '$ingredients', '$instructions', '$target')";
        mysqli_query($connect, $query);
    } else {
        echo "Ошибка загрузки изображения.";
    }
    header('Location: manage_recipes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление рецептами</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Управление рецептами</h1>

    <h2>Добавить новый рецепт</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label>Название</label>
        <input type="text" name="title" required>
        <label>Ингредиенты</label>
        <textarea name="ingredients" required></textarea>
        <label>Инструкции</label>
        <textarea name="instructions" required></textarea>
        <label>Изображение</label>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" name="add_recipe">Добавить рецепт</button>
    </form>

    <h2>Список рецептов</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Ингредиенты</th>
            <th>Инструкции</th>
            <th>Изображение</th>
            <th>Действия</th>
        </tr>
        <?php while ($recipe = mysqli_fetch_assoc($recipes)): ?>
            <tr>
                <td><?php echo htmlspecialchars($recipe['id']); ?></td>
                <td><?php echo htmlspecialchars($recipe['title']); ?></td>
                <td><?php echo htmlspecialchars($recipe['ingredients']); ?></td>
                <td><?php echo htmlspecialchars($recipe['instructions']); ?></td>
                <td><img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" width="50"></td>
                <td>
                    <a href="edit_recipe.php?id=<?php echo htmlspecialchars($recipe['id']); ?>">Редактировать</a>
                    <a href="?delete_id=<?php echo htmlspecialchars($recipe['id']); ?>" onclick="return confirm('Вы уверены, что хотите удалить этот рецепт?');">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="admin_dashboard.php">Назад в админ-панель</a>
</body>
</html>