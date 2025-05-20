<?php
session_start();
require_once '../config/connect.php';

// Определение данных пользователя для чата
$user_name_chat = "Гость"; // Имя по умолчанию
// Путь к аватару пользователя или дефолтному аватару.
// Важно: путь должен быть корректным относительно HTML-страницы, где он будет использоваться в <img>
$user_avatar_display_chat = '../img/default-avatar.jpg'; // Дефолтный аватар

if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $name_parts_chat = [];
    if (!empty($_SESSION['user']['first_name'])) {
        $name_parts_chat[] = $_SESSION['user']['first_name'];
    }
    if (!empty($_SESSION['user']['last_name'])) {
        $name_parts_chat[] = $_SESSION['user']['last_name'];
    }
    
    if (!empty($name_parts_chat)) {
        $user_name_chat = htmlspecialchars(implode(" ", $name_parts_chat));
    } elseif (!empty($_SESSION['user']['name'])) { // Для совместимости, если first_name/last_name нет
        $user_name_chat = htmlspecialchars($_SESSION['user']['name']);
    }

    // Обработка аватара пользователя
    if (!empty($_SESSION['user']['avatar'])) {
        // Предполагаем, что $_SESSION['user']['avatar'] хранит путь от корня сайта, например, "uploads/avatars/file.jpg"
        // Формируем относительный путь от текущего скрипта (profile/support.php) к файлу аватара
        $path_to_avatar_from_script_chat = '../' . ltrim($_SESSION['user']['avatar'], '/'); 
        
        // Проверяем существование файла по полному пути на сервере
        $full_server_path_to_avatar_chat = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($_SESSION['user']['avatar'], '/');
        // Убедитесь, что $_SERVER['DOCUMENT_ROOT'] настроен правильно на вашем сервере.
        // Для локального сервера OSPanel это может быть что-то вроде 'C:/OSPanel/domains/your_site.com/'
        // На хостинге Beget это будет корректный корневой путь.

        if (file_exists($full_server_path_to_avatar_chat)) {
            $user_avatar_display_chat = htmlspecialchars($path_to_avatar_from_script_chat);
        }
    }
}

// Получение информации для хедера (корзина) - остается как было
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
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>"> <!-- Общий стиль сайта -->
    <link rel="stylesheet" href="profile_style_v2.css?v=<?php echo time(); ?>"> <!-- Общие стили для карточки профиля (если чат внутри такой карточки) -->
    <link rel="stylesheet" href="support_chat_style.css?v=<?php echo time(); ?>"> <!-- Стили для чата -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
        // Переменная для подсветки активного пункта меню в хедере
        $is_support_chat_page = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
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
                        <p>Здравствуйте, <?php echo $user_name_chat; // Используем подготовленное имя ?>! Чем мы можем вам помочь сегодня?</p>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script> <?php // Toastr для уведомлений, если понадобится ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chatMessages');
            const messageInput = document.getElementById('chatMessageInput');
            const sendButton = document.getElementById('sendMessageButton');
            
            // Получаем путь к аватару пользователя из PHP
            const userAvatar = "<?php echo $user_avatar_display_chat; ?>"; 
            const supportAvatar = "../uploads/avatars/support-avatar.gif";

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
                textElement.innerHTML = message.replace(/\n/g, '<br>'); // Отображаем переносы строк
                contentElement.appendChild(textElement);

                const timeElement = document.createElement('span');
                timeElement.classList.add('message-time');
                const now = new Date();
                timeElement.textContent = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                contentElement.appendChild(timeElement);
                
                messageElement.appendChild(contentElement);
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function addTypingIndicator() {
                const typingElement = document.createElement('div');
                typingElement.classList.add('message', 'support', 'typing-indicator');
                typingElement.innerHTML = `
                    <img src="${supportAvatar}" alt="S" class="message-avatar">
                    <div class="message-content">
                        <p><span class="dot"></span><span class="dot"></span><span class="dot"></span></p>
                    </div>`;
                chatMessages.appendChild(typingElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function removeTypingIndicator() {
                const typingIndicator = chatMessages.querySelector('.typing-indicator');
                if (typingIndicator) {
                    typingIndicator.remove();
                }
            }

            function handleSendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText === '') return;

                addMessageToChat(messageText, 'user', userAvatar);
                messageInput.value = '';
                messageInput.style.height = 'auto'; 

                addTypingIndicator(); 

                $.ajax({
                    url: 'handle_chat_message.php', // Путь к PHP обработчику
                    type: 'POST',
                    data: { message: messageText },
                    dataType: 'json',
                    success: function(response) {
                        removeTypingIndicator(); 
                        if (response && response.reply) {
                            addMessageToChat(response.reply, 'support', supportAvatar);
                        } else {
                            addMessageToChat('Извините, чат-бот временно недоступен или произошла ошибка.', 'support', supportAvatar);
                        }
                    },
                    error: function(xhr, status, error) {
                        removeTypingIndicator(); 
                        console.error("Chat AJAX error:", status, error, xhr.responseText);
                        addMessageToChat('Произошла ошибка при отправке сообщения. Пожалуйста, проверьте ваше интернет-соединение и попробуйте снова.', 'support', supportAvatar);
                    }
                });
            }

            if(sendButton) {
                sendButton.addEventListener('click', handleSendMessage);
            }

            if(messageInput) {
                messageInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        handleSendMessage();
                    }
                });

                messageInput.addEventListener('input', function () {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        });
    </script>
</body>
</html>