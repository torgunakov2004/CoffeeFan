<?php
session_start();
require_once '../config/connect.php'; // Подключение к базе данных

$cart_quantities = [];
$has_items_in_cart = false;
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
    $query_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart = $connect->prepare($query_cart);
    if ($stmt_cart) {
        $stmt_cart->bind_param("i", $user_id);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();
        while ($row_cart = $result_cart->fetch_assoc()) {
            $cart_quantities[$row_cart['product_id']] = $row_cart['quantity'];
        }
        $has_items_in_cart = !empty($cart_quantities);
        $stmt_cart->close();
    } else {
        error_log("Failed to prepare cart query: " . $connect->error);
    }
}

$promotions = [];
$query_promotions = "SELECT id, title, description, image, conditions, type, link FROM promotions WHERE is_active = 1 ORDER BY created_at DESC";
$result_promotions = $connect->query($query_promotions);

if ($result_promotions && $result_promotions->num_rows > 0) {
    while ($row_promo = $result_promotions->fetch_assoc()) {
        $promotions[] = $row_promo;
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Акции - Coffee Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="promotions.css">
    <link href="https://fonts.googleapis.com/css2?family=Righteous&family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header id="header-section">
        <div class="container container-header">
            <div class="header">
                <nav class="nav-main">
                    <ul class="nav-main__list">
                        <li class="nav-main__item"><a class="nav-main__link" href="../index.php">Главная</a></li>
                        <li class="nav-main__item"><a class="nav-main__link" href="../Продукты/index.php">Продукты</a></li>
                        <li class="nav-main__item"><a class="nav-main__link" href="../Рецепты/index.php">Рецепты</a></li>
                        <li class="nav-main__item"><a class="nav-main__link nav-main__link_selected" href="#">Акции</a></li>
                    </ul>
                    <img class="header__logo" src="../img/logo.svg" alt="Логотип Coffee Shop">
                    <ul class="nav-main__list">
                        <li class="nav-main__item"><a class="nav-main__link" href="../О кофе/index.php">О кофе</a></li>
                        <li class="nav-main__item"><a class="nav-main__link" href="../Новости/index.php">Новости</a></li>
                        <li class="nav-main__item"><a class="nav-main__link" href="../Контакты/index.php">Контакты</a></li>
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

    <main class="section-main">
        <div class="container">
            <h2 class="section-subtitle">Наши Акции и Предложения</h2>
            <?php if (!empty($promotions)): ?>
                <div class="items-container">
                    <?php foreach ($promotions as $index => $promo): ?>
                        <?php
                            $icon_number = ($index % 6) + 1;
                            $icon_image_path = '../uploads/promotions/icons/promotions-' . $icon_number . '.png';
                            $promo_image_url = "../" . htmlspecialchars(ltrim($promo['image'], '/'));
                        ?>
                        <div class="item">
                            <div class="item-wrapper">
                                <div class="content-wrapper">
                                    <div class="img-container">
                                        <img class="promo-icon-img" src="<?= htmlspecialchars($icon_image_path) ?>" alt="Иконка акции">

                                        <div class="promo-blob-bg" style="background-image: url('<?= $promo_image_url ?>');"></div>

                                    </div>
                                    <div class="content-text">
                                        <div class="item-name"><?= htmlspecialchars($promo['title']) ?></div>
                                        <div class="item-subtext-container">
                                            <span class="item-subtext subtext-promo-type">Специальное предложение</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="view-more-btn">Подробнее <span class="view-symbol">+</span></div>
                                <div class="item-details-container">
                                    <div class="details-content-wrapper">

                                        <div class="detail">
                                            <span class="detail-title">Акция: </span>
                                            <span class="detail-text detail-name"><?= htmlspecialchars($promo['title']) ?></span>
                                        </div>

                                        <?php if (!empty($promo['conditions'])): ?>
                                        <div class="detail">
                                            <span class="detail-title">Условия: </span>
                                            <span class="detail-text detail-conditions"><?= htmlspecialchars($promo['conditions']) ?></span>
                                        </div>
                                        <?php endif; ?>

                                        <div class="detail detail-desc">
                                            <div class="detail-title">Описание:</div>
                                            <div class="detail-description"><?= nl2br(htmlspecialchars($promo['description'])) ?></div>
                                        </div>

                                        <?php if (!empty($promo['link']) && $promo['link'] !== '#'): ?>
                                        <a href="<?= htmlspecialchars($promo['link']) ?>" target="_blank" rel="noopener noreferrer" class="detail-action-link">
                                            <div class="detail-manual-link">
                                                <div class="manual-icon-container">
                                                    <span class="material-icons-outlined">arrow_forward</span>
                                                </div>
                                                <div class="manual-link-text">Перейти к акции</div>
                                            </div>
                                        </a>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-promotions">К сожалению, активных акций сейчас нет. Загляните позже!</p>
            <?php endif; ?>

        </div>
    </main>
    <?php include_once '../footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.view-more-btn').on('click', function() {
            var thisWrapper = $(this).closest('.item-wrapper');
            var thisItem = thisWrapper.closest('.item');
            var $this = $(this);

            if (!thisItem.hasClass('active')) {
                thisItem.addClass('active');
                $this.html('Меньше <span class="view-symbol">-</span>');
            } else {
                thisItem.removeClass('active');
                $this.html('Подробнее <span class="view-symbol">+</span>');
            }
        });
    });
    </script>

</body>
</html>