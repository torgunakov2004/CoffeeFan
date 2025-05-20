<?php
session_start();
require_once '../config/connect.php';
// Подключаем наш фильтр и список слов
require_once __DIR__ . '/profanity_filter.php'; // __DIR__ для пути от текущего файла
$profanityWords = require __DIR__ . '/profanity_filter_list.php';


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt_news = $connect->prepare("SELECT n.*, u.first_name, u.last_name FROM `news` n LEFT JOIN `user` u ON n.author_id = u.id WHERE n.`id` = ?");
    if ($stmt_news === false) {
        die("Ошибка подготовки запроса к БД (новости): " . htmlspecialchars($connect->error));
    }
    $stmt_news->bind_param("i", $id);
    $stmt_news->execute();
    $news_item_result = $stmt_news->get_result();
    $news_item = $news_item_result->fetch_assoc();
    $stmt_news->close();

    if (!$news_item) {
        die("Новость не найдена.");
    }

    $current_news_id_for_nav = intval($news_item['id']);
    $current_date_for_nav = $news_item['date'];

    $stmt_prev = $connect->prepare("SELECT id, title FROM news WHERE (date > ?) OR (date = ? AND id > ?) ORDER BY date ASC, id ASC LIMIT 1");
    if ($stmt_prev) {
        $stmt_prev->bind_param("ssi", $current_date_for_nav, $current_date_for_nav, $current_news_id_for_nav);
        $stmt_prev->execute();
        $prev_news_result = $stmt_prev->get_result();
        $prev_news = $prev_news_result->fetch_assoc();
        $stmt_prev->close();
    } else {
        $prev_news = null;
    }

    $stmt_next = $connect->prepare("SELECT id, title FROM news WHERE (date < ?) OR (date = ? AND id < ?) ORDER BY date DESC, id DESC LIMIT 1");
    if ($stmt_next) {
        $stmt_next->bind_param("ssi", $current_date_for_nav, $current_date_for_nav, $current_news_id_for_nav);
        $stmt_next->execute();
        $next_news_result = $stmt_next->get_result();
        $next_news = $next_news_result->fetch_assoc();
        $stmt_next->close();
    } else {
        $next_news = null;
    }

    $stmt_images = $connect->prepare("SELECT `image_path` FROM `news_images` WHERE `news_id` = ? ORDER BY `id` ASC");
    if ($stmt_images) {
        $stmt_images->bind_param("i", $id);
        $stmt_images->execute();
        $slider_images_result = $stmt_images->get_result();
        $slider_images = [];
        while ($img_row = $slider_images_result->fetch_assoc()) {
            $slider_images[] = $img_row['image_path'];
        }
        $stmt_images->close();
    } else {
        $slider_images = [];
    }

    if (empty($slider_images) && !empty($news_item['image'])) {
        $slider_images[] = $news_item['image'];
    }

    $cart_quantities = [];
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
        $query_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
        $stmt_cart = $connect->prepare($query_cart);
        if($stmt_cart) {
            $stmt_cart->bind_param("i", $user_id);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            while ($row_cart = $result_cart->fetch_assoc()) {
                $cart_quantities[$row_cart['product_id']] = $row_cart['quantity'];
            }
            $stmt_cart->close();
        }
    }
    $has_items_in_cart = !empty($cart_quantities);

    $stmt_comments = $connect->prepare("SELECT nc.*, u.avatar as user_avatar FROM `news_comments` nc LEFT JOIN `user` u ON nc.user_id = u.id WHERE nc.`news_id` = ? AND nc.`is_approved` = 1 ORDER BY nc.`created_at` DESC");
    if ($stmt_comments) {
        $stmt_comments->bind_param("i", $id);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();
        $comments = [];
        while ($comment_row = $comments_result->fetch_assoc()) {
            $comments[] = $comment_row;
        }
        $stmt_comments->close();
    } else {
        $comments = [];
    }


    // Блок обработки комментария
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
        // Сначала получаем "сырой" текст комментария
        $comment_text_raw = trim($_POST['comment_text']); // Используем $_POST напрямую, filter_input здесь не нужен для "сырого" текста
        
        $author_name = "Гость";
        $user_id_comment = NULL;

        if (isset($_SESSION['user']) && isset($_SESSION['user']['name']) && isset($_SESSION['user']['id'])) {
            // Имя авторизованного пользователя уже должно быть безопасным из сессии
            $author_name = $_SESSION['user']['name']; // или $_SESSION['user']['first_name']
            $user_id_comment = $_SESSION['user']['id'];
        } elseif (!empty($_POST['author_name'])) {
            // Имя гостя нужно экранировать перед вставкой в БД, но для проверки используем "сырое"
            $author_name_raw = trim($_POST['author_name']);
            if (empty($author_name_raw)){
                 $comment_error = "Пожалуйста, укажите ваше имя.";
            } else {
                 $author_name = htmlspecialchars($author_name_raw, ENT_QUOTES, 'UTF-8');
            }
        } else {
             $comment_error = "Пожалуйста, укажите ваше имя.";
        }


        if (empty($comment_error) && !empty($comment_text_raw) && !empty($author_name)) {
            // Проверяем "сырой" текст на наличие запрещенных слов
            if (containsProfanity($comment_text_raw, $profanityWords)) {
                $comment_error = "Ваш комментарий содержит недопустимые слова и не может быть опубликован.";
            } else {
                // Если все в порядке, экранируем текст комментария для безопасности перед вставкой в БД
                $comment_text_for_db = htmlspecialchars($comment_text_raw, ENT_QUOTES, 'UTF-8');

                // is_approved = 0, так как комментарий должен пройти модерацию в админке
                // Даже если нет мата, модерация нужна для общего контроля контента
                $is_approved_default = 1;

                $stmt_add_comment = $connect->prepare("INSERT INTO `news_comments` (`news_id`, `user_id`, `author_name`, `comment_text`, `is_approved`) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_add_comment) {
                    $stmt_add_comment->bind_param("iissi", $id, $user_id_comment, $author_name, $comment_text_for_db, $is_approved_default);
                    if ($stmt_add_comment->execute()) {
                        // Сообщение для пользователя, что комментарий отправлен на модерацию
                        if (empty($comment_error)) { // Если ошибок не было (включая проверку на мат)
                            $_SESSION['user_message_news_comment'] = "Ваш комментарий успешно опубликован!";
                        }
                        header("Location: news_detail.php?id=" . $id . "#comments-form-anchor"); // Якорь к форме, чтобы пользователь видел сообщение
                        exit();
                    } else {
                        $comment_error = "Ошибка добавления комментария: " . $stmt_add_comment->error;
                    }
                    $stmt_add_comment->close();
                } else {
                     $comment_error = "Ошибка подготовки запроса для добавления комментария.";
                }
            }
        } elseif (empty($comment_error)) { // Если $comment_error еще не установлен другими проверками
            $comment_error = "Пожалуйста, заполните текст комментария.";
        }
    }

} else {
    die("ID новости не указан.");
}

