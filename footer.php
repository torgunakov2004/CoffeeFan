<?php
session_start();

$current_script_path_footer = dirname($_SERVER['SCRIPT_NAME']);
$current_script_path_footer = str_replace('\\', '/', $current_script_path_footer);

if ($current_script_path_footer === '/' ) {
    $baseUrl_footer = '';
} else {
    $path_segments_footer = array_filter(explode('/', trim($current_script_path_footer, '/')));
    $depth_footer = count($path_segments_footer);
    $baseUrl_footer = str_repeat('../', $depth_footer);
}
?>
<style>
.site-footer.new_footer_area {
    background: linear-gradient(180deg, #0f0c08 0%, #14110E 100%);
    color: #adb5bd;
    font-family: 'Urbanist', Arial, Helvetica, sans-serif;
    position: relative;
    overflow: hidden;
    border-top: 1px solid rgba(201, 158, 113, 0.1);
}

.site-footer .new_footer_top {
    padding: 80px 0 120px;
    position: relative;
    z-index: 2;
    overflow-x: hidden;
}

.site-footer .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}

.site-footer .footer_bottom {
    padding: 30px 0;
    background: rgba(20, 17, 14, 0.9);
    border-top: 1px solid rgba(201, 158, 113, 0.15);
    position: relative;
    z-index: 2;
}

.site-footer .footer_bottom p {
    font-size: 14px;
    font-weight: 400;
    line-height: 24px;
    color: #868e96;
    margin-bottom: 0;
}

.site-footer .f_widget {
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
}

