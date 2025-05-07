<?php
session_start();
require_once '../config/connect.php'; // Подключение к базе данных

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $news_item = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `news` WHERE `id` = '$id'"));

    if (!$news_item) {
        die("Новость не найдена.");
    }
} else {
    die("ID новости не указан.");
}

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
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($news_item['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="style.css">
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
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
                            <a class="nav-main__link" href="../Новости/index.php">Новости</a>
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
                            <a class="nav-main__link" href="../Новости/index.php">Новости</a>
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
                        <img src="<?php echo $_SESSION['user']['avatar'] ?? '../img/icons8.png'; ?>" class="profile-avatar" alt="Аватар профиля">
                    </nav>
                        <?php if (!$_SESSION): ?>
                            <ul class="submenu">
                                <li><a class="log" href="../auth/authorization.php">Вход</a></li>
                                <li><a class="log" href="../auth/register.php">Регистрация</a></li>
                            </ul>
                        <?php else: ?>
                            <ul class="submenu">
                                <li class="user-info">
                                <div class="user-avatar">
                                    <img src="<?php echo $_SESSION['user']['avatar'] ?? '../img/default-avatar.jpg'; ?>" alt="Аватар">
                                </div>
                                    <div class="user-details">
                                    <span class="user-name"><?= htmlspecialchars($_SESSION["user"]['first_name'] ?? ($_SESSION["user"]['name'] ?? 'Пользователь')) ?></span>
                                        <span class="user-email"><?= htmlspecialchars($_SESSION["user"]['email']) ?></span>
                                    </div>
                                </li>
                                <li class="menu-divider"></li>
                                <li><a class="menu-item" href="profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                <li><a class="menu-item" href="orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                <li><a class="menu-item" href="favorites.php"><i class="icon-heart"></i>Избранное</a></li>
                                <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin']): ?>
                                    <li class="menu-divider"></li>
                                    <li><a class="menu-item admin" href="../admin/admin_dashboard.php"><i class="icon-admin"></i>Админ-панель</a></li>
                                <?php endif; ?>
                                <li class="menu-divider"></li>
                                <li><a class="menu-item logout" href="../config/logout.php"><i class="icon-logout"></i>Выход</a></li>
                            </ul>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <?php
        // Получение ID текущей новости
        $current_id = intval($_GET['id']);

        // Получение предыдущей и следующей новостей
        $prev_news = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `news` WHERE `id` < '$current_id' ORDER BY `id` DESC LIMIT 1"));
        $next_news = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `news` WHERE `id` > '$current_id' ORDER BY `id` ASC LIMIT 1"));
    ?>
    <main>
        <div class="container">
            <div class="news-card_detail">
                <img src="<?php echo htmlspecialchars($news_item['image']); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-image">
                <h2 class="news-title"><?php echo htmlspecialchars($news_item['title']); ?></h2>
                <time class="news-date"><?php echo date('d.m.Y H:i', strtotime($news_item['date'])); ?></time>
                <p class="news-content"><?php echo nl2br(htmlspecialchars($news_item['content'])); ?></p>
                <div class="navigation-buttons">
                    <?php if ($next_news): ?>
                        <a href="news_detail.php?id=<?php echo $next_news['id']; ?>" class="btn-primary">
                            <span class="material-icons-outlined">arrow_back</span> Предыдущая
                        </a>                 
                    <?php endif; ?>
                    
                    <a href="index.php" class="btn-primary">Назад к новостям</a>
                    
                    <?php if ($prev_news): ?>
                        <a href="news_detail.php?id=<?php echo $prev_news['id']; ?>" class="btn-primary">
                            Следующая <span class="material-icons-outlined">arrow_forward</span>
                        </a>                 
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer id="footer-section">
        <div class="container">
            <div class="footer">
                <img class="footer__img" src="../img/logo.svg" alt="#">
                <ul class="footer__list">
                    <li class="footer__item">
                        <a class="footer__link" href="../index.php">Главная</a>
                    </li>
                    <li class="footer__item">
                        <a class="footer__link" href="../Рецепты/index.php">Рецепты</a>
                    </li>
                    <li class="footer__item">
                        <a class="footer__link" href="../Продукты/index.php">Продукты</a>
                    </li>
                    <li class="footer__item">
                        <a class="footer__link" href="../Меню/index.php">Меню</a>
                    </li>
                    <li class="footer__item">
                        <a class="footer__link" href="../Тесты/index.php">Тесты</a>
                    </li>
                    <li class="footer__item">
                        <a class="footer__link" href="../Контакты/index.php">Контакты</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                <p class="footer-copyright__text">e-Wiwonti © 2025. Все права защищены</p>
            </div>
        </div>
    </footer>
</body>
</html>