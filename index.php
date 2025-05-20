<?php
session_start();
require_once 'config/connect.php'; 
$popular_products = mysqli_query($connect, "SELECT id, title, price, image, composition, features FROM `products` WHERE `is_popular` = 1");
if (!$popular_products) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}
$ad_query = "SELECT * FROM advertisements WHERE is_active = 1 LIMIT 1";
$ad_result = mysqli_query($connect, $ad_query);
$advertisement = mysqli_fetch_assoc($ad_result);
$cart_quantities = [];
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $cart_query = "SELECT product_id, quantity FROM cart WHERE user_id = $user_id";
    $cart_result = mysqli_query($connect, $cart_query);

    if ($cart_result) {
        while ($row = mysqli_fetch_assoc($cart_result)) {
            $cart_quantities[$row['product_id']] = $row['quantity'];
        }
    }
}
$latest_news_query = "SELECT * FROM `news` ORDER BY `id` DESC LIMIT 3";
$latest_news = mysqli_query($connect, $latest_news_query);
if (!$latest_news) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}
$popular_drinks = mysqli_query($connect, "SELECT * FROM `menu` WHERE `is_popular` = 1 LIMIT 8");
if (!$popular_drinks) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}
$adTextQuery = "SELECT * FROM advertisement_text LIMIT 1";
$adTextResult = mysqli_query($connect, $adTextQuery);
$adText = mysqli_fetch_assoc($adTextResult);
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
$has_items_in_cart = !empty($cart_quantities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeeeFan</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="JS.js"></script>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <?php
        $current_page_is_faq = true; 
        include_once 'header_footer_elements/header.php'; 
    ?>
    <main>
        <section id="banner-section">
            <div class="container">
                <div class="banner">
                    <video class="banner-video" autoplay muted loop>
                        <source src="img/banner_animation.mp4" type="video/mp4">
                    </video>
                    <div class="banner-info">
                        <h2 class="banner__header">Наслаждайтесь утренним кофе</h2>
                        <p class="banner__text">Кофе варится путем кулачной обжарки зеленых кофейных зерен над горячими углями в мангале. 
                            Не упустите возможность испробовать</p>
                        <a href="О кофе/index.php" class="banner__btn btn-primary">ТЕСТ КОФЕ</a>
                    </div>
                    <button class="banner-video__btn" onclick="toggleVideo()">
                        <span class="banner-video__btn-text">Pause video</span>
                    </button>
                </div>
            </div>
        </section>
        <script>
            function toggleVideo() {
                const video = document.querySelector('.banner-video');
                const btnText = document.querySelector('.banner-video__btn-text');
                if (video.paused) {
                    video.play();
                    btnText.textContent = 'Pause video';
                } else {
                    video.pause();
                    btnText.textContent = 'Play video';
                }
            }
        </script>
        <div class="container">
            <ol class="features">
                <li class="features__item">
                    <span class="features__item_dark">©️</span>Лучший вкус кофе
                    <img class="features__img" src="img/feature-1.jpg" alt="#">
                </li>
                <li class="features__item">
                    <span class="features__item_dark">©️</span>Изящный аромат кофе
                    <img class="features__img" src="img/feature-2.jpg" alt="#">
                </li>
                <li class="features__item">
                    <span class="features__item_dark">©️</span>Правильная обжарка
                    <img class="features__img" src="img/feature-3.jpg" alt="#">
                </li>
            </ol>
            <a class="popular__link btn-primary recip__section" href="Рецепты/index.php">Рецепты</a>
        </div>
        <section id="history-section" class="section-main">
            <div class="container">
                <div class="history-wrap">
                    <img class="history-wrap__img" src="img/feature-2.jpg" alt="#">
                    <img class="history-wrap__img" src="img/feature-4.jpg" alt="#">
                    <img class="history-wrap__img" src="img/feature-1.jpg" alt="#">
                    <div class="history">
                        <h2 class="history__title section-title">Наша история</h2>
                        <h3 class="history__subtitle section-subtitle">Создайте<br>
                            новую историю вместе с нами</h3>
                        <p class="history__text section__text">Как-то раз загорелась соседняя деревня народа оромо. 
                            Уже сейчас никто не скажет, с чего вдруг произошло возгорание, 
                            однако на их местности была кофейная роща. Вот было удивление для местных жителей, 
                            когда горящие плоды вдруг стали ароматно пахнуть. Отсюда пошла традиция обжаривать кофейные зерна.</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="popular-section" class="section-main">
            <div class="container">
                <h2 class="section-title section-title__h2">Популярные продукты</h2>
                <h3 class="section-subtitle">Наши лучшие предложения</h3>
                <div class="popular-wrap">
                    <?php while ($product = mysqli_fetch_assoc($popular_products)): ?>
                        <div class="popular">
                            <div class="popular__flick-container">
                                <div class="popular__content-wrapper">
                                    <div class="popular__image-side">
                                        <img class="popular__img" src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                                    </div>
                                    <div class="popular__info-side">
                                        <h4>Состав:</h4>
                                        <p><?= nl2br(htmlspecialchars($product['composition'] ?? 'Информация о составе отсутствует.')) ?></p>
                                        <h4>Особенности:</h4>
                                        <p><?= nl2br(htmlspecialchars($product['features'] ?? 'Информация об особенностях отсутствует.')) ?></p>
                                    </div>
                                </div>
                            </div>
                            <h3 class="popular__title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <b class="popular__price">Цена - <strong class="popular__price_dark"><?php echo htmlspecialchars($product['price']); ?>₽/</strong> <?php echo htmlspecialchars($product['price'] + 30); ?>₽</b>
                            <div class="popular__actions">
                                <?php if (isset($_SESSION['user'])): ?>
                                    <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <span class="material-icons-outlined">local_mall</span>Добавить
                                    </button>
                                    <button class="remove-from-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <span class="material-icons-outlined">remove_shopping_cart</span>Убрать
                                    </button>
                                    <span class="product-count" id="count-<?php echo $product['id']; ?>">
                                        <?php echo $cart_quantities[$product['id']] ?? 0; ?>
                                    </span>
                                <?php else: ?>
                                    <p class="auth-message">Авторизуйтесь, чтобы добавить товар в корзину.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <a class="popular__link btn-primary" href="Продукты/index.php">Еще...</a>
            </div>
        </section>
        <section id="discover-section" class="section-main">
    <div class="container">
        <div class="discover-wrap">
            <?php if ($advertisement): ?>
                <div class="discover-img-container">
                    <img class="discover-img" src="<?php echo htmlspecialchars($advertisement['image']); ?>" alt="<?php echo htmlspecialchars($advertisement['title']); ?>">
                    <div class="discover-img-overlay">
                        <span class="overlay-text"><?php echo htmlspecialchars($adText['text']); ?></span>
                    </div>
                </div>
                <div class="discover">
                    <h3 class="discover__title section-subtitle"><?php echo htmlspecialchars($advertisement['title']); ?></h3>
                    <p class="section__text"><?php echo htmlspecialchars($advertisement['description']); ?></p>
                    <a class="discover__link btn-primary" href="<?php echo htmlspecialchars($advertisement['link']); ?>">
                        <span>Узнай сейчас</span>
                        <i class="arrow-icon">→</i>
                    </a>
                </div>
            <?php else: ?>
                <!-- Стандартный контент, если рекламы нет -->
            <?php endif; ?>
        </div>
    </div>
</section>
    <section id="menu-section" class="section-main">
        <div class="container">
            <h2 class="section-title">Меню кофейни</h2>
            <h3 class="section-subtitle">Популярные новинки меню</h3>
            <ul class="menu-wrap">
                <?php while ($item = mysqli_fetch_assoc($popular_drinks)): ?>
                    <li class="menu">
                        <div class="menu__img-container">
                            <img class="menu__img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="menu__overlay"></div>
                        </div>
                        <h3 class="menu__title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <b class="menu__price"><?php echo htmlspecialchars($item['price']); ?> ₽</b>
                    </li>
                <?php endwhile; ?>
            </ul>
            <a class="popular__link btn-primary btn-primary__knop" href="Продукты/index.php#menu-section">Еще...</a>
        </div>
    </section>
        <section id="testimonial-section" class="section-main testimonial-carousel">
            <div class="container">
                <h2 class="section-title">Уникальные особенности нашего кофе</h2>
                <h3 class="section-subtitle">Почему наш кофе особенный?</h3>
                <div class="testimonial-wrap">
                        <div class="testimonial" id="review-1" data-testimonial-index="0">
                            <div class="testimonial-item active">
                            <div class="testimonial-data">
                                 <img class="testimonial__img" src="img/COFFEE-1.png" alt="#">
                                <p class="testimonial__text section__text">Наш кофе выращивается на лучших плантациях в высокогорьях Латинской Америки, где идеальные климатические условия способствуют получению зерен с уникальным вкусом и ароматом.</p>
                            </div>
                            <div class="testimonial-info">
                                <div class="testimonial-person">
                                    <span class="testimonial__name">Происхождение</span>
                                     <span class="testimonial__position">☕ Кофейный эстет</span>
                                </div>
                                <ul class="testimonial__list rating__list">
                                     <li class="rating__item material-icons-outlined">star</li>
                                     <li class="rating__item material-icons-outlined">star</li>
                                     <li class="rating__item material-icons-outlined">star</li>
                                     <li class="rating__item material-icons-outlined">star</li>
                                     <li class="rating__item material-icons-outlined">star</li>
                                </ul>
                            </div>
                        </div></div>
                        <div class="testimonial" id="review-2" data-testimonial-index="1">
                            <div class="testimonial-item active">
                            <div class="testimonial-data">
                                 <img class="testimonial__img" src="img/COFFEE-2.png" alt="#">
                                <p class="testimonial__text section__text">Мы используем традиционные методы обжарки, которые позволяют сохранить все натуральные ароматы и вкусовые ноты, делая каждый глоток незабываемым.</p>
                            </div>
                            <div class="testimonial-info">
                                <div class="testimonial-person">
                                    <span class="testimonial__name">Методы обжарки</span>
                                    <span class="testimonial__position">🌱 Искусство кофе</span>
                                </div>
                                <ul class="testimonial__list rating__list">
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                </ul>
                            </div>
                        </div></div>
                        <div class="testimonial" id="review-3"data-testimonial-index="2">
                            <div class="testimonial-item active">
                            <div class="testimonial-data">
                                <img class="testimonial__img" src="img/COFFEE-3.png" alt="#">
                                <p class="testimonial__text section__text">Попробуйте наши эксклюзивные рецепты кофе, которые помогут вам раскрыть весь потенциал наших зерен. От классического эспрессо до креативных кофейных коктейлей!</p>
                            </div>
                            <div class="testimonial-info">
                                <div class="testimonial-person">
                                    <span class="testimonial__name">Рецепты</span>
                                    <span class="testimonial__position">✨ Кофейный маг</span>
                                </div>
                                <ul class="testimonial__list rating__list">
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                    <li class="rating__item material-icons-outlined">star</li>
                                </ul>
                            </div>
                        </div></div>
                        </div>
                    <div class="testimonial-action">
                        <div class="testimonial-count">
                            <b class="testimonial-count__text">1/3 Особенности</b>
                            <progress class="testimonial-count__progress" value="40" max="100"></progress>
                        </div>
                        <div class="testimonial-btn-wrap">
                            <button class="testimonial-btn material-icons-outlined testimonial-btn_west">west</button>
                             <button class="testimonial-btn material-icons-outlined testimonial-btn_active">east</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="latest-news-section" class="section-main">
            <div class="container">
                <h2 class="section-title">Свежие новости</h2>
                <h3 class="section-subtitle">Последние события дня</h3>
                <div class="news-wrap">
                    <?php while ($news_item = mysqli_fetch_assoc($latest_news)): ?>
                        <div class="news-card">
                            <img src="<?php echo htmlspecialchars($news_item['image']); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-card__image">
                            <div class="news-card__content">
                                <h3 class="news-card__title"><?php echo htmlspecialchars($news_item['title']); ?></h3>
                                <p class="news-card__text"><?php echo htmlspecialchars($news_item['content_preview']); ?></p>
                                <a class="news-card__link" href="Новости/news_detail.php?id=<?php echo $news_item['id']; ?>&from=main">Читать далее<span class="material-icons-outlined">arrow_forward</span></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php include_once 'footer.php'; ?>
    </main>
    <script>
        $(document).ready(function() {
            $('.add-to-cart-btn').on('click', function() {
                var productId = $(this).data('product-id');
                var countElement = $('#count-' + productId);
                $.ajax({
                    type: 'POST',
                    url: 'Продукты/add_to_cart.php', 
                    data: { product_id: productId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            countElement.text(response.quantity);
                            toastr.success('Товар добавлен в корзину');
                            updateCartHighlight(true);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        console.error('Произошла ошибка при добавлении товара в корзину.');
                    }
                });
            });

            // Обработка удаления товара
            $('.remove-from-cart-btn').on('click', function() {
                var productId = $(this).data('product-id');
                var countElement = $('#count-' + productId);

                $.ajax({
                    type: 'POST',
                    url: 'Продукты/remove_from_cart.php', // Путь к скрипту удаления
                    data: { product_id: productId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            countElement.text(response.quantity);
                            toastr.success('Товар удален из корзины');
                            // Обновляем подсветку корзины, проверяя, остался ли хоть один товар
                            var totalItemsInCart = 0;
                            $('.product-count').each(function() {
                                totalItemsInCart += parseInt($(this).text()) || 0;
                            });
                            updateCartHighlight(totalItemsInCart > 0 || (response.quantity > 0 && productId == $(this).data('product-id')) ); // Обновленная логика
                                                                                                                                        // или response.cart_empty === false (если сервер присылает)
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        console.error('Произошла ошибка при удалении товара из корзины.');
                    }
                });
            });

            // Функция для обновления подсветки корзины (пример, если она у вас есть)
            function updateCartHighlight(hasItems) {
                var cartButton = $('.header-action__cart-1');
                if (hasItems) {
                    cartButton.addClass('active');
                } else {
                    // Проверяем, действительно ли все счетчики 0
                    var allCountersZero = true;
                    $('.product-count').each(function() {
                        if (parseInt($(this).text()) > 0) {
                            allCountersZero = false;
                            return false; // Прерываем цикл
                        }
                    });
                    if (allCountersZero) {
                        cartButton.removeClass('active');
                    }
                }
            }

            // НОВЫЙ КОД: Обработка клика на карточку popular для показа информации
            $('.popular__flick-container').on('click', function() {
                $(this).closest('.popular').toggleClass('info-visible');
            });
        });
    </script>
</body>
</html>