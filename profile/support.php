<?php
session_start();
require_once '../config/connect.php';

// Проверка авторизации (оставляем, чтобы знать, с кем общаемся, если пользователь вошел)
$user_name = "Гость";
$user_avatar_display = '../img/default-avatar.jpg'; // Путь от profile/

if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $name_parts = [];
    if (!empty($_SESSION['user']['first_name'])) $name_parts[] = $_SESSION['user']['first_name'];
    if (!empty($_SESSION['user']['last_name'])) $name_parts[] = $_SESSION['user']['last_name'];
    
    if (!empty($name_parts)) {
        $user_name = htmlspecialchars(implode(" ", $name_parts));
    } elseif (!empty($_SESSION['user']['name'])) {
        $user_name = htmlspecialchars($_SESSION['user']['name']);
    }

    if (!empty($_SESSION['user']['avatar'])) {
        $path_check = '../' . $_SESSION['user']['avatar']; // Путь от profile/
        if (file_exists($path_check)) {
            $user_avatar_display = htmlspecialchars($path_check);
        }
    }
}

// Получение информации для хедера (корзина)
$cart_quantities = [];
$has_items_in_cart = false;
if (isset($_SESSION['user']['id'])) {
    $user_id_for_cart = $_SESSION['user']['id'];
    $query_cart_header = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart_header = $connect->prepare($query_cart_header);
    if ($stmt_cart_header) {
        $stmt_cart_header->bind_param("i", $user_id_for_cart);
        $stmt_cart_header->execute();
        $result_cart_header = $stmt_cart_header->get_result();
        while ($row_cart_header = $result_cart_header->fetch_assoc()) {
            $cart_quantities[$row_cart_header['product_id']] = $row_cart_header['quantity'];
        }
        $stmt_cart_header->close();
    }
    $has_items_in_cart = !empty($cart_quantities);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoffeeeFan - Чат поддержки</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="profile_style_v2.css?v=<?php echo time(); ?>"> <!-- Общие стили карточки -->
    <link rel="stylesheet" href="support_chat_style.css?v=<?php echo time(); ?>"> <!-- Новый CSS для чата -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <li class="nav-main__item"><a class="nav-main__link" href="../Акции/index.php">Акции</a></li>
                    </ul>
                        <img class="header__logo" src="../img/logo.svg" alt="CoffeeeFan Logo">
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
                                $avatar_path_header = '../img/icons8.png';
                                if (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                    $path_check = '../' . $_SESSION['user']['avatar'];
                                    if (file_exists($path_check)) {
                                        $avatar_path_header = htmlspecialchars($path_check);
                                    }
                                }
                             ?>
                             <img src="<?php echo $avatar_path_header; ?>" class="profile-avatar" alt="Профиль">
                        </nav>
                         <?php if (!isset($_SESSION['user'])): ?>
                            <ul class="submenu">
                                <li><a class="log" href="../auth/authorization.php">Вход</a></li>
                                <li><a class="log" href="../auth/register.php">Регистрация</a></li>
                            </ul>
                        <?php else: ?>
                            <ul class="submenu">
                                <li class="user-info">
                                    <div class="user-avatar">
                                        <?php
                                            $avatar_submenu_path = '../img/default-avatar.jpg';
                                            if (isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])) {
                                                $path_check_submenu = '../' . $_SESSION['user']['avatar'];
                                                if (file_exists($path_check_submenu)) {
                                                    $avatar_submenu_path = htmlspecialchars($path_check_submenu);
                                                }
                                            }
                                        ?>
                                        <img src="<?php echo $avatar_submenu_path; ?>" alt="Аватар">
                                    </div>
                                    <div class="user-details">
                                        <span class="user-name"><?= htmlspecialchars($_SESSION["user"]['first_name'] ?? ($_SESSION["user"]['name'] ?? 'Пользователь')) ?></span>
                                        <span class="user-email"><?= htmlspecialchars($_SESSION["user"]['email'] ?? '') ?></span>
                                    </div>
                                </li>
                                <li class="menu-divider"></li>
                                <li><a class="menu-item" href="profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                <li><a class="menu-item" href="orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                <li><a class="menu-item" href="support.php"><i class="icon-heart"></i>Поддержка</a></li> 
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

    <main class="profile-page-main-v2 support-chat-page">
        <div class="support-chat-container">
            <div class="chat-header">
                <img src="../uploads/avatars/support-avatar.gif" alt="Support Avatar" class="support-avatar-chat">
                <div class="support-info">
                    <span class="support-name">Служба поддержки CoffeeeFan</span>
                    <span class="support-status online">Онлайн</span>
                </div>
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Начальное сообщение от поддержки -->
                <div class="message support">
                    <img src="../uploads/avatars/support-avatar.gif" alt="S" class="message-avatar">
                    <div class="message-content">
                        <p>Здравствуйте, <?php echo $user_name; ?>! Чем мы можем вам помочь сегодня?</p>
                        <span class="message-time"><?php echo date("H:i"); ?></span>
                    </div>
                </div>
                <!-- Сообщения будут добавляться сюда -->
            </div>
            <div class="chat-input-area">
                <textarea id="chatMessageInput" placeholder="Напишите ваше сообщение..." rows="1"></textarea>
                <button id="sendMessageButton" title="Отправить">
                    <span class="material-icons-outlined">send</span>
                </button>
            </div>
            <div class="chat-contact-info">
                <p>Если чат недоступен или вам удобнее другой способ связи:</p>
                <p><i class="fas fa-phone-alt"></i> Телефон: <a href="tel:+79526267236">+7 (952) 626-72-36</a></p>
                <p><i class="fas fa-envelope"></i> Email: <a href="mailto:info@coffeefan.ru">info@coffeefan.ru</a></p>
            </div>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chatMessages');
            const messageInput = document.getElementById('chatMessageInput');
            const sendButton = document.getElementById('sendMessageButton');
            const userAvatar = "<?php echo $user_avatar_display; ?>"; // PHP передает путь к аватару пользователя

            function addMessageToChat(message, sender, avatarSrc) {
                const messageElement = document.createElement('div');
                messageElement.classList.add('message', sender);

                const avatarElement = document.createElement('img');
                avatarElement.src = avatarSrc;
                avatarElement.alt = sender === 'user' ? 'U' : 'S';
                avatarElement.classList.add('message-avatar');
                messageElement.appendChild(avatarElement);

                const contentElement = document.createElement('div');
                contentElement.classList.add('message-content');
                
                const textElement = document.createElement('p');
                textElement.textContent = message;
                contentElement.appendChild(textElement);

                const timeElement = document.createElement('span');
                timeElement.classList.add('message-time');
                const now = new Date();
                timeElement.textContent = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                contentElement.appendChild(timeElement);
                
                messageElement.appendChild(contentElement);
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight; // Автопрокрутка вниз
            }

            function handleSendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText === '') return;

                addMessageToChat(messageText, 'user', userAvatar);
                messageInput.value = '';
                messageInput.style.height = 'auto'; // Сброс высоты textarea

                // Имитация ответа поддержки
                setTimeout(() => {
                    let reply = "Спасибо за ваше сообщение! Оператор скоро подключится.";
                    if (messageText.toLowerCase().includes("заказ")) {
                        reply = "Уточните, пожалуйста, номер вашего заказа, чтобы мы могли вам помочь.";
                    } else if (messageText.toLowerCase().includes("проблема")) {
                        reply = "Опишите, пожалуйста, вашу проблему подробнее, мы постараемся разобраться.";
                    }
                    addMessageToChat(reply, 'support', '../uploads/avatars/support-avatar.gif');
                }, 1000 + Math.random() * 1000);
            }

            sendButton.addEventListener('click', handleSendMessage);
            messageInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSendMessage();
                }
            });

            // Автоматическое изменение высоты textarea
            messageInput.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>