<?php
session_start();
require_once '../config/connect.php';

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
    $stmt->close();
}

$has_items_in_cart = !empty($cart_quantities);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeeeFan - Контакты</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="/menu_style.css">
    <style>
        #map { height: 100%; width: 100%; }
        .map-info-container { display: flex; justify-content: space-between; align-items: stretch; gap: 40px; margin-top: 40px; }
        /* Изменение здесь: убран height: 100%; */
        .map-container { flex: 1; min-width: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); /* height: 100%; */ }
        .info-container { flex: 0 0 350px; display: flex; }
        .info-card { background-color: #14110E; border-radius: 12px; padding: 25px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); border: 1px solid #333; width: 100%; display: flex; flex-direction: column; }
        .info-card__title { font: 22px/28px 'Righteous', cursive; color: #C99E71; margin-bottom: 20px; text-align: center; }
        .info-card__content { display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
        .info-card__item { display: flex; align-items: center; gap: 12px; font: 16px/24px 'Urbanist', Arial, Helvetica, sans-serif; color: #FFFFFF; opacity: 0.8; }
        .info-card__item span.material-icons-outlined { color: #C99E71; font-size: 22px; }
        .info-card__image { width: 100%; border-radius: 8px; margin-top: 25px; transition: transform 0.3s ease; object-fit: cover; max-height: 200px; }
        .info-card__image:hover { transform: scale(1.02); }
        @media (max-width: 992px) { .map-info-container { flex-direction: column; } .info-container { flex: 0 0 auto; width: 100%; } #map { height: 400px; } }
        @media (max-width: 576px) { .info-card { padding: 20px; } .info-card__title { font-size: 20px; } .info-card__item { font-size: 14px; } }
    </style>
    <script>
        function initMap() {
            var coffeeShop = [52.2860, 104.2810];
            var map = L.map('map').setView(coffeeShop, 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }).addTo(map);
            L.marker(coffeeShop).addTo(map).bindPopup('<b>CoffeeeFan</b><br>г. Иркутск, ул. Ленина, д.10').openPopup();
        }
    </script>
</head>
<body onload="initMap()">
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <section class="contacts-section section-main">
        <div class="container">
            <h2 class="section-subtitle">Контактная информация</h2>
            <div class="contacts-info">
                <div class="contacts-item">
                    <div class="material-icons-outlined contacts-icon">location_on</div>
                    <p class="contacts-text">г. Иркутск, ул. Ленина, д.10</p>
                </div>
                <div class="contacts-item">
                    <div class="material-icons-outlined contacts-icon">phone</div>
                    <p class="contacts-text">+7 (952) 626-72-36</p>
                </div>
                <div class="contacts-item">
                    <div class="material-icons-outlined contacts-icon">email</div>
                    <p class="contacts-text">info@coffeefan.ru</p>
                </div>
                <div class="contacts-item">
                    <div class="material-icons-outlined contacts-icon">schedule</div>
                    <p class="contacts-text">Пн-Вс: с 11:00 до 20:00</p>
                </div>
            </div>
            <div class="contacts-form">
                <h3 class="form-title">Напишите нам</h3>
                <form action="send_contact.php" method="post" id="contact-form">
                    <input type="text" name="name" placeholder="Ваше имя" required>
                    <input type="email" name="email" placeholder="Ваш Email" required>
                    <textarea name="message" placeholder="Ваше сообщение" required></textarea>
                    <button type="submit" class="btn-primary">Отправить</button>
                </form>
            </div>
        </div>
    </section>

    <main class="section-main">
        <div class="container">
            <section id="map-section">
                <h3 class="section-subtitle">Приходите к нам в гости!</h3>
                <div class="map-info-container">
                    <div class="map-container">
                        <div id="map"></div>
                    </div>
                    <div class="info-container">
                        <div class="info-card">
                            <h3 class="info-card__title">CoffeeFan</h3>
                            <div class="info-card__content">
                                <div class="info-card__item">
                                    <span class="material-icons-outlined">location_on</span>
                                    <p>г. Иркутск, ул. Ленина, д.10</p>
                                </div>
                                <div class="info-card__item">
                                    <span class="material-icons-outlined">phone</span>
                                    <p>+7 (952) 626-72-36</p>
                                </div>
                                <div class="info-card__item">
                                    <span class="material-icons-outlined">schedule</span>
                                    <p>Пн-Вс: 11:00 - 20:00</p>
                                </div>
                            </div>
                            <img class="info-card__image" src="../img/restor.png" alt="Интерьер CoffeeeFan">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
             const contactForm = $('#contact-form');
             let formChanged = false;

             contactForm.find('input, textarea').on('input', function() {
                 formChanged = true;
             });

             contactForm.on('submit', function(event) {
                 event.preventDefault();
                 console.log('Form submitted:', $(this).serialize());
                 formChanged = false;
                 toastr.success('Сообщение отправлено! Мы скоро свяжемся с Вами.');
                 setTimeout(function() {
                     contactForm[0].reset();
                 }, 500);
             });

             $(window).on('beforeunload', function(event) {
                 if (formChanged) {
                     const message = 'Если вы перезагрузите страницу, заполненные данные будут удалены. Продолжить?';
                     event.returnValue = message;
                     return message;
                 }
             });
         });
    </script>
</body>
</html>