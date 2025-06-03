<?php
session_start();
require_once '../config/connect.php'; 

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
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - CoffeeFan</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style_info_page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; // Подключаем стандартизированный хедер
    ?>

    <main class="section-main">
        <div class="info-page-container">
            <h1 class="section-subtitle">Часто Задаваемые Вопросы (FAQ)</h1>

            <h2>Заказы и Оплата</h2>
            <p><strong>Как я могу отменить свой заказ?</strong><br>
            Если статус вашего заказа «В обработке», вы можете связаться с нашей службой поддержки через страницу «Контакты» или по указанным на ней телефонам/email для отмены заказа. Если заказ уже отправлен, отмена может быть невозможна, но вы сможете оформить возврат после получения.</p>

            <p><strong>Какие способы оплаты вы принимаете?</strong><br>
            Мы принимаем оплату банковскими картами Visa/Mastercard онлайн через защищенный платежный шлюз. Также возможна оплата при получении заказа (наложенный платеж) в некоторых регионах или при самовывозе.</p>

            <p><strong>Я не получил письмо с подтверждением заказа. Что делать?</strong><br>
            Пожалуйста, проверьте папку «Спам» или «Нежелательная почта» в вашем почтовом ящике. Если письма там нет в течение 15-30 минут после оформления заказа, свяжитесь с нашей службой поддержки, указав детали вашего заказа (если помните) или email, который вы использовали.</p>

            <h2>Доставка</h2>
            <p><strong>Какие у вас есть способы доставки?</strong><br>
            Мы предлагаем курьерскую доставку до двери и самовывоз из наших партнерских пунктов выдачи. Доступные способы и стоимость доставки будут рассчитаны при оформлении заказа в зависимости от вашего региона.</p>

            <p><strong>Сколько времени занимает доставка?</strong><br>
            Сроки доставки зависят от вашего местоположения и выбранного способа доставки. Обычно доставка по Иркутску занимает 1-2 рабочих дня, в другие регионы России – от 3 до 10 рабочих дней. Точные сроки будут указаны при оформлении заказа.</p>

            <h2>Продукция</h2>
            <p><strong>Как правильно хранить ваш кофе?</strong><br>
            Рекомендуем хранить кофейные зерна и молотый кофе в герметичной упаковке, в сухом, темном и прохладном месте, вдали от продуктов с сильным запахом. Это поможет сохранить свежесть и аромат кофе надолго.</p>

            <p><strong>Могу ли я заказать помол зерен?</strong><br>
            Да, для большинства наших сортов кофе в зернах доступна услуга помола. Вы можете выбрать степень помола (для турки, эспрессо-машины, френч-пресса и т.д.) на странице товара перед добавлением в корзину.</p>
            
            <h2>Аккаунт и Профиль</h2>
            <p><strong>Как изменить данные в моем профиле?</strong><br>
            После авторизации на сайте, перейдите в раздел "Мой профиль" (обычно доступен через иконку аккаунта в шапке сайта). Там вы сможете изменить ваше имя, фамилию, адрес доставки и пароль.</p>
            
            <p><strong>Я забыл пароль. Как его восстановить?</strong><br>
            На странице входа нажмите на ссылку "Забыли пароль?" или "Восстановить пароль". Вам будет предложено ввести ваш email, указанный при регистрации. Инструкции по восстановлению пароля будут отправлены на эту почту.</p>

            <p class="last-updated">Последнее обновление: <?php echo date("d.m.Y"); ?></p>
        </div>
    </main>

    <?php include_once '../footer.php'; ?>
</body>
</html>