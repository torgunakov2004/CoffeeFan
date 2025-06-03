<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/authorization.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe'])) {
    $recipe_id_to_delete = intval($_POST['recipe_id']);
    $delete_stmt = $connect->prepare("DELETE FROM `saved_recipes` WHERE `user_id` = ? AND `recipe_id` = ?");
    $delete_stmt->bind_param("ii", $user_id, $recipe_id_to_delete);
    if ($delete_stmt->execute()) {
         $_SESSION['message'] = 'Рецепт успешно удален.';
     } else {
         $_SESSION['error'] = 'Ошибка удаления рецепта.';
     }
    $delete_stmt->close();
    header('Location: my_recipes.php');
    exit();
}

$recipes_stmt = $connect->prepare("SELECT r.* FROM `saved_recipes` sr JOIN `recipes` r ON sr.recipe_id = r.id WHERE sr.user_id = ? ORDER BY sr.id DESC");
$recipes_stmt->bind_param("i", $user_id);
$recipes_stmt->execute();
$recipes_result = $recipes_stmt->get_result();

$cart_quantities = [];
$query_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
$stmt_cart = $connect->prepare($query_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();
while ($row_cart = $result_cart->fetch_assoc()) {
    $cart_quantities[$row_cart['product_id']] = $row_cart['quantity'];
}
$stmt_cart->close();
$has_items_in_cart = !empty($cart_quantities);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeeeFan - Мои рецепты</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <main>
        <div class="container">
            <h3 class="section-subtitle">Ваши сохраненные рецепты</h3>
            <div class="page-standalone-back-button-wrapper">
                    <a href="index.php" class="page-header__back-button-textual" title="Вернуться назад">
                        <span class="material-icons-outlined">arrow_back_ios_new</span> Вернуться назад
                    </a>
                </div>
            <section>
                <ul class="card-list">
                    <?php if ($recipes_result->num_rows > 0): ?>
                        <?php while ($recipe = $recipes_result->fetch_assoc()): ?>
                            <li data-id="<?php echo $recipe['id']; ?>">
                                 <article>
                                     <section>
                                     <img src="../<?php echo htmlspecialchars(ltrim($recipe['image'], '/')); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="recipe-image">
                                         <!-- УДАЛЕН onsubmit ИЗ ФОРМЫ -->
                                         <form method="post" class="delete-recipe-form">
                                             <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                             <button type="submit" name="delete_recipe" class="delete-recipe-btn" title="Удалить рецепт">✖</button>
                                         </form>
                                         <div class="content">
                                             <h2 class="recipe-content-title"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                                             <div class="recipe-details">
                                                 <p><strong>Ингредиенты:</strong><br><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>
                                                 <p><strong>Инструкции:</strong><br><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>
                                             </div>
                                         </div>
                                     </section>
                                 </article>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="no-recipes-item">
                            <p class="no-recipes-message">У вас нет сохраненных рецептов.</p>
                        </li>
                    <?php endif; ?>
                    <?php $recipes_stmt->close(); ?>
                </ul>
            </section>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
     <script>
        $(document).ready(function() {
            <?php
            if (isset($_SESSION['message'])) {
                echo "toastr.success('" . addslashes($_SESSION['message']) . "');";
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error'])) {
                echo "toastr.error('" . addslashes($_SESSION['error']) . "');";
                unset($_SESSION['error']);
            }
            ?>
        });
     </script>
</body>
</html>