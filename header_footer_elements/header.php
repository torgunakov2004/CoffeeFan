<?php

global $connect; 
$current_script_name = basename($_SERVER['PHP_SELF']);
$current_dir_name = basename(dirname($_SERVER['PHP_SELF']));
$base_web_path = ''; 

$request_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$is_main_page = ($request_uri_path === $base_web_path . '/index.php' || $request_uri_path === $base_web_path . '/');
$is_produkty_page = (strpos($request_uri_path, $base_web_path . '/Продукты/') === 0);
$is_recepty_page = (strpos($request_uri_path, $base_web_path . '/Рецепты/') === 0);
$is_akcii_page = (strpos($request_uri_path, $base_web_path . '/Акции/') === 0);
$is_o_kofe_page = (strpos($request_uri_path, $base_web_path . '/О кофе/') === 0);
$is_novosti_page = (strpos($request_uri_path, $base_web_path . '/Новости/') === 0);
$is_kontakty_page = (strpos($request_uri_path, $base_web_path . '/Контакты/') === 0);
// Для страниц в list_footer/
$is_faq_page = ($current_dir_name == 'list_footer' && $current_script_name == 'faq.php'); // Оставим пока так для простоты
$is_terms_page = ($current_dir_name == 'list_footer' && $current_script_name == 'terms.php');
$is_support_policy_page = ($current_dir_name == 'list_footer' && $current_script_name == 'support_policy.php');
$is_privacy_page = ($current_dir_name == 'list_footer' && $current_script_name == 'privacy.php');
// Для профиля
$is_profile_page = ($current_dir_name == 'profile' && $current_script_name == 'profile.php');
$is_orders_page = ($current_dir_name == 'profile' && $current_script_name == 'orders.php');
$is_support_chat_page = ($current_dir_name == 'profile' && $current_script_name == 'support.php');


// Логика получения данных для корзины ВНУТРИ хедера
$has_items_in_cart = false;
if (isset($_SESSION['user']['id']) && isset($connect)) { // Проверяем и $connect
    $user_id_for_cart_header = $_SESSION['user']['id'];
    $query_cart_in_header = "SELECT product_id FROM cart WHERE user_id = ? LIMIT 1"; // Достаточно одной записи для проверки
    $stmt_cart_in_header = $connect->prepare($query_cart_in_header);
    if ($stmt_cart_in_header) {
        $stmt_cart_in_header->bind_param("i", $user_id_for_cart_header);
        $stmt_cart_in_header->execute();
        $stmt_cart_in_header->store_result(); // Важно для num_rows
        if ($stmt_cart_in_header->num_rows > 0) {
            $has_items_in_cart = true;
        }
        $stmt_cart_in_header->close();
    } else {
        error_log("Header: Failed to prepare cart check query: " . $connect->error);
    }
}
?>
<style>
    /* Стили шапки */
