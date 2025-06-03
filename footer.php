<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_script_path_footer = dirname($_SERVER['SCRIPT_NAME']);
$current_script_path_footer_normalized = str_replace('\\', '/', $current_script_path_footer);

$project_base_folder_name = 'CoffeeFan';

if ($current_script_path_footer_normalized === '/' || $current_script_path_footer_normalized === '/' . $project_base_folder_name) {
    $baseUrl_footer = '';
    $base_web_path_for_footer = ($current_script_path_footer_normalized === '/' . $project_base_folder_name) ? '/' . $project_base_folder_name : '';
} else {
    $path_segments_footer_temp = explode('/', trim($current_script_path_footer_normalized, '/'));
    if (isset($path_segments_footer_temp[0]) && strtolower($path_segments_footer_temp[0]) === strtolower($project_base_folder_name)) {
        $base_web_path_for_footer = '/' . $project_base_folder_name;
        array_shift($path_segments_footer_temp); 
        $baseUrl_footer = str_repeat('../', count($path_segments_footer_temp));
    } else {
        $path_segments_footer = array_filter(explode('/', trim($current_script_path_footer_normalized, '/')));
        $depth_footer = count($path_segments_footer);
        $baseUrl_footer = str_repeat('../', $depth_footer);
        $base_web_path_for_footer = ''; 
    }
}
if (empty($baseUrl_footer) && $current_script_path_footer_normalized !== '/' && $current_script_path_footer_normalized !== '/' . $project_base_folder_name) {
    $baseUrl_footer = './'; 
} elseif ($current_script_path_footer_normalized === '/' || ($base_web_path_for_footer !== '' && $current_script_path_footer_normalized === $base_web_path_for_footer )) {
     $baseUrl_footer = '';
}


if (!isset($current_site_url)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $main_page_url = rtrim($protocol . $domainName . $base_web_path_for_footer, '/') . '/';
    $current_site_url = $main_page_url;
}