.site-footer .f_widget .widget-content-wrapper {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.site-footer .company_widget p {
    font-size: 15px;
    font-weight: 400;
    line-height: 1.7;
    color: #adb5bd;
    margin-bottom: 25px;
    opacity: 0.8;
}

.site-footer .f_subscribe_two {
    margin-top: auto;
    position: relative;
}

.site-footer .f_subscribe_two .form-control.memail {
    background: rgba(28, 24, 20, 0.8);
    border: 1px solid rgba(68, 68, 68, 0.5);
    border-radius: 6px;
    height: 50px;
    line-height: 50px;
    padding: 0 20px;
    color: #fff;
    margin-bottom: 15px;
    font-family: 'Urbanist', Arial, Helvetica, sans-serif;
    font-size: 15px;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.site-footer .f_subscribe_two .form-control.memail:focus {
    border-color: #C99E71;
    box-shadow: 0 0 0 2px rgba(201, 158, 113, 0.2);
}

.site-footer .f_subscribe_two .form-control.memail::placeholder {
    color: #777;
    opacity: 0.7;
}

.site-footer .f_subscribe_two .btn_get {
    border-width: 1px;
    margin-top: 0;
    background: linear-gradient(135deg, #C99E71 0%, #bd864b 100%);
    color: #14110E;
    border: none;
    font-weight: 600;
    padding: 14px 25px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-family: 'Inter', Arial, Helvetica, sans-serif;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(201, 158, 113, 0.2);
}

.site-footer .f_subscribe_two .btn_get:hover {
    background: linear-gradient(135deg, #bd864b 0%, #a87a42 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(201, 158, 113, 0.3);
}

.site-footer .mchimp-errmessage,
.site-footer .mchimp-sucmessage {
    font-size: 14px;
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    display: none;
}

.site-footer .mchimp-errmessage {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border-left: 3px solid #e74c3c;
}

.site-footer .mchimp-sucmessage {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border-left: 3px solid #2ecc71;
}

.site-footer a {
    color: #adb5bd;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.site-footer a:hover,
.site-footer a:focus {
    color: #C99E71;
    text-decoration: none;
    outline: none;
}

.site-footer .new_footer_top .f_widget.about-widget .f_list {
    list-style: none;
    padding-left: 0;
    margin-bottom: 0;
}

.site-footer .new_footer_top .f_widget.about-widget .f_list li {
    margin-bottom: 12px;
    position: relative;
    padding-left: 15px;
}

.site-footer .new_footer_top .f_widget.about-widget .f_list li:before {
    content: "•";
    color: #C99E71;
    position: absolute;
    left: 0;
    top: 0;
    font-size: 18px;
    line-height: 1;
}

.site-footer .new_footer_top .f_widget.about-widget .f_list li a {
    color: #adb5bd;
    font-size: 15px;
    display: inline-block;
    transition: all 0.3s ease;
    padding: 2px 0;
}

.site-footer .new_footer_top .f_widget.about-widget .f_list li a:hover {
    color: #C99E71;
    transform: translateX(5px);
}

.site-footer .f_widget.social-widget .widget-content-wrapper {
    justify-content: space-between;
}

.site-footer .f_social_icon {
    margin-bottom: 25px;
    display: flex;
    gap: 12px;
}

.site-footer .f_social_icon a {
    width: 44px;
    height: 44px;
    line-height: 44px;
    background: rgba(36, 33, 31, 0.8);
    border: 1px solid rgba(51, 51, 51, 0.5);
    font-size: 18px;
    border-radius: 50%;
    color: #adb5bd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.site-footer .f_social_icon a:hover {
    background: #C99E71;
    border-color: #C99E71;
    color: #14110E;
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 8px 16px rgba(201, 158, 113, 0.3);
}

.site-footer .f-title {
    margin-bottom: 25px;
    padding-bottom: 15px;
    color: #FFFFFF;
    font-family: 'Righteous', cursive;
    font-weight: 600;
    font-size: 22px;
    position: relative;
}

.site-footer .f-title:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 2px;
    background: linear-gradient(90deg, #C99E71 0%, rgba(201, 158, 113, 0) 100%);
}

.site-footer .pl_70 {
    padding-left: 0;
}

.site-footer .coffee_time_gif_container {
    margin-top: 20px;
    text-align: center;
    padding-top: 20px;
    display: flex;
    justify-content: center;
    align-items: flex-end;
    flex-grow: 1;
    position: relative; /* Для позиционирования выпрыгивающих GIF */
    cursor: pointer; /* Показываем, что на GIF можно нажать */
}

.site-footer .coffee_time_gif {
    max-width: 120px;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    transition: transform 0.5s ease;
    position: relative; /* Чтобы z-index работал */
    z-index: 1;
}

.site-footer .coffee_time_gif:hover {
    transform: scale(1.05) rotate(-2deg);
}

/* Стили для "выпрыгивающих" GIF */
.jumping-cat-gif {
    position: absolute;
    width: 80px;
    height: auto;
    border-radius: 8px;
    z-index: 10;
    pointer-events: none;
    animation: jumpOutEffect 0.8s ease-out forwards;
    opacity: 0;
}

@keyframes jumpOutEffect {
    0% {
        transform: scale(0.1) translate(-50%, -50%);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: scale(1) translate(var(--tx, 0), var(--ty, 0));
        opacity: 0;
    }
}

.site-footer .footer_bottom .icon_heart {
    color: #e74c3c;
    font-style: normal;
    animation: heartbeat 1.5s infinite;
    display: inline-block;
}

@keyframes heartbeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.1); }
    50% { transform: scale(1); }
    75% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.site-footer .footer_bottom .footer-credits a {
    color: #C99E71;
    font-weight: 500;
    position: relative;
}

.site-footer .footer_bottom .footer-credits a:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 0;
    height: 1px;
    background: #C99E71;
    transition: width 0.3s ease;
}

.site-footer .footer_bottom .footer-credits a:hover {
    color: #FFFFFF;
}

.site-footer .footer_bottom .footer-credits a:hover:after {
    width: 100%;
}

/* СТИЛИ АНИМАЦИИ ИЗ СТАРОГО ФУТЕРА (машинка, велосипедист) - НАЧАЛО */
.site-footer .new_footer_top .footer_bg {
    position: absolute;
    bottom: 0;
    left: 0;
    background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEigB8iI5tb8WSVBuVUGc9UjjB8O0708X7Fdic_4O1LT4CmLHoiwhanLXiRhe82yw0R7LgACQ2IhZaTY0hhmGi0gYp_Ynb49CVzfmXtYHUVKgXXpWvJ_oYT8cB4vzsnJLe3iCwuzj-w6PeYq_JaHmy_CoGoa6nw0FBo-2xLdOPvsLTh_fmYH2xhkaZ-OGQ/s16000/footer_bg.png") no-repeat scroll center 0;
    width: 100%;
    height: 266px;
    opacity: 0.15; /* Сделал чуть менее заметным, т.к. нет другого фонового узора */
    z-index: 0;
    pointer-events: none;
}

.site-footer .new_footer_top .footer_bg .footer_bg_one {
    background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEia0PYPxwT5ifToyP3SNZeQWfJEWrUENYA5IXM6sN5vLwAKvaJS1pQVu8mOFFUa_ET4JuHNTFAxKURFerJYHDUWXLXl1vDofYXuij45JZelYOjEFoCOn7E6Vxu0fwV7ACPzArcno1rYuVxGB7JY6G7__e4_KZW4lTYIaHSLVaVLzklZBLZnQw047oq5-Q/s16000/volks.gif") no-repeat center center;
    width: 280px;
    height: 90px;
    background-size:100%;
    position: absolute;
    bottom: 10px;
    left: 30%;
    -webkit-animation: myfirst-footer-anim 22s linear infinite;
    animation: myfirst-footer-anim 22s linear infinite;
}

.site-footer .new_footer_top .footer_bg .footer_bg_two {
    background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhyLGwEUVwPK6Vi8xXMymsc-ZXVwLWyXhogZxbcXQYSY55REw_0D4VTQnsVzCrL7nsyjd0P7RVOI5NKJbQ75koZIalD8mqbMquP20fL3DxsWngKkOLOzoOf9sMuxlbyfkIBTsDw5WFUj-YJiI50yzgVjF8cZPHhEjkOP_PRTQXDHEq8AyWpBiJdN9SfQA/s16000/cyclist.gif") no-repeat center center;
    width: 70px;
    height: 80px;
    background-size:100%;
    bottom: 5px;
    left: 38%;
    position: absolute;
    -webkit-animation: myfirst-footer-anim 30s linear infinite;
    animation: myfirst-footer-anim 30s linear infinite;
}

@-moz-keyframes myfirst-footer-anim {0% {left: -25%;} 100% {left: 100%;}}
@-webkit-keyframes myfirst-footer-anim {0% {left: -25%;} 100% {left: 100%;}}
@keyframes myfirst-footer-anim {0% {left: -25%;} 100% {left: 100%;}}
/* СТИЛИ АНИМАЦИИ ИЗ СТАРОГО ФУТЕРА - КОНЕЦ */


/* Адаптивные стили */
@media (min-width: 992px) {
    .site-footer .pl_70 {
        padding-left: 40px;
    }

    .site-footer .new_footer_top {
        padding: 100px 0 140px;
    }
}

@media (max-width: 991px) {
    .site-footer .new_footer_top .f_widget {
        margin-bottom: 40px;
    }

    .site-footer .f-title {
        font-size: 20px;
    }

    .site-footer .f_social_icon {
        justify-content: flex-start;
    }
    .site-footer .pl_70 {
        padding-left: 15px;
    }
}

@media (max-width: 767px) {
    .site-footer .new_footer_top {
        padding: 60px 0 100px;
    }

    .site-footer .footer_bottom .row > div {
        text-align: center !important;
        margin-bottom: 15px;
    }

    .site-footer .footer_bottom .row > div:last-child {
        margin-bottom: 0;
    }

    .site-footer .footer_bottom .text-right {
        text-align: center !important;
    }

    .site-footer .f-title {
        font-size: 18px;
    }

    .site-footer .new_footer_top .footer_bg .footer_bg_one,
    .site-footer .new_footer_top .footer_bg .footer_bg_two {
        display: none;
    }
}

/* Сетка */
.site-footer .row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.site-footer .col-md-6,
.site-footer .col-lg-3,
.site-footer .col-lg-6 {
    position: relative;
    width: 100%;
    padding: 0 15px;
    margin-bottom: 30px;
}

@media (min-width: 768px) {
    .site-footer .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
        margin-bottom: 0;
    }
}

@media (min-width: 992px) {
    .site-footer .col-lg-3 {
        flex: 0 0 25%;
        max-width: 25%;
        margin-bottom: 0;
    }

    .site-footer .col-lg-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

.site-footer .align-items-center {
    align-items: center !important;
}

.site-footer .text-right {
    text-align: right !important;
}

.site-footer .mb-0 {
    margin-bottom: 0 !important;
}
</style>

<footer class="site-footer new_footer_area bg_color">
    <!-- <div class="coffee_steam"></div> ЭЛЕМЕНТ УДАЛЕН, ТАК КАК СТИЛИ ДЛЯ НЕГО УДАЛЕНЫ -->
    <div class="new_footer_top">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="f_widget company_widget">
                        <h3 class="f-title f_600 t_color f_size_18">Будьте на связи</h3>
                        <div class="widget-content-wrapper">
                            <p>Подпишитесь на нашу рассылку, чтобы первыми узнавать о новых продуктах, акциях и специальных предложениях.</p>
                            <form id="footer-subscribe-form" class="f_subscribe_two mailchimp" method="post" novalidate="true">
                                <input type="email" name="EMAIL" id="footer-subscribe-email" class="form-control memail" placeholder="Ваш Email" required>
                                <button class="btn btn_get btn_get_two" type="submit">Подписаться</button>
                                <p class="mchimp-errmessage" style="display: none;"></p>
                                <p class="mchimp-sucmessage" style="display: none;"></p>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="f_widget about-widget pl_70">
                        <h3 class="f-title f_600 t_color f_size_18">Навигация</h3>
                        <ul class="list-unstyled f_list">
                            <li><a href="<?php echo $baseUrl_footer; ?>index.php">Главная</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>Продукты/index.php">Продукты</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>Рецепты/index.php">Рецепты</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>Акции/index.php">Акции</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>О кофе/index.php">О кофе</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>Новости/index.php">Новости</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>Контакты/index.php">Контакты</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="f_widget about-widget pl_70">
                        <h3 class="f-title f_600 t_color f_size_18">Помощь</h3>
                        <ul class="list-unstyled f_list">
                            <li><a href="<?php echo $baseUrl_footer; ?>faq.php">FAQ</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>terms.php">Условия использования</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>support.php">Политика поддержки</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>privacy.php">Конфиденциальность</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="f_widget social-widget pl_70">
                         <h3 class="f-title f_600 t_color f_size_18">Мы в соцсетях</h3>
                        <div class="widget-content-wrapper">
                            <div class="f_social_icon">
                                <a href="https://t.me/coffeefan" target="_blank" class="fab fa-telegram" title="Telegram"></a>
                                <a href="https://vk.com/coffeefan" target="_blank" class="fab fa-vk" title="VK"></a>
                                <a href="https://youtube.com/coffeefan" target="_blank" class="fab fa-youtube" title="YouTube"></a>
                                <a href="https://instagram.com/coffeefan" target="_blank" class="fab fa-instagram" title="Instagram"></a>
                            </div>
                             <div class="coffee_time_gif_container">
                                <img src="<?php echo $baseUrl_footer; ?>img/footer_coffee_steam_light.gif" alt="Время кофе с котом" class="coffee_time_gif">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- HTML ДЛЯ АНИМАЦИИ ФОНА ИЗ СТАРОГО ФУТЕРА (машинка, велосипедист) -->
        <div class="footer_bg">
            <div class="footer_bg_one"></div>
            <div class="footer_bg_two"></div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 col-sm-7">
                    <p class="mb-0 f_400">© CoffeeFan <?php echo date("Y"); ?>. Все права защищены.</p>
                </div>
                <div class="col-lg-6 col-sm-5 text-right footer-credits">
                    <p>Сделано с <i class="icon_heart fas fa-heart"></i> разработчиком <a href="https://github.com/torgunakov2004" target="_blank">CoffeeFan</a></p>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Код для формы подписки ---
    const form = document.getElementById('footer-subscribe-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const emailInput = document.getElementById('footer-subscribe-email');
            const email = emailInput.value.trim();
            const errorMsg = form.querySelector('.mchimp-errmessage');
            const successMsg = form.querySelector('.mchimp-sucmessage');
            const submitButton = form.querySelector('button[type="submit"]');

            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';

            function isValidEmail(email) {
                const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                return emailRegex.test(email);
            }

            if (!email) {
                errorMsg.textContent = 'Пожалуйста, введите ваш Email.';
                errorMsg.style.display = 'block';
                emailInput.focus();
                return;
            }

            if (!isValidEmail(email)) {
                errorMsg.textContent = 'Пожалуйста, введите корректный Email адрес.';
                errorMsg.style.display = 'block';
                emailInput.focus();
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Отправка...';

            fetch('<?php echo $baseUrl_footer; ?>subscribe_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `EMAIL=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    successMsg.textContent = data.message || 'Спасибо за подписку!';
                    successMsg.style.display = 'block';
                    emailInput.value = '';

                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Спасибо за подписку!');
                    }
                } else {
                    errorMsg.textContent = data.message || 'Произошла ошибка. Попробуйте позже.';
                    errorMsg.style.display = 'block';

                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Ошибка подписки.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMsg.textContent = 'Ошибка сети. Пожалуйста, попробуйте позже.';
                errorMsg.style.display = 'block';

                if (typeof toastr !== 'undefined') {
                    toastr.error('Ошибка сети при попытке подписки.');
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Подписаться';
            });
        });
    }

    // --- Код для анимации "выпрыгивания" GIF ---
    const originalCatGif = document.querySelector('.site-footer .coffee_time_gif');
    const gifContainer = document.querySelector('.site-footer .coffee_time_gif_container');

    if (originalCatGif && gifContainer) {
        originalCatGif.addEventListener('click', function(event) {
            const numberOfJumpingGifs = 5;
            const gifSrc = originalCatGif.src;

            const originalRect = originalCatGif.getBoundingClientRect();
            const containerRect = gifContainer.getBoundingClientRect();

            const startX = (originalRect.left + originalRect.width / 2) - containerRect.left;
            const startY = (originalRect.top + originalRect.height / 2) - containerRect.top;

            for (let i = 0; i < numberOfJumpingGifs; i++) {
                const jumpingGif = document.createElement('img');
                jumpingGif.src = gifSrc;
                jumpingGif.alt = "Jumping coffee cat";
                jumpingGif.classList.add('jumping-cat-gif');

                jumpingGif.style.left = `${startX}px`;
                jumpingGif.style.top = `${startY}px`;

                const angle = Math.random() * Math.PI * 2;
                const distance = Math.random() * 80 + 50;
                const translateX = Math.cos(angle) * distance;
                const translateY = Math.sin(angle) * distance - 30;

                jumpingGif.style.setProperty('--tx', `${translateX}px`);
                jumpingGif.style.setProperty('--ty', `${translateY}px`);
                
                jumpingGif.style.animationDelay = `${i * 0.05}s`;

                gifContainer.appendChild(jumpingGif);

                jumpingGif.addEventListener('animationend', function() {
                    jumpingGif.remove();
                });
            }
        });
    }
});
</script>