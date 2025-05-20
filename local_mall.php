<?php
session_start();
require_once 'config/connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: auth/authorization.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

$query = "SELECT p.id, p.title, p.price, p.image, p.composition, p.features, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}
$stmt->close();

$user_name_checkout = '';
$user_email_checkout = '';

if(isset($_SESSION['user'])) {
    $user_name_checkout = $_SESSION['user']['first_name'] ?? ($_SESSION['user']['name'] ?? '');
    $user_email_checkout = $_SESSION['user']['email'] ?? '';
}

$has_items_in_cart = false;
if (isset($_SESSION['user']['id'])) {
    $user_id_for_header = $_SESSION['user']['id'];
    $query_cart_header = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
    $stmt_cart_header = $connect->prepare($query_cart_header);
    if ($stmt_cart_header) {
        $stmt_cart_header->bind_param("i", $user_id_for_header);
        $stmt_cart_header->execute();
        $result_cart_header = $stmt_cart_header->get_result();
        while ($row_cart_header = $result_cart_header->fetch_assoc()) {
            // Эта переменная не используется в текущем контексте файла, но оставлена, если нужна для header.php
        }
        $has_items_in_cart = $result_cart_header->num_rows > 0; // Проверяем, есть ли строки
        $stmt_cart_header->close();
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffeee shop - Корзина</title> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">    
    <style>
        /* Стили для формы оформления */
        .checkout-form {
            background-color: #24211F;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px solid #333;
            display: none; /* Изначально форма скрыта */
        }
        .checkout-form h4 {
            color: #C99E71;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.3em;
            font-family: 'Righteous', cursive;
        }
        .form-group-checkout {
            margin-bottom: 18px;
        }
        .form-group-checkout label {
            display: block;
            color: #ccc;
            margin-bottom: 6px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .form-group-checkout input[type="text"],
        .form-group-checkout input[type="email"],
        .form-group-checkout input[type="tel"],
        .form-group-checkout textarea,
        .form-group-checkout select {
            width: 100%;
            padding: 10px 12px;
            background-color: #1C1814;
            border: 1px solid #444;
            border-radius: 6px;
            color: #FFFFFF;
            font-size: 1em;
            font-family: 'Urbanist', sans-serif;
        }
        .form-group-checkout input:focus,
        .form-group-checkout textarea:focus,
        .form-group-checkout select:focus {
            border-color: #C99E71;
            outline: none;
            box-shadow: 0 0 0 2px rgba(201, 158, 113, 0.2);
        }
        /* Кнопка "Оформить заказ" внутри формы */
        .checkout-form .cart__checkout_btn_style { 
            width: 100%;
            margin-top: 10px;
            padding: 15px 30px;
            font: 700 18px 'Inter', Arial, Helvetica, sans-serif;
            text-align: center;
            background-color: #C99E71;
            color: #14110E;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            border: none;
            cursor: pointer;
        }
        .checkout-form .cart__checkout_btn_style:hover {
            background-color: #bd864b;
            transform: translateY(-2px);
        }
        .checkout-form .cart__checkout_btn_style:disabled {
            background-color: #a07c58;
            cursor: not-allowed;
        }

        /* Кнопка "Перейти к оформлению" */
        .proceed-to-checkout-btn {
            display: block;
            width: 100%;
            max-width: 300px; /* Ограничим ширину для лучшего вида */
            margin: 25px auto 0 auto; /* Центрируем и добавляем отступ */
            padding: 15px 25px;
            font-size: 1.1em;
            font-weight: 600;
            color: #14110E;
            background-color: #C99E71;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .proceed-to-checkout-btn:hover {
            background-color: #bd864b;
            transform: translateY(-2px);
        }
        .proceed-to-checkout-btn:disabled {
            background-color: #a07c58;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* Стили для модального окна (остаются как были) */
        .modal { display: none; /* ... */ }
        .modal-content { /* ... */ }
        /* ... и т.д. для модалки ... */
         .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-content {
            background-color: #1C1814;
            color: #FFFFFF;
            padding: 30px;
            border: 1px solid #444;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            position: relative;
            animation: fadeInModal 0.3s ease-out;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        @keyframes fadeInModal {
            from {opacity: 0; transform: scale(0.95) translateY(-20px);}
            to {opacity: 1; transform: scale(1) translateY(0);}
        }
        .modal-close-btn-custom {
            position: absolute;
            top: 15px;         
            right: 15px;       
            background-color: rgba(255, 77, 77, 0.7);
            color: #fff;
            padding: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            line-height: 28px;
            text-align: center;
            box-shadow: none;
            border: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            cursor: pointer;
            z-index: 10; 
        }
        .modal-close-btn-custom:hover {
            background-color: #e60000;
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.4);
        }
        .modal-product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin: 0 auto 20px auto;
            border: 2px solid #333;
        }
        .modal-product-title {
            font: 28px/36px 'Righteous', cursive;
            color: #C99E71;
            text-align: center;
            margin-bottom: 25px;
        }
        .modal-product-details {
            text-align: left;
            max-height: calc(90vh - 280px); 
            overflow-y: auto;
            padding-right: 15px;
            margin-bottom: 10px;
        }
        .modal-product-details h4 {
            color: #C99E71;
            margin-top: 15px;
            margin-bottom: 8px;
            font-size: 1.2em;
            font-weight: 600;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .modal-product-details h4:first-child {
            margin-top: 0;
        }
        .modal-product-details p {
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 15px;
            opacity: 0.9;
            word-wrap: break-word;
        }
        .modal-product-details p:last-child {
            margin-bottom: 0;
        }
        .modal-product-details::-webkit-scrollbar { width: 8px; }
        .modal-product-details::-webkit-scrollbar-track { background: #2a2623; border-radius: 4px; }
        .modal-product-details::-webkit-scrollbar-thumb { background: #7a5f43; border-radius: 4px; }
        .modal-product-details::-webkit-scrollbar-thumb:hover { background: #C99E71; }
    </style>
</head>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once 'header_footer_elements/header.php'; 
    ?>
    <main>
        <section id="cart-section" class="section-main">
            <div class="container">
                <h3 class="section-subtitle">Ваша Корзина</h3>
                <div class="cart">
                    <?php if (count($cart_items) > 0): ?>
                        <div class="cart__items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart__item"
                                     data-product-id="<?= $item['id'] ?>"
                                     data-title="<?= htmlspecialchars($item['title']) ?>"
                                     data-image="../<?php echo htmlspecialchars(ltrim($item['image'], '/')); ?>"
                                     data-composition="<?= htmlspecialchars($item['composition'] ?? 'Информация о составе отсутствует.') ?>"
                                     data-features="<?= htmlspecialchars($item['features'] ?? 'Информация об особенностях отсутствует.') ?>"
                                >
                                    <img class="cart__item-image" src="../<?php echo htmlspecialchars(ltrim($item['image'], '/')); ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                    <div class="cart__item-info">
                                        <h3 class="cart__item-title cart__item-title--clickable"><?= htmlspecialchars($item['title']) ?></h3>
                                        <p class="cart__item-price">Цена: <?= number_format((float)$item['price'], 2, '.', '') ?>₽</p>
                                        <div class="cart__item-quantity-controls">
                                            <span class="quantity-label">Количество:</span>
                                            <button class="quantity-btn quantity-decrease" data-product-id-ctrl="<?= $item['id'] ?>">-</button>
                                            <input type="text" class="quantity-input" value="<?= htmlspecialchars($item['quantity']) ?>" readonly data-product-id-input="<?= $item['id'] ?>">
                                            <button class="quantity-btn quantity-increase" data-product-id-ctrl="<?= $item['id'] ?>">+</button>
                                        </div>
                                        <p class="cart__item-subtotal">Сумма: <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, '.', '') ?>₽</p>
                                    </div>
                                    <button class="cart__item-remove btn-danger" data-product-id-remove="<?= $item['id'] ?>">
                                        <span class="material-icons-outlined">delete</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart__total">
                            <p class="cart__total-text">Общая сумма:</p>
                            <p class="cart__total-price">0.00₽</p>
                        </div>

                        <!-- КНОПКА ДЛЯ ПОКАЗА ФОРМЫ ОФОРМЛЕНИЯ -->
                        <button class="proceed-to-checkout-btn" id="proceedToCheckoutBtn">Перейти к оформлению</button>

                        <!-- ФОРМА ОФОРМЛЕНИЯ ЗАКАЗА (изначально скрыта) -->
                        <div class="checkout-form" id="checkoutFormContainer">
                            <h4>Детали заказа и доставка</h4>
                            <div id="checkout-errors-onpage" style="color: #e74c3c; margin-bottom: 15px; font-size: 0.9em; text-align:left;"></div>
                            <div class="form-group-checkout">
                                <label for="checkout_name">Ваше имя *</label>
                                <input type="text" id="checkout_name" name="checkout_name" value="<?php echo htmlspecialchars($user_name_checkout); ?>" required>
                            </div>
                            <div class="form-group-checkout">
                                <label for="checkout_email">Email *</label>
                                <input type="email" id="checkout_email" name="checkout_email" value="<?php echo htmlspecialchars($user_email_checkout); ?>" required>
                            </div>
                            <div class="form-group-checkout">
                                <label for="checkout_phone">Телефон *</label>
                                <input type="tel" id="checkout_phone" name="checkout_phone" placeholder="+7 (___) ___-__-__" required>
                            </div>
                            <div class="form-group-checkout">
                                <label for="checkout_address">Адрес доставки (Город, улица, дом, квартира) *</label>
                                <textarea id="checkout_address" name="checkout_address" rows="3" required></textarea>
                            </div>
                            <div class="form-group-checkout">
                                <label for="checkout_payment_method">Способ оплаты *</label>
                                <select id="checkout_payment_method" name="checkout_payment_method" required>
                                    <option value="">-- Выберите способ --</option>
                                    <option value="cash_on_delivery">Оплата при получении</option>
                                    <option value="card_online_mock">Картой онлайн (имитация)</option>
                                </select>
                            </div>
                            <div class="form-group-checkout">
                                <label for="checkout_comment">Комментарий к заказу (необязательно)</label>
                                <textarea id="checkout_comment" name="checkout_comment" rows="2"></textarea>
                            </div>
                            
                            <button class="cart__checkout_btn_style" id="submitOrderBtnFinal">Оформить заказ</button>
                        </div>
                        <!-- КОНЕЦ ФОРМЫ -->

                    <?php else: ?>
                        <p class="cart__empty">Корзина пуста.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <div id="product-details-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close-btn-custom" aria-label="Закрыть модальное окно">✖</button>
            <img id="modal-product-image" src="" alt="Изображение товара" class="modal-product-image">
            <h2 id="modal-product-title" class="modal-product-title"></h2>
            <div class="modal-product-details">
                <h4>Состав:</h4>
                <p id="modal-product-composition"></p>
                <h4>Особенности:</h4>
                <p id="modal-product-features"></p>
            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
    $(document).ready(function() {
        // --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (updateCartIconInHeader, updateCartItemDisplay, updateTotalPriceAndCheckoutInterface) ---
        // ОСТАВЛЯЕМ ИХ КАК В ПРЕДЫДУЩЕМ ВАРИАНТЕ (с небольшими правками для отмены)
        function updateCartIconInHeader() {
            let totalItemsInCart = 0;
            $('.cart__item:not(.item-pending-removal)').each(function() { // Исключаем элементы, ожидающие удаления
                const quantity = parseInt($(this).find('.quantity-input').val()) || 0;
                totalItemsInCart += quantity;
            });
            const cartButton = $('.header-action__cart-1');
            if (totalItemsInCart > 0) {
                cartButton.addClass('active');
            } else {
                cartButton.removeClass('active');
            }
        }

        function updateCartItemDisplay(productId, newQuantity, isPendingRemoval = false) {
            const cartItem = $(`.cart__item[data-product-id="${productId}"]`);
            if (cartItem.length) {
                cartItem.find('.quantity-input').val(newQuantity);
                const priceText = cartItem.find('.cart__item-price').text();
                const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                if (!isNaN(price)) {
                    const itemSubtotal = price * newQuantity;
                    cartItem.find('.cart__item-subtotal').text('Сумма: ' + itemSubtotal.toFixed(2) + '₽');
                }
                if (isPendingRemoval) {
                    cartItem.addClass('item-pending-removal');
                } else {
                    cartItem.removeClass('item-pending-removal');
                }
            }
        }

        function updateTotalPriceAndCheckoutInterface() {
            var totalPrice = 0;
            var itemCount = 0;
            var totalQuantity = 0;
            $('.cart__item:not(.item-pending-removal)').each(function() { // Исключаем элементы, ожидающие удаления
                itemCount++;
                var $this = $(this);
                var priceText = $this.find('.cart__item-price').text();
                var price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                var quantity = parseInt($this.find('.quantity-input').val());
                if (!isNaN(price) && !isNaN(quantity)) {
                    var itemSubtotal = price * quantity;
                    totalPrice += itemSubtotal;
                    totalQuantity += quantity;
                    $this.find('.cart__item-subtotal').text('Сумма: ' + itemSubtotal.toFixed(2) + '₽');
                }
            });
            $('.cart__total-price').text(totalPrice.toFixed(2) + '₽');

            var $proceedBtn = $('#proceedToCheckoutBtn');
            var $checkoutFormDiv = $('#checkoutFormContainer');
            var $cartTotalDiv = $('.cart__total');
            var $cartEmptyMsg = $('.cart__empty');
            var $cartItemsContainer = $('.cart__items');

            if (itemCount > 0 && totalQuantity > 0) {
                $cartTotalDiv.show();
                $proceedBtn.show().prop('disabled', false);
                if ($cartEmptyMsg.length > 0) $cartEmptyMsg.hide();
            } else {
                $cartTotalDiv.hide();
                $proceedBtn.hide();
                $checkoutFormDiv.hide();
                if ($cartItemsContainer.children('.cart__item:not(.item-pending-removal)').length === 0) {
                    if ($cartEmptyMsg.length === 0) {
                        $cartItemsContainer.after('<p class="cart__empty">Корзина пуста.</p>');
                    } else {
                        $cartEmptyMsg.text('Корзина пуста.').show();
                    }
                }
            }
            updateCartIconInHeader();
        }

        // --- ЛОГИКА ОТМЕНЫ УДАЛЕНИЯ ---
        let removalTimers = {}; // Объект для хранения таймеров удаления

        // --- ОБРАБОТЧИК ПОЛНОГО УДАЛЕНИЯ ТОВАРА (КРЕСТИК) ---
        $('.cart__items').on('click', '.cart__item-remove', function(event) {
            event.stopPropagation();
            var productId = $(this).data('product-id-remove');
            var $cartItem = $(this).closest('.cart__item');

            // Если уже есть таймер на удаление этого товара, ничего не делаем (избегаем двойных кликов)
            if (removalTimers[productId]) {
                return;
            }

            // Визуально "удаляем" товар и показываем опцию отмены
            $cartItem.addClass('item-pending-removal'); 
            updateTotalPriceAndCheckoutInterface(); // Обновляем сумму, исключая этот товар

            // Показываем Toastr с кнопкой "Отменить"
            var toastMessage = 'Товар удален. <button type="button" class="btn btn-link btn-sm toastr-undo-btn" data-undo-product-id="' + productId + '">Отменить</button>';
            var $toast = toastr.info(toastMessage, '', {
                "closeButton": false,
                "timeOut": 5000, // Время на отмену (5 секунд)
                "extendedTimeOut": 2000,
                "tapToDismiss": false, // Не закрывать по клику на сообщение (только по "Отменить" или по таймауту)
                "onHidden": function() { // Сработает, когда Toastr исчезнет (по таймауту или если был закрыт иначе)
                    // Если таймер все еще существует (т.е. "Отменить" не была нажата)
                    if (removalTimers[productId]) {
                        performActualRemoval(productId, $cartItem);
                        delete removalTimers[productId]; // Удаляем таймер
                    }
                }
            });

            // Сохраняем таймер (на самом деле, здесь мы сохраняем сам $toast для возможности его закрыть,
            // а реальный "таймер" - это timeOut у Toastr)
            removalTimers[productId] = $toast; 
        });

        // Обработчик для кнопки "Отменить" в Toastr
        // Используем делегирование, так как кнопка создается динамически
        $(document).on('click', '.toastr-undo-btn', function() {
            var productIdToUndo = $(this).data('undo-product-id');
            var $cartItemToUndo = $(`.cart__item[data-product-id="${productIdToUndo}"]`);

            if (removalTimers[productIdToUndo]) {
                toastr.clear(removalTimers[productIdToUndo]); // Закрываем конкретный Toastr
                delete removalTimers[productIdToUndo]; // Удаляем "таймер"

                $cartItemToUndo.removeClass('item-pending-removal'); // Возвращаем товар визуально
                updateTotalPriceAndCheckoutInterface(); // Пересчитываем сумму
                toastr.success('Удаление товара отменено.');
            }
        });

        // Функция фактического удаления с сервера
        function performActualRemoval(productId, $cartItemElement) {
            $.ajax({
                type: 'POST',
                url: 'Продукты/remove_from_cart.php', 
                data: { 
                    product_id: productId,
                    remove_all_units: true 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.removed_all === true) {
                        $cartItemElement.remove(); // Окончательно удаляем из DOM
                        // Обновление общей суммы уже произошло при визуальном скрытии, 
                        // но на всякий случай можно вызвать еще раз, если есть сомнения.
                        updateTotalPriceAndCheckoutInterface(); 
                        // Сообщение об успешном удалении можно не показывать, т.к. Toastr уже был
                    } else {
                        // Если удаление не удалось, возвращаем товар визуально
                        $cartItemElement.removeClass('item-pending-removal');
                        updateTotalPriceAndCheckoutInterface();
                        toastr.error(response.message || 'Не удалось окончательно удалить товар. Попробуйте еще раз.');
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка AJAX при окончательном удалении:', xhr.responseText);
                    $cartItemElement.removeClass('item-pending-removal'); // Возвращаем товар, если ошибка сервера
                    updateTotalPriceAndCheckoutInterface();
                    toastr.error('Ошибка сервера при удалении товара.');
                }
            });
        }


        // --- ОБРАБОТЧИКИ ДЛЯ КНОПОК "+" и "-" ---
        // Увеличение количества
        $('.cart__items').on('click', '.quantity-increase', function() {
            const productId = $(this).data('product-id-ctrl');
            const $cartItem = $(this).closest('.cart__item');
            if ($cartItem.hasClass('item-pending-removal')) return; // Не даем изменять товар, ожидающий удаления

            $.ajax({
                type: 'POST',
                url: 'Продукты/add_to_cart.php', 
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && typeof response.quantity !== 'undefined') {
                        updateCartItemDisplay(productId, response.quantity);
                        updateTotalPriceAndCheckoutInterface();
                    } else {
                        toastr.error(response.message || 'Не удалось увеличить количество.');
                    }
                },
                error: function(xhr) { /* ... */ }
            });
        });

        // Уменьшение количества
        $('.cart__items').on('click', '.quantity-decrease', function() {
            const productId = $(this).data('product-id-ctrl');
            const $inputField = $(`.quantity-input[data-product-id-input="${productId}"]`);
            let currentQuantity = parseInt($inputField.val());
            const $cartItem = $(this).closest('.cart__item');
            if ($cartItem.hasClass('item-pending-removal')) return;

            if (currentQuantity <= 1) { 
                // Ничего не делаем и не показываем сообщение
                return; 
            }

            $.ajax({
                type: 'POST',
                url: 'Продукты/remove_from_cart.php', 
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && typeof response.quantity !== 'undefined') {
                        if (response.quantity > 0) {
                            updateCartItemDisplay(productId, response.quantity);
                            updateTotalPriceAndCheckoutInterface();
                        } 
                        // Ветка с quantity = 0 не должна здесь срабатывать из-за проверки currentQuantity <= 1
                    } else {
                        toastr.error(response.message || 'Не удалось уменьшить количество.');
                    }
                },
                error: function(xhr) { /* ... */ }
            });
        });

        // --- МОДАЛЬНОЕ ОКНО ДЕТАЛЕЙ ТОВАРА (без изменений) ---
        var productDetailModal = $('#product-details-modal');
        var productDetailModalCloseBtn = productDetailModal.find('.modal-close-btn-custom, .delete-recipe-btn');
        $('.cart__items').on('click', '.cart__item', function(e) {
            if ($(e.target).closest('.cart__item-remove').length || $(e.target).closest('.quantity-btn').length || $(e.target).is('.quantity-input')) {
                return;
            }
            var cartItemData = $(this);
            $('#modal-product-image').attr('src', cartItemData.data('image'));
            $('#modal-product-title').text(cartItemData.data('title'));
            $('#modal-product-composition').html( (cartItemData.data('composition') || 'Информация о составе отсутствует.').replace(/\n/g, '<br>') );
            $('#modal-product-features').html( (cartItemData.data('features') || 'Информация об особенностях отсутствует.').replace(/\n/g, '<br>') );
            productDetailModal.css('display', 'flex');
        });
        productDetailModalCloseBtn.on('click', function() { productDetailModal.hide(); });
        $(window).on('click', function(event) { if ($(event.target).is(productDetailModal)) { productDetailModal.hide(); } });

        // --- ФОРМА ОФОРМЛЕНИЯ ЗАКАЗА (без изменений) ---
        var $checkoutFormContainer = $('#checkoutFormContainer');
        var $proceedToCheckoutBtn = $('#proceedToCheckoutBtn');
        var $submitOrderBtnFinal = $('#submitOrderBtnFinal');
        var $checkoutErrorsOnPage = $('#checkout-errors-onpage');

        $proceedToCheckoutBtn.on('click', function() {
            $checkoutFormContainer.slideDown(400); 
            $(this).slideUp(200); 
        });

        $submitOrderBtnFinal.on('click', function() {
            var $thisButton = $(this);
            $checkoutErrorsOnPage.html('').hide(); // Скрываем ошибки при новой попытке
            
            var name = $('#checkout_name').val().trim();
            var email = $('#checkout_email').val().trim();
            var phone = $('#checkout_phone').val().trim();
            var address = $('#checkout_address').val().trim();
            var paymentMethod = $('#checkout_payment_method').val();
            var comment = $('#checkout_comment').val().trim();

            var errors = [];
            if (name === '') errors.push('Пожалуйста, укажите ваше имя.');
            if (email === '') errors.push('Пожалуйста, укажите ваш Email.');
            else {
                var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (!emailRegex.test(email)) errors.push('Пожалуйста, введите корректный Email.');
            }
            if (phone === '') errors.push('Пожалуйста, укажите ваш телефон.');
            else if (!/^\+?[0-9\s\-\(\)]{7,20}$/.test(phone)) errors.push('Некорректный формат телефона.');
            if (address === '') errors.push('Пожалуйста, укажите адрес доставки.');
            if (paymentMethod === '') errors.push('Пожалуйста, выберите способ оплаты.');

            if (errors.length > 0) {
                var errorHtml = '<ul>';
                errors.forEach(function(error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                errorHtml += '</ul>';
                $checkoutErrorsOnPage.html(errorHtml).show();
                return; 
            }

            $thisButton.prop('disabled', true).text('Оформляется...');

            $.ajax({
                type: 'POST',
                url: 'checkout.php', 
                data: {
                    customer_name: name,
                    customer_email: email,
                    customer_phone: phone,
                    delivery_address: address,
                    payment_method: paymentMethod,
                    order_comment: comment
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success(response.message || 'Заказ успешно оформлен!');
                        $('.cart__items').empty(); 
                        $('.cart__total').hide();
                        $checkoutFormContainer.hide(); 
                        var $cartEmpty = $('.cart__empty');
                        if ($cartEmpty.length === 0) {
                            $('.cart').append('<p class="cart__empty">Корзина пуста. Ваш заказ оформлен!</p>');
                        } else {
                            $cartEmpty.text('Корзина пуста. Ваш заказ оформлен!').show();
                        }
                        updateCartIconInHeader(); 
                    } else {
                        toastr.error(response.message || 'Ошибка при оформлении заказа.');
                        $thisButton.prop('disabled', false).text('Оформить заказ');
                        if(response.message) {
                            $checkoutErrorsOnPage.html('<ul><li>' + response.message + '</li></ul>').show();
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка AJAX при оформлении заказа:', xhr.responseText);
                    toastr.error('Произошла ошибка при связи с сервером (оформление заказа).');
                    $thisButton.prop('disabled', false).text('Оформить заказ');
                    $checkoutErrorsOnPage.html('<ul><li>Произошла ошибка при связи с сервером.</li></ul>').show();
                }
            });
        });

        // --- ИНИЦИАЛИЗАЦИЯ ---
        updateTotalPriceAndCheckoutInterface(); 
        
        toastr.options = {
            "closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true,
            "positionClass": "toast-top-right", "preventDuplicates": false, "onclick": null,
            "showDuration": "300", "hideDuration": "1000", "timeOut": "4000", // Уменьшил немного время показа
            "extendedTimeOut": "1000", "showEasing": "swing", "hideEasing": "linear",
            "showMethod": "fadeIn", "hideMethod": "fadeOut"
        };
    });
    </script>
</body>
</html>