?>
<style>
.site-footer.new_footer_area { background: linear-gradient(180deg, #0f0c08 0%, #14110E 100%); color: #adb5bd; font-family: 'Urbanist', Arial, Helvetica, sans-serif; position: relative; overflow: hidden; border-top: 1px solid rgba(201, 158, 113, 0.1); }
.site-footer .new_footer_top { padding: 80px 0 120px; position: relative; z-index: 2; overflow-x: hidden; }
.site-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 1; }
.site-footer .footer_bottom { padding: 30px 0; background: rgba(20, 17, 14, 0.9); border-top: 1px solid rgba(201, 158, 113, 0.15); position: relative; z-index: 2; }
.site-footer .footer_bottom p { font-size: 14px; font-weight: 400; line-height: 24px; color: #868e96; margin-bottom: 0; }
.site-footer .f_widget { display: flex; flex-direction: column; height: 100%; position: relative; }
.site-footer .f_widget .widget-content-wrapper { flex-grow: 1; display: flex; flex-direction: column; }
.site-footer .company_widget p { font-size: 15px; font-weight: 400; line-height: 1.7; color: #adb5bd; margin-bottom: 25px; opacity: 0.8; }
.site-footer .f_subscribe_two { margin-top: auto; position: relative; }
.site-footer .f_subscribe_two .form-control.memail { background: rgba(28, 24, 20, 0.8); border: 1px solid rgba(68, 68, 68, 0.5); border-radius: 6px; height: 50px; line-height: 50px; padding: 0 20px; color: #fff; margin-bottom: 15px; font-family: 'Urbanist', Arial, Helvetica, sans-serif; font-size: 15px; transition: all 0.3s ease; backdrop-filter: blur(5px); }
.site-footer .f_subscribe_two .form-control.memail:focus { border-color: #C99E71; box-shadow: 0 0 0 2px rgba(201, 158, 113, 0.2); }
.site-footer .f_subscribe_two .form-control.memail::placeholder { color: #777; opacity: 0.7; }
.site-footer .f_subscribe_two .btn_get { border-width: 1px; margin-top: 0; background: linear-gradient(135deg, #C99E71 0%, #bd864b 100%); color: #14110E; border: none; font-weight: 600; padding: 14px 25px; border-radius: 6px; transition: all 0.3s ease; font-family: 'Inter', Arial, Helvetica, sans-serif; font-size: 15px; cursor: pointer; width: 100%; letter-spacing: 0.5px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(201, 158, 113, 0.2); }
.site-footer .f_subscribe_two .btn_get:hover { background: linear-gradient(135deg, #bd864b 0%, #a87a42 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(201, 158, 113, 0.3); }
.site-footer .mchimp-errmessage, .site-footer .mchimp-sucmessage { font-size: 14px; margin-top: 10px; padding: 8px 12px; border-radius: 4px; display: none; }
.site-footer .mchimp-errmessage { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border-left: 3px solid #e74c3c; }
.site-footer .mchimp-sucmessage { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border-left: 3px solid #2ecc71; }
.site-footer a { color: #adb5bd; text-decoration: none; transition: all 0.3s ease; position: relative; }
.site-footer a:hover, .site-footer a:focus { color: #C99E71; text-decoration: none; outline: none; }
.site-footer .new_footer_top .f_widget.about-widget .f_list { list-style: none; padding-left: 0; margin-bottom: 0; }
.site-footer .new_footer_top .f_widget.about-widget .f_list li { margin-bottom: 12px; position: relative; padding-left: 15px; }
.site-footer .new_footer_top .f_widget.about-widget .f_list li:before { content: "•"; color: #C99E71; position: absolute; left: 0; top: 0; font-size: 18px; line-height: 1; }
.site-footer .new_footer_top .f_widget.about-widget .f_list li a { color: #adb5bd; font-size: 15px; display: inline-block; transition: all 0.3s ease; padding: 2px 0; }
.site-footer .new_footer_top .f_widget.about-widget .f_list li a:hover { color: #C99E71; transform: translateX(5px); }
.site-footer .f_widget.social-widget .widget-content-wrapper { justify-content: space-between; }
.site-footer .f_social_icon { margin-bottom: 25px; display: flex; gap: 12px; }
.site-footer .f_social_icon a { width: 44px; height: 44px; line-height: 44px; background: rgba(36, 33, 31, 0.8); border: 1px solid rgba(51, 51, 51, 0.5); font-size: 18px; border-radius: 50%; color: #adb5bd; display: inline-flex; align-items: center; justify-content: center; transition: all 0.3s ease; backdrop-filter: blur(5px); }
.site-footer .f_social_icon a:hover { background: #C99E71; border-color: #C99E71; color: #14110E; transform: translateY(-3px) scale(1.1); box-shadow: 0 8px 16px rgba(201, 158, 113, 0.3); }
.site-footer .f-title { margin-bottom: 25px; padding-bottom: 15px; color: #FFFFFF; font-family: 'Righteous', cursive; font-weight: 600; font-size: 22px; position: relative; }
.site-footer .f-title:after { content: ''; position: absolute; left: 0; bottom: 0; width: 50px; height: 2px; background: linear-gradient(90deg, #C99E71 0%, rgba(201, 158, 113, 0) 100%); }
.site-footer .pl_70 { padding-left: 0; }
.site-footer .coffee_time_gif_container { margin-top: 20px; text-align: center; padding-top: 20px; display: flex; justify-content: center; align-items: flex-end; flex-grow: 1; position: relative; cursor: pointer; }
.site-footer .coffee_time_gif { max-width: 120px; height: auto; border-radius: 10px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); transition: transform 0.5s ease; position: relative; z-index: 1; }
.site-footer .coffee_time_gif:hover { transform: scale(1.05) rotate(-2deg); }
.jumping-cat-gif { position: absolute; width: 80px; height: auto; border-radius: 8px; z-index: 10; pointer-events: none; animation: jumpOutEffect 0.8s ease-out forwards; opacity: 0; }
@keyframes jumpOutEffect { 0% { transform: scale(0.1) translate(-50%, -50%); opacity: 0; } 50% { opacity: 1; } 100% { transform: scale(1) translate(var(--tx, 0), var(--ty, 0)); opacity: 0; } }
.site-footer .footer_bottom .icon_heart { color: #e74c3c; font-style: normal; animation: heartbeat 1.5s infinite; display: inline-block; }
@keyframes heartbeat { 0% { transform: scale(1); } 25% { transform: scale(1.1); } 50% { transform: scale(1); } 75% { transform: scale(1.1); } 100% { transform: scale(1); } }
.site-footer .footer_bottom .footer-credits a { color: #C99E71; font-weight: 500; position: relative; }
.site-footer .footer_bottom .footer-credits a:after { content: ''; position: absolute; left: 0; bottom: -2px; width: 0; height: 1px; background: #C99E71; transition: width 0.3s ease; }
.site-footer .footer_bottom .footer-credits a:hover { color: #FFFFFF; }
.site-footer .footer_bottom .footer-credits a:hover:after { width: 100%; }
.site-footer .new_footer_top .footer_bg { position: absolute; bottom: 0; left: 0; background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEigB8iI5tb8WSVBuVUGc9UjjB8O0708X7Fdic_4O1LT4CmLHoiwhanLXiRhe82yw0R7LgACQ2IhZaTY0hhmGi0gYp_Ynb49CVzfmXtYHUVKgXXpWvJ_oYT8cB4vzsnJLe3iCwuzj-w6PeYq_JaHmy_CoGoa6nw0FBo-2xLdOPvsLTh_fmYH2xhkaZ-OGQ/s16000/footer_bg.png") no-repeat scroll center 0; width: 100%; height: 266px; opacity: 0.15; z-index: 0; pointer-events: none; }
.site-footer .new_footer_top .footer_bg .footer_bg_one { background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEia0PYPxwT5ifToyP3SNZeQWfJEWrUENYA5IXM6sN5vLwAKvaJS1pQVu8mOFFUa_ET4JuHNTFAxKURFerJYHDUWXLXl1vDofYXuij45JZelYOjEFoCOn7E6Vxu0fwV7ACPzArcno1rYuVxGB7JY6G7__e4_KZW4lTYIaHSLVaVLzklZBLZnQw047oq5-Q/s16000/volks.gif") no-repeat center center; width: 280px; height: 90px; background-size:100%; position: absolute; bottom: 10px; left: 30%; animation: myfirst-footer-anim 22s linear infinite; }
.site-footer .new_footer_top .footer_bg .footer_bg_two { background: url("https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhyLGwEUVwPK6Vi8xXMymsc-ZXVwLWyXhogZxbcXQYSY55REw_0D4VTQnsVzCrL7nsyjd0P7RVOI5NKJbQ75koZIalD8mqbMquP20fL3DxsWngKkOLOzoOf9sMuxlbyfkIBTsDw5WFUj-YJiI50yzgVjF8cZPHhEjkOP_PRTQXDHEq8AyWpBiJdN9SfQA/s16000/cyclist.gif") no-repeat center center; width: 70px; height: 80px; background-size:100%; bottom: 5px; left: 38%; position: absolute; animation: myfirst-footer-anim 30s linear infinite; }
@keyframes myfirst-footer-anim {0% {left: -25%;} 100% {left: 100%;}}
.share-site-button { position: fixed; bottom: 20px; right: 20px; background-color: #C99E71; color: #14110E; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); z-index: 998; transition: transform 0.2s ease-in-out, background-color 0.2s ease; }
.share-site-button:hover { transform: scale(1.1); background-color: #bd864b; }
.share-site-button .material-icons-outlined { font-size: 28px; }
.share-qr-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.6); align-items: center; justify-content: center; }
.share-qr-modal-content {
    background-color: rgba(28, 24, 20, 0.5); /* Ваш темный фон, полупрозрачный (например, 90%) */
    color: #FFFFFF;
    margin: auto;
    padding: 20px 25px; 
    border: 1px solid rgba(68, 68, 68, 0.6); /* Рамка тоже может быть с альфа */
    border-radius: 10px;
    width: 90%; 
    max-width: 380px; 
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
    text-align: center;
    position: relative; 
    animation: fadeInModalShare 0.3s ease-out;
    box-sizing: border-box; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    
    /* Эффект матового стекла */
    backdrop-filter: blur(10px) saturate(120%);
    -webkit-backdrop-filter: blur(10px) saturate(120%); 
}
@keyframes fadeInModalShare { from { opacity: 0; transform: scale(0.9) translateY(-20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.share-qr-modal-close { color: #aaa; position: absolute; top: 10px; right: 15px; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.2s ease; }
.share-qr-modal-close:hover, .share-qr-modal-close:focus { color: #C99E71; text-decoration: none; }
.share-qr-modal-content h2 { font-family: 'Righteous', cursive; color: #C99E71; margin-top: 0; margin-bottom: 10px; font-size: 1.4em; line-height: 1.3; }
.share-qr-modal-content p { font-size: 0.85em; line-height: 1.5; margin-bottom: 10px; opacity: 0.9; max-width: 95%; }
.qr-code-container { margin: 10px auto; padding: 8px; background-color: #fff; display: inline-block; border-radius: 6px; }
.qr-code-container img { display: block; width: 120px; height: 120px; }
.btn-primary-share { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background-color: #C99E71; color: #14110E; padding: 8px 15px; border: none; border-radius: 6px; font-size: 0.9em; font-weight: 600; cursor: pointer; transition: background-color 0.3s ease, transform 0.2s ease; margin-top: 15px; width: auto; max-width: 90%; box-sizing: border-box;}
.btn-primary-share:hover { background-color: #bd864b; transform: translateY(-1px); }
.btn-primary-share .material-icons-outlined { font-size: 1.1em;}
@media (min-width: 992px) { .site-footer .pl_70 { padding-left: 40px; } .site-footer .new_footer_top { padding: 100px 0 140px; } }
@media (max-width: 991px) { .site-footer .new_footer_top .f_widget { margin-bottom: 40px; } .site-footer .f-title { font-size: 20px; } .site-footer .f_social_icon { justify-content: flex-start; } .site-footer .pl_70 { padding-left: 15px; } }
@media (max-width: 767px) { .site-footer .new_footer_top { padding: 60px 0 100px; } .site-footer .footer_bottom .row > div { text-align: center !important; margin-bottom: 15px; } .site-footer .footer_bottom .row > div:last-child { margin-bottom: 0; } .site-footer .footer_bottom .text-right { text-align: center !important; } .site-footer .f-title { font-size: 18px; } .site-footer .new_footer_top .footer_bg .footer_bg_one, .site-footer .new_footer_top .footer_bg .footer_bg_two { display: none; } .share-site-button { width: 50px; height: 50px; bottom: 15px; right: 15px; } .share-site-button .material-icons-outlined { font-size: 24px; } .share-qr-modal-content { padding: 20px 15px; max-width: 90%; } .share-qr-modal-content h2 { font-size: 1.4em; margin-bottom: 10px; } .share-qr-modal-content p { font-size: 0.85em; margin-bottom: 10px; } .qr-code-container { margin: 10px auto; } .qr-code-container img { width: 130px; height: 130px; } .btn-primary-share { font-size: 0.9em; padding: 10px 15px; min-width: auto; } }
.site-footer .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
.site-footer .col-md-6, .site-footer .col-lg-3, .site-footer .col-lg-6 { position: relative; width: 100%; padding: 0 15px; margin-bottom: 30px; }
@media (min-width: 768px) { .site-footer .col-md-6 { flex: 0 0 50%; max-width: 50%; margin-bottom: 0; } }
@media (min-width: 992px) { .site-footer .col-lg-3 { flex: 0 0 25%; max-width: 25%; margin-bottom: 0; } .site-footer .col-lg-6 { flex: 0 0 50%; max-width: 50%; } }
.site-footer .align-items-center { align-items: center !important; }
.site-footer .text-right { text-align: right !important; }
.site-footer .mb-0 { margin-bottom: 0 !important; }
@media (max-width: 480px) {
    .share-qr-modal-content { padding: 15px 10px; max-width: 95%;}
    .share-qr-modal-content h2 { font-size: 1.3em; }
    .share-qr-modal-content p { font-size: 0.8em; }
    .qr-code-container img { width: 110px; height: 110px; }
    .btn-primary-share { font-size: 0.85em; padding: 8px 12px; gap: 4px; }
    .btn-primary-share .material-icons-outlined { font-size: 1em; }
    .share-site-button { width: 45px; height: 45px; bottom: 10px; right: 10px; }
    .share-site-button .material-icons-outlined { font-size: 20px; }
}
@media (max-width: 360px) {
    .qr-code-container img { width: 100px; height: 100px; }
}
</style>

<footer class="site-footer new_footer_area bg_color">
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
                            <li><a href="<?php echo $baseUrl_footer; ?>list_footer/faq.php">FAQ</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>list_footer/terms.php">Условия использования</a></li>
                            <li><a href="<?php echo $baseUrl_footer; ?>list_footer/support_policy.php">Политика поддержки</a></li> 
                            <li><a href="<?php echo $baseUrl_footer; ?>list_footer/privacy.php">Конфиденциальность</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="f_widget social-widget pl_70">
                         <h3 class="f-title f_600 t_color f_size_18">Мы в соцсетях</h3>
                        <div class="widget-content-wrapper">
                            <div class="f_social_icon">
                                <a href="#" target="_blank" class="fab fa-telegram" title="Telegram"></a>
                                <a href="#" target="_blank" class="fab fa-vk" title="VK"></a>
                                <a href="#" target="_blank" class="fab fa-youtube" title="YouTube"></a>
                                <a href="#" target="_blank" class="fab fa-instagram" title="Instagram"></a>
                            </div>
                             <div class="coffee_time_gif_container">
                                <img src="<?php echo $baseUrl_footer; ?>img/footer_coffee_steam_light.gif" alt="Время кофе с котом" class="coffee_time_gif">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

<div id="shareSiteButton" class="share-site-button">
    <span class="material-icons-outlined">share</span>
</div>
<div id="shareQrModal" class="share-qr-modal">
    <div class="share-qr-modal-content">
        <span class="share-qr-modal-close" id="closeShareModal">×</span>
        <h2>Поделитесь CoffeeFan!</h2>
        <p>Расскажите о нашем сайте друзьям, мы будем вам очень благодарны!</p>
        <div class="qr-code-container">
            <img src="<?php echo rtrim($base_web_path_for_footer, '/'); ?>/img/qr-code.gif" alt="QR-код CoffeeFan" id="qrCodeImage">
        </div>
        <button id="nativeShareButton" class="btn-primary-share">
            <span class="material-icons-outlined">share</span>Поделиться
        </button>
        <button id="copyUrlButtonFallback" class="btn-primary-share" style="display: none; margin-top: 10px;">
             <span class="material-icons-outlined">content_copy</span>Копировать ссылку
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
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

    const originalCatGif = document.querySelector('.site-footer .coffee_time_gif');
    const gifContainer = document.querySelector('.site-footer .coffee_time_gif_container');
    let catGifClickCount = 0; 
    const clicksForGame = 10; 
     if (originalCatGif && gifContainer) {
        originalCatGif.addEventListener('click', function(event) {
            catGifClickCount++;
             console.log('Cat GIF clicks: ' + catGifClickCount); 
            if (catGifClickCount >= clicksForGame) {
                window.location.href = '<?php echo rtrim($base_web_path_for_footer, '/'); ?>/games_hub.php'; // Эта ссылка остается на games_hub.php
                catGifClickCount = 0;
                return; 
            }
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

    const shareSiteButton = document.getElementById('shareSiteButton');
    const shareQrModal = document.getElementById('shareQrModal');
    const closeShareModal = document.getElementById('closeShareModal');
    const nativeShareButton = document.getElementById('nativeShareButton');
    const copyUrlButtonFallback = document.getElementById('copyUrlButtonFallback');
    const siteUrl = "<?php echo htmlspecialchars($current_site_url, ENT_QUOTES, 'UTF-8'); ?>";
    const siteTitle = document.title; 

    if (shareSiteButton && shareQrModal && closeShareModal && nativeShareButton && copyUrlButtonFallback) {
        shareSiteButton.addEventListener('click', function () {
            shareQrModal.style.display = 'flex';
            if (navigator.share) {
                nativeShareButton.style.display = 'inline-flex';
                copyUrlButtonFallback.style.display = 'none';
            } else {
                nativeShareButton.style.display = 'none';
                copyUrlButtonFallback.style.display = 'inline-flex';
            }
        });
        closeShareModal.addEventListener('click', function () {
            shareQrModal.style.display = 'none';
        });
        window.addEventListener('click', function (event) {
            if (event.target == shareQrModal) {
                shareQrModal.style.display = 'none';
            }
        });
    }

    if (nativeShareButton) {
        nativeShareButton.addEventListener('click', async function() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: siteTitle,
                        text: 'Зацени этот классный кофейный сайт CoffeeFan!',
                        url: siteUrl
                    });
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Спасибо, что поделились!');
                    }
                    shareQrModal.style.display = 'none'; 
                } catch (error) {
                    console.error('Ошибка при попытке поделиться:', error);
                    if (error.name !== 'AbortError') { 
                        if (typeof toastr !== 'undefined') {
                            toastr.info('Не удалось поделиться. Попробуйте скопировать ссылку.');
                        }
                        nativeShareButton.style.display = 'none';
                        copyUrlButtonFallback.style.display = 'inline-flex';
                    } else {
                        console.log('Пользователь отменил шаринг.');
                    }
                }
            } else {
                nativeShareButton.style.display = 'none';
                copyUrlButtonFallback.style.display = 'inline-flex';
                if (typeof toastr !== 'undefined') {
                    toastr.info('Ваш браузер не поддерживает функцию "Поделиться". Пожалуйста, скопируйте ссылку.');
                }
            }
        });
    }

    if (copyUrlButtonFallback) {
        copyUrlButtonFallback.addEventListener('click', function() {
            copyTextToClipboard(siteUrl);
        });
    }

    function copyTextToClipboard(textToCopy) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                if (typeof toastr !== 'undefined') { 
                    toastr.success('Ссылка скопирована в буфер обмена!'); 
                } else {
                    alert('Ссылка скопирована!');
                }
            }).catch(function(err) {
                console.error('Ошибка автоматического копирования: ', err);
                fallbackCopyTextToClipboard(textToCopy);
            });
        } else {
            fallbackCopyTextToClipboard(textToCopy);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";  
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            const successful = document.execCommand('copy');
            const msg = successful ? 'Ссылка скопирована!' : 'Не удалось скопировать.';
            if (typeof toastr !== 'undefined') {
                if(successful) toastr.success(msg); else toastr.error(msg + ' Пожалуйста, скопируйте вручную.');
            } else {
                alert(msg);
            }
            if (!successful) {
                 window.prompt("Скопируйте вручную: Ctrl+C, Enter", text);
            }
        } catch (err) {
            console.error('Fallback copy error:', err);
            window.prompt("Ошибка копирования. Скопируйте вручную: Ctrl+C, Enter", text);
            if (typeof toastr !== 'undefined') {
                toastr.error('Ошибка копирования. Пожалуйста, скопируйте вручную.');
            }
        }
        document.body.removeChild(textArea);
    }

    <?php if (!isset($_SESSION['user_timezone_js'])): ?>
    try {
        const userTimeZoneFromJS = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (userTimeZoneFromJS) {
            console.log('Detected client timezone:', userTimeZoneFromJS);
            $.ajax({
                type: 'POST',
                url: '<?php echo rtrim($base_web_path_for_footer, '/'); ?>/set_user_timezone.php',
                data: { timezone: userTimeZoneFromJS },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 'success') {
                        console.log('User timezone set on server:', response.timezone_set);
                    } else if (response && response.message) {
                        console.warn('Failed to set user timezone on server:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error sending user timezone to server:', status, error);
                }
            });
        }
    } catch (e) {
        console.warn('Could not detect client timezone:', e);
    }
    <?php endif; ?>
});
</script>