$page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$page_title_encoded = urlencode($news_item['title']);

$back_url = 'index.php'; 
if (isset($_GET['from']) && $_GET['from'] === 'main') {
    $back_url = '../index.php#latest-news-section'; 
} 
// Убрал elseif (isset($_SERVER['HTTP_REFERER'])), т.к. $back_url уже имеет значение по умолчанию

// Проверяем, есть ли сообщение для пользователя в сессии (например, после отправки комментария)
$user_feedback_message = '';
if (isset($_SESSION['user_message_news_comment'])) {
    $user_feedback_message = $_SESSION['user_message_news_comment'];
    unset($_SESSION['user_message_news_comment']);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($news_item['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="style.css?v=<?php echo time(); // Для сброса кэша CSS ?>">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <?php
        $current_page_is_faq = false; // Пример, если эта страница не FAQ
        include_once '../header_footer_elements/header.php'; 
    ?>
    <main>
        <div class="container">
            <!-- ... (код для заголовка новости, слайдера, контента, кнопок поделиться и навигации - остается без изменений) ... -->
            <h1 class="section-subtitle news-detail__main-title-override"><?php echo htmlspecialchars($news_item['title']); ?></h1>
            <article class="news-card_detail">
            <div class="page-standalone-back-button-wrapper">
                    <a href="<?php echo htmlspecialchars($back_url); ?>" class="page-header__back-button-textual" title="Вернуться назад">
                        <span class="material-icons-outlined">arrow_back_ios_new</span> Вернуться к новстям
                    </a>
                </div>
                <?php if (!empty($slider_images)): ?>
                <div class="news-slider-container">
                    <div class="swiper-container news-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($slider_images as $image_path): ?>
                                <div class="swiper-slide"> <img src="../<?php echo htmlspecialchars(ltrim($image_path, '/')); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>"> </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
                <?php elseif (!empty($news_item['image'])): ?>
                    <img src="../<?php echo htmlspecialchars($news_item['image']); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-image">
                <?php endif; ?>

                <time class="news-date">
                     Опубликовано: <?php echo display_date_in_user_timezone($news_item['date']); ?>
                </time>

                <div class="news-content">
                    <?php
                    $content_raw = $news_item['content'];
                    $content_html = $content_raw; 
                    $content_html = preg_replace('/\[h2\](.*?)\[\/h2\]/s', '<h2>$1</h2>', $content_html);
                    $content_html = preg_replace('/\[h3\](.*?)\[\/h3\]/s', '<h3>$1</h3>', $content_html);
                    $content_html = preg_replace('/\[b\](.*?)\[\/b\]/s', '<strong>$1</strong>', $content_html);
                    $content_html = preg_replace('/\[i\](.*?)\[\/i\]/s', '<em>$1</em>', $content_html);
                    $content_html = preg_replace_callback('/\[quote\](.*?)\[\/quote\]/s', function($matches) {
                        return '<blockquote><p>' . $matches[1] . '</p></blockquote>';
                    }, $content_html);
                    $content_html = preg_replace_callback('/\[ul\](.*?)\[\/ul\]/s', function($matches) {
                        $items_raw = $matches[1];
                        $items_html = preg_replace('/\[li\](.*?)\[\/li\]/s', '<li>$1</li>', $items_raw);
                        return '<ul>' . $items_html . '</ul>';
                    }, $content_html);
                    $content_html = preg_replace_callback('/\[ol\](.*?)\[\/ol\]/s', function($matches) {
                        $items_raw = $matches[1];
                        $items_html = preg_replace('/\[li\](.*?)\[\/li\]/s', '<li>$1</li>', $items_raw);
                        return '<ol>' . $items_html . '</ol>';
                    }, $content_html);
                    $content_html = nl2br($content_html);
                    echo $content_html;
                    ?>
                    <?php if (!empty($news_item['video_url'])): ?>
                        <div class="video-container"> <iframe src="<?php echo htmlspecialchars($news_item['video_url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> </div>
                    <?php endif; ?>
                </div>

                <div class="share-buttons">
                    <p>Поделиться новостью:</p>
                    <a href="https://vk.com/share.php?url=<?php echo urlencode($page_url); ?>&title=<?php echo $page_title_encoded; ?>" target="_blank" class="share-button vk" title="Поделиться ВКонтакте"><i class="fab fa-vk"></i></a>
                    <a href="https://t.me/share/url?url=<?php echo urlencode($page_url); ?>&text=<?php echo $page_title_encoded; ?>" target="_blank" class="share-button telegram" title="Поделиться в Telegram"><i class="fab fa-telegram-plane"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo $page_title_encoded . ' ' . urlencode($page_url); ?>" target="_blank" class="share-button whatsapp" title="Поделиться в WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <button type="button" class="share-button copy-link" title="Скопировать ссылку" data-link="<?php echo htmlspecialchars($page_url); ?>"><i class="fas fa-link"></i></button>
                </div>
                
                <div class="navigation-buttons">
                    <?php if ($prev_news): ?>
                        <a href="news_detail.php?id=<?php echo $prev_news['id']; ?>" class="btn-primary"> <span class="material-icons-outlined">arrow_back</span> Предыдущая </a>
                    <?php else: ?>
                        <span class="btn-primary disabled-nav-button"> <span class="material-icons-outlined">arrow_back</span> Предыдущая </span>
                    <?php endif; ?>
                    <?php if ($next_news): ?>
                        <a href="news_detail.php?id=<?php echo $next_news['id']; ?>" class="btn-primary"> Следующая <span class="material-icons-outlined">arrow_forward</span> </a>
                    <?php else: ?>
                         <span class="btn-primary disabled-nav-button"> Следующая <span class="material-icons-outlined">arrow_forward</span> </span>
                    <?php endif; ?>
                </div>

                <section id="comments-section">
                    <h2 class="comments-title">Комментарии (<?php echo count($comments); ?>)</h2>
                     <div id="comments-form-anchor" class="comment-form"> 
                        <?php if (!empty($user_feedback_message)): ?>
                            <p class="comment-feedback success"><?php echo htmlspecialchars($user_feedback_message); ?></p>
                        <?php endif; ?>
                        <?php if (isset($comment_error)): ?> 
                            <p class="comment-feedback error"><?php echo htmlspecialchars($comment_error); ?></p> 
                        <?php endif; ?>

                        <form action="news_detail.php?id=<?php echo $id; ?>#comments-form-anchor" method="POST">
                            <?php if (!isset($_SESSION['user'])): ?>
                            <div class="form-group"> <label for="author_name">Ваше имя:</label> <input type="text" name="author_name" id="author_name" required value="<?php echo isset($_POST['author_name']) ? htmlspecialchars($_POST['author_name']) : ''; ?>"> </div>
                            <?php endif; ?>
                            <div class="form-group"> <label for="comment_text">Ваш комментарий:</label> <textarea name="comment_text" id="comment_text" required><?php echo isset($_POST['comment_text']) ? htmlspecialchars($_POST['comment_text']) : ''; ?></textarea> </div>
                            <button type="submit" name="submit_comment" class="btn-primary">Отправить</button>
                        </form>
                    </div>
                    <?php if (!empty($comments)): ?>
                        <ul class="comment-list">
                            <?php foreach ($comments as $comment): ?>
                                <li class="comment-item">
                                    <div class="comment-avatar">
                                        <?php
                                        $display_comment_initials = true;
                                        $avatar_comment_src = '';
                                        $author_name_for_initials = $comment['author_name'];

                                        if (!empty($comment['user_avatar'])) {
                                            $path_to_comment_avatar_from_script = '../' . ltrim($comment['user_avatar'], '/');
                                            if (file_exists($path_to_comment_avatar_from_script)) {
                                                $avatar_comment_src = htmlspecialchars($path_to_comment_avatar_from_script);
                                                $display_comment_initials = false;
                                            }
                                        }

                                        if ($display_comment_initials) {
                                            $initial_comment = '';
                                            if (!empty($author_name_for_initials)) {
                                                if (function_exists('mb_strtoupper') && function_exists('mb_substr')) {
                                                    $initial_comment = htmlspecialchars(mb_strtoupper(mb_substr($author_name_for_initials, 0, 1, 'UTF-8')));
                                                } elseif (function_exists('strtoupper') && function_exists('substr')) {
                                                    $initial_comment = htmlspecialchars(strtoupper(substr($author_name_for_initials, 0, 1)));
                                                }
                                            }
                                            echo '<div class="testimonial__img_initials comment-avatar-initials">' . $initial_comment . '</div>';
                                        } else {
                                            echo '<img src="' . $avatar_comment_src . '" alt="Аватар ' . htmlspecialchars($comment['author_name']) . '">';
                                        }
                                        ?>
                                    </div>
                                    <div class="comment-content">
                                        <p class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></p>
                                        <p class="comment-date"><?php echo display_date_in_user_timezone($comment['created_at']); ?></p>
                                        <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-comments">Комментариев пока нет. Будьте первым!</p>
                    <?php endif; ?>
                </section>
            </article>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
<script>
    var swiper = new Swiper('.news-swiper', {
        loop: true, grabCursor: true, pagination: { el: '.swiper-pagination', clickable: true, },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev', },
        autoplay: { delay: 5000, disableOnInteraction: false, },
    });
    document.addEventListener('DOMContentLoaded', function() {
        const copyButton = document.querySelector('.copy-link');
        if (copyButton) {
            copyButton.addEventListener('click', function() {
                const linkToCopy = this.dataset.link;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(linkToCopy).then(function() {
                        if (typeof toastr !== 'undefined') { toastr.success('Ссылка скопирована!'); }
                    }).catch(function(err) { tryAlternativeCopy(linkToCopy); });
                } else { tryAlternativeCopy(linkToCopy); }
            });
        }
        function tryAlternativeCopy(textToCopy) {
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            textArea.style.position = "fixed"; textArea.style.left = "-9999px";
            document.body.appendChild(textArea);
            textArea.focus(); textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    if (typeof toastr !== 'undefined') { toastr.success('Ссылка скопирована (fallback)!'); }
                } else { throw new Error('Fallback copy failed'); }
            } catch (err) {
                if (typeof toastr !== 'undefined') { toastr.error('Не удалось скопировать ссылку. Пожалуйста, скопируйте вручную.'); }
                else { alert('Не удалось скопировать ссылку. Пожалуйста, скопируйте вручную.'); }
            }
            document.body.removeChild(textArea);
        }

        // Показ Toastr сообщения, если оно есть (для уведомления о модерации)
        <?php if (!empty($user_feedback_message)): ?>
            <?php if (strpos(strtolower($user_feedback_message), 'ошибка') === false && strpos(strtolower($user_feedback_message), 'недопустимые') === false): ?>
                toastr.success('<?php echo addslashes($user_feedback_message); ?>');
            <?php else: ?>
                // Если это было сообщение об ошибке от $comment_error, его уже вывели в HTML
                // Этот блок Toastr для $user_feedback_message можно убрать или доработать,
                // чтобы он не дублировал $comment_error
            <?php endif; ?>
        <?php endif; ?>
    });
</script>
</body>
</html>