<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Получение рецепта для редактирования
$id = $_GET['id'];
$recipe_item = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `recipes` WHERE `id` = '$id'"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $ingredients = $_POST['ingredients'];
    $instructions = $_POST['instructions'];

    // Проверка, загружено ли новое изображение
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        $target = "../uploads/" . basename($image);
        
        // Проверка успешной загрузки
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // Успешная загрузка
            $query = "UPDATE `recipes` SET `title` = ?, `ingredients` = ?, `instructions` = ?, `image` = ? WHERE `id` = ?";
            $stmt = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param($stmt, 'ssssi', $title, $ingredients, $instructions, $target, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo "Ошибка загрузки изображения.";
        }
    } else {
        // Если изображение не загружено, просто обновляем другие поля
        $query = "UPDATE `recipes` SET `title` = ?, `ingredients` = ?, `instructions` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $ingredients, $instructions, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: manage_recipes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать рецепт</title>
</head>
<body>
    <h1>Редактировать рецепт</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($recipe_item['id']); ?>">
        <label>Название</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($recipe_item['title']); ?>" required>
        <label>Ингредиенты</label>
        <textarea name="ingredients" required><?php echo htmlspecialchars($recipe_item['ingredients']); ?></textarea>
        <label>Инструкции</label>
        <textarea name="instructions" required><?php echo htmlspecialchars($recipe_item['instructions']); ?></textarea>
        <label>Изображение (оставьте пустым, если не хотите менять)</label>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Сохранить изменения</button>
    </form>
    <a href="manage_recipes.php">Назад к управлению рецептами</a>
</body>
</html>