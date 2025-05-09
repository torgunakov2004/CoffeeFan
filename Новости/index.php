<?php
session_start();
require_once '../config/connect.php'; // Подключение к базе данных

// Получение всех новостей
$news = mysqli_query($connect, "SELECT * FROM `news` ORDER BY `date` DESC");

// Получаем количество товаров в корзине для текущего пользователя
$cart_quantities = [];
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $query = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_quantities[$row['product_id']] = $row['quantity'];
    }
}

// Проверка наличия товаров в корзине
$has_items_in_cart = !empty($cart_quantities);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffeee shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<body>
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
                            <a class="nav-main__link" href="../Рецепты/index.php">Рецепты</a>
                        </li>
                        <li class="nav-main__item">
                        <a class="nav-main__link" href="../Акции/index.php">Акции</a>
                        </li>
                    </ul>
                    <img class="header__logo" src="../img/logo.svg" alt="#">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../О кофе/index.php">О кофе</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link nav-main__link_selected" href="#">Новости</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Контакты/index.php">Контакты</a>
                        </li>
                    </ul>
                </nav>
                <div class="header-action">
                <a href="../local_mall.php">
                    <button class="header-action__cart-1 material-icons-outlined <?php echo $has_items_in_cart ? 'active' : ''; ?>">shopping_cart</button>
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
    <section id="section-news" class="section-main">
        <div class="container">
            <h3 class="section-subtitle">Наши актуальные новости</h3>
            <div class="news-wrap">
                <?php while ($news_item = mysqli_fetch_assoc($news)): ?>
                    <div class="news-card">
                    <img src="../<?php echo htmlspecialchars(ltrim($news_item['image'], '/')); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-card__image">
                        <div class="news-card__content">
                            <time class="news__date"><?php echo date('d.m.Y H:i', strtotime($news_item['date'])); ?></time> 
                            <h3 class="news-card__title"><?php echo htmlspecialchars($news_item['title']); ?></h3>
                            <p class="news-card__text"><?php echo htmlspecialchars($news_item['content_preview']); ?></p>
                            <a class="news-card__link" href="news_detail.php?id=<?php echo $news_item['id']; ?>">Читать далее <span class="material-icons-outlined">arrow_forward</span></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php include_once '../footer.php'; ?>
</body>
</html>