#header-section { padding: 32px 0; background-color: #1C1814; position: relative; }
.header { display: flex; justify-content: space-between; align-items: center; }
.nav-main { flex-grow: 0.8; display: flex; justify-content: space-between; align-items: center; }
.nav-main__list { display: flex; gap: 70px; font: 400 18px 'Inter', Arial, Helvetica, sans-serif; align-items: center; }
.header__logo { position: absolute; left: 50%; transform: translateX(-50%); animation: logo-glow-filter 3s infinite alternate ease-in-out; border-radius: 50%; width: 72px; height: 60px; }
.nav-main__link_selected { color: #C99E71; font-weight: 700; cursor: default; }
.nav-main__link:hover { color: #C99E71; }
.header-action { display: flex; align-items: center; gap: 30px; margin-top: 8px; }
.profile { position: relative; cursor: pointer; margin-left: 15px; display: flex; align-items: center; height: 42px; }
.account { display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; background-color: rgba(201, 158, 113, 0.1); transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); border: 2px solid transparent; overflow: hidden; position: relative; z-index: 2; }
.account img.profile-avatar { width: 100%; height: 100%; object-fit: cover; transition: all 0.3s ease; border-radius: 50%; } 
.profile:hover .account { transform: scale(1.1); box-shadow: 0 0 0 3px rgba(201, 158, 113, 0.3); }
.profile:hover .account img.profile-avatar { transform: scale(1.1); }
.profile:focus-within .account { border-color: #C99E71; box-shadow: 0 0 0 3px rgba(201, 158, 113, 0.3); }
.header-action__cart-1 { color: #FFFFFF; font-size: 28px; position: relative; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.3s ease; background-color: rgba(201, 158, 113, 0.1); border: 2px solid transparent; }
.header-action__cart-1:hover { color: #C99E71; background-color: rgba(201, 158, 113, 0.2); transform: scale(1.05); box-shadow: 0 0 15px rgba(201, 158, 113, 0.3); }
.header-action__cart-1:focus-visible { outline: none; border-color: #C99E71; box-shadow: 0 0 0 3px rgba(201, 158, 113, 0.3); }
.header-action__cart-1.active { color: #C99E71; background-color: rgba(201, 158, 113, 0.2); animation: cart-pulse 1.5s infinite ease-in-out; }
.header-action__cart-1.active::after { content: ''; position: absolute; top: 4px; right: 4px; width: 10px; height: 10px; background-color: #ff4d4d; border-radius: 50%; border: 2px solid #1C1814; box-shadow: 0 0 5px rgba(255, 77, 77, 0.5); }
.submenu { position: absolute; top: calc(100% + 10px); right: 0; background: #FFF; border-radius: 12px; padding: 15px 0; list-style: none; min-width: 280px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); z-index: 100; opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.25s cubic-bezier(0.23, 1, 0.32, 1); border: 1px solid #eee; }
.profile:hover .submenu, .profile:focus-within .submenu { opacity: 1; visibility: visible; transform: translateY(0); }
.submenu::before { content: ''; position: absolute; bottom: 100%; right: 15px; border-width: 8px; border-style: solid; border-color: transparent transparent #FFF transparent; }
.submenu .log { display: block; padding: 12px 25px; color: #555; text-decoration: none; font-size: 14px; transition: all 0.2s ease; position: relative; }
.submenu .log:hover { background: #f9f5f0; color: #5D3A1A; padding-left: 30px; }
.submenu .log:hover::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #8B5A2B; border-radius: 0 3px 3px 0; }
.user-info { display: flex; align-items: center; padding: 0 20px 15px; margin-bottom: 10px; border-bottom: 1px solid #f0f0f0; }
.user-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 12px; }
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-details { display: flex; flex-direction: column; }
.user-name { font-weight: 600; color: #333; font-size: 16px; margin-bottom: 3px; }
.user-email { font-size: 13px; color: #888; }
.menu-item { display: flex; align-items: center; padding: 12px 25px; color: #555; text-decoration: none; font-size: 14px; transition: all 0.2s ease; position: relative; }
.menu-item i { margin-right: 12px; color: #8B5A2B; font-size: 18px; width: 20px; text-align: center; }
.menu-item:hover { background: #f9f5f0; color: #5D3A1A; padding-left: 30px; }
.menu-item:hover::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #8B5A2B; border-radius: 0 3px 3px 0; }
.menu-divider { height: 1px; background: #f0f0f0; margin: 8px 0; }
.logout { color: #e74c3c !important; } .logout i { color: #e74c3c !important; } .logout:hover { background: #fdeaea !important; }
.admin { color: #9b59b6 !important; } .admin i { color: #9b59b6 !important; } .admin:hover { background: #f5eef8 !important; }
.icon-user:before { content: "👤"; } .icon-orders:before { content: "📦"; } .icon-heart:before { content: "❤️"; } .icon-admin:before { content: "⚙️"; } .icon-logout:before { content: "🚪"; }
</style>
<header id="header-section">
    <div class="container container-header">
        <div class="header">
            <nav class="nav-main">
                <ul class="nav-main__list">
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_main_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/index.php">Главная</a>
                    </li>
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_produkty_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/Продукты/index.php">Продукты</a>
                    </li>
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_recepty_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/Рецепты/index.php">Рецепты</a>
                    </li>
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_akcii_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/Акции/index.php">Акции</a>
                    </li>
                </ul>
                <img class="header__logo" src="<?php echo $base_web_path; ?>/img/logo.svg" alt="CoffeeFan Logo">
                <ul class="nav-main__list">
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_o_kofe_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/О кофе/index.php">О кофе</a>
                    </li>
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_novosti_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/Новости/index.php">Новости</a>
                    </li>
                    <li class="nav-main__item">
                        <a class="nav-main__link <?php echo $is_kontakty_page ? 'nav-main__link_selected' : ''; ?>" href="<?php echo $base_web_path; ?>/Контакты/index.php">Контакты</a>
                    </li>
                </ul>
            </nav>
            <div class="header-action">
                <a href="<?php echo $base_web_path; ?>/local_mall.php">
                    <button class="header-action__cart-1 material-icons-outlined <?php echo $has_items_in_cart ? 'active' : ''; ?>" title="Корзина">shopping_cart</button>
                </a>
                <nav class="profile">
                    <nav class="account">
                         <?php
                            $is_admin_session_header = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
                            $default_avatar_path_header = $base_web_path . '/img/icons8.png';
                            $admin_avatar_path_header = $base_web_path . '/img/admin-avatar.png';
                            $avatar_to_display_header = $default_avatar_path_header;

                            if ($is_admin_session_header) {
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_header)) {
                                     $avatar_to_display_header = $admin_avatar_path_header;
                                } else {
                                     if(defined('STDERR')) { // Проверка, что это не веб-запрос, а CLI (маловероятно для header)
                                        fwrite(STDERR, "Admin avatar for header not found: " . $_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_header . "\n");
                                     } else {
                                        error_log("Admin avatar for header not found: " . $_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_header);
                                     }
                                }
                            } elseif (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                $user_avatar_from_session_header = $base_web_path . '/' . ltrim($_SESSION['user']['avatar'], '/');
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $user_avatar_from_session_header)) {
                                    $avatar_to_display_header = htmlspecialchars($user_avatar_from_session_header);
                                }
                            }
                         ?>
                         <img src="<?php echo $avatar_to_display_header; ?>" class="profile-avatar" alt="Профиль">
                    </nav>
                    <?php if (!isset($_SESSION['user'])): ?>
                        <ul class="submenu">
                            <li><a class="log" href="<?php echo $base_web_path; ?>/auth/authorization.php">Вход</a></li>
                            <li><a class="log" href="<?php echo $base_web_path; ?>/auth/register.php">Регистрация</a></li>
                        </ul>
                    <?php else: ?>
                        <ul class="submenu">
                            <li class="user-info">
                                <div class="user-avatar">
                                    <?php
                                        $avatar_for_user_info_header = $base_web_path . '/img/default-avatar.jpg';
                                        if ($is_admin_session_header) {
                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $admin_avatar_path_header)) {
                                                $avatar_for_user_info_header = $admin_avatar_path_header;
                                            }
                                        } elseif (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                            $user_avatar_submenu_header = $base_web_path . '/' . ltrim($_SESSION['user']['avatar'], '/');
                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $user_avatar_submenu_header)) {
                                                $avatar_for_user_info_header = htmlspecialchars($user_avatar_submenu_header);
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo $avatar_for_user_info_header; ?>" alt="Аватар">
                                </div>
                                <div class="user-details">
                                    <span class="user-name"><?= htmlspecialchars($_SESSION["user"]['first_name'] ?? ($_SESSION["user"]['name'] ?? 'Пользователь')) ?></span>
                                    <span class="user-email"><?= htmlspecialchars($_SESSION["user"]['email'] ?? '') ?></span>
                                </div>
                            </li>
                            <li class="menu-divider"></li>
                            <?php if ($is_admin_session_header): ?>
                                <li><a class="menu-item admin <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : ''; ?>" href="<?php echo $base_web_path; ?>/admin/admin_dashboard.php"><i class="icon-admin"></i>Админ-панель</a></li>
                            <?php else: ?>
                                <li><a class="menu-item <?php echo $is_profile_page ? 'active' : ''; ?>" href="<?php echo $base_web_path; ?>/profile/profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                <li><a class="menu-item <?php echo $is_orders_page ? 'active' : ''; ?>" href="<?php echo $base_web_path; ?>/profile/orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                <li><a class="menu-item <?php echo $is_support_chat_page ? 'active' : ''; ?>" href="<?php echo $base_web_path; ?>/profile/support.php"><i class="icon-heart"></i>Поддержка</a></li>
                            <?php endif; ?>
                            <li class="menu-divider"></li>
                            <li><a class="menu-item logout" href="<?php echo $base_web_path; ?>/config/logout.php"><i class="icon-logout"></i>Выход</a></li>
                        </ul>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
</header>