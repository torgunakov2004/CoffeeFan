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

    <header id="header-section">
        <div class="container container-header">
            <div class="header">
                 <nav class="nav-main">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../index.php">Главная</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Продукты/index.php">Продукты</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link nav-main__link_selected" href="index.php">Рецепты</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Акции/index.php">Акции</a>
                        </li>
                    </ul>
                    <img class="header__logo" src="../img/logo.svg" alt="CoffeeeFan Logo">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../О кофе/index.php">О кофе</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Новости/index.php">Новости</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Контакты/index.php">Контакты</a>
                        </li>
                    </ul>
                </nav>
                 <div class="header-action">
                    <a href="../local_mall.php">
                        <button class="header-action__cart-1 material-icons-outlined <?php echo $has_items_in_cart ? 'active' : ''; ?>" title="Корзина">shopping_cart</button>
                    </a>
                    <nav class="profile">
                        <nav class="account">
                            <?php
                                $is_admin_session = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
                                $default_avatar_path_from_root = '/img/icons8.png'; // Дефолтный для обычных пользователей
                                $admin_avatar_path_from_root = '/img/admin-avatar.png'; // <-- ПУТЬ К ВАШЕЙ АДМИНСКОЙ АВАТАРКЕ

                                $avatar_to_display = $default_avatar_path_from_root; // По умолчанию

                                if ($is_admin_session) {
                                    // Если это админ, всегда показываем специальную админскую аватарку
                                    // Убедитесь, что файл /img/admin-avatar.png существует
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_from_root)) {
                                        $avatar_to_display = $admin_avatar_path_from_root;
                                    } else {
                                        // Если админская аватарка не найдена, можно использовать дефолтную или другую заглушку
                                        // $avatar_to_display = $default_avatar_path_from_root; // или например '/img/default-admin.png'
                                        error_log("Admin avatar not found: " . $_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_from_root);
                                    }
                                } elseif (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                    // Это обычный пользователь, пытаемся загрузить его аватар
                                    $user_avatar_from_session = '/' . ltrim($_SESSION['user']['avatar'], '/');
                                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $user_avatar_from_session)) {
                                        $avatar_to_display = htmlspecialchars($user_avatar_from_session);
                                    }
                                    // Если у пользователя нет аватара или файл не найден, останется $default_avatar_path_from_root
                                }
                            ?>
                            <img src="<?php echo $avatar_to_display; ?>" class="profile-avatar" alt="Профиль">
                        </nav>
                        <?php if (!isset($_SESSION['user'])): ?>
                            <ul class="submenu">
                                <li><a class="log" href="/auth/authorization.php">Вход</a></li>
                                <li><a class="log" href="/auth/register.php">Регистрация</a></li>
                            </ul>
                        <?php else: // Пользователь авторизован ?>
                            <ul class="submenu">
                                <li class="user-info">
                                    <div class="user-avatar">
                                        <?php
                                            // Логика для аватара в user-info такая же, как для иконки профиля
                                            $avatar_for_user_info = $default_avatar_path_from_root; // Дефолтный для обычных в подменю
                                            if ($is_admin_session) {
                                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_from_root)) {
                                                    $avatar_for_user_info = $admin_avatar_path_from_root;
                                                }
                                            } elseif (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                                $user_avatar_from_session_submenu = '/' . ltrim($_SESSION['user']['avatar'], '/');
                                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $user_avatar_from_session_submenu)) {
                                                    $avatar_for_user_info = htmlspecialchars($user_avatar_from_session_submenu);
                                                } else {
                                                    // Если у пользователя есть запись об аватаре, но файл не найден, можно использовать дефолтный
                                                    $avatar_for_user_info = '/img/default-avatar.jpg';
                                                }
                                            } else {
                                            $avatar_for_user_info = '/img/default-avatar.jpg'; // Для пользователей без аватара в подменю
                                            }
                                        ?>
                                        <img src="<?php echo $avatar_for_user_info; ?>" alt="Аватар">
                                    </div>
                                    <div class="user-details">
                                        <span class="user-name"><?= htmlspecialchars($_SESSION["user"]['first_name'] ?? ($_SESSION["user"]['name'] ?? 'Пользователь')) ?></span>
                                        <span class="user-email"><?= htmlspecialchars($_SESSION["user"]['email'] ?? '') ?></span>
                                    </div>
                                </li>
                                <li class="menu-divider"></li>

                                <?php if ($is_admin_session): // Если это администратор ?>
                                    <li><a class="menu-item admin" href="/admin/admin_dashboard.php"><i class="icon-admin"></i>Админ-панель</a></li>
                                <?php else: // Если это обычный пользователь ?>
                                    <li><a class="menu-item" href="/profile/profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                    <li><a class="menu-item" href="/profile/orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                    <li><a class="menu-item" href="/profile/support.php"><i class="icon-heart"></i>Поддержка</a></li>
                                <?php endif; ?>

                                <li class="menu-divider"></li>
                                <li><a class="menu-item logout" href="/config/logout.php"><i class="icon-logout"></i>Выход</a></li>
                            </ul>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="page-standalone-back-button-wrapper">
                <a href="index.php" class="page-header__back-button-textual" title="Вернуться назад">
                    <span class="material-icons-outlined">arrow_back_ios_new</span> Вернуться назад
                </a>
            </div>

            <h3 class="section-subtitle">Ваши сохраненные рецепты</h3>

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