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
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
    $stmt->close();
} else {
    error_log("Cart page: Failed to prepare cart items query: " . $connect->error);
    $cart_items = []; 
}


$user_name_checkout = '';
$user_email_checkout = '';

if(isset($_SESSION['user'])) {
    $user_name_checkout = $_SESSION['user']['first_name'] ?? ($_SESSION['user']['name'] ?? '');
    $user_email_checkout = $_SESSION['user']['email'] ?? '';
}

$has_items_in_cart_header = false; 
if (isset($_SESSION['user']['id'])) {
    $user_id_for_header = $_SESSION['user']['id'];
    $query_cart_header = "SELECT product_id FROM cart WHERE user_id = ? LIMIT 1";
    $stmt_cart_header = $connect->prepare($query_cart_header);
    if ($stmt_cart_header) {
        $stmt_cart_header->bind_param("i", $user_id_for_header);
        $stmt_cart_header->execute();
        $stmt_cart_header->store_result(); 
        $has_items_in_cart_header = $stmt_cart_header->num_rows > 0;
        $stmt_cart_header->close();
    }
}
$_SESSION['has_items_in_cart'] = $has_items_in_cart_header;

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
</head>
<body>
    <?php
        $is_cart_page = true; 
        $base_web_path = ''; 
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

                        <button class="proceed-to-checkout-btn" id="proceedToCheckoutBtn">Перейти к оформлению</button>

                        <div class="checkout-form" id="checkoutFormContainer">
                            <h4>Детали заказа и доставка</h4>
                            <div id="checkout-errors-onpage"></div>
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
                    <?php else: ?>
                        <div class="cart__empty_container">
                            <img src="img/ponke-ponkesol.gif" alt="Корзина пуста" class="cart__empty-image">
                            <p class="cart__empty_text">Ваша корзина пуста.</p>
                            <a href="Продукты/index.php" class="btn-primary empty-cart-link-styled">Перейти к покупкам</a>
                        </div>
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
        function updateCartIconInHeader() {
            let totalItemsInCart = 0;
            $('.cart__item:not(.item-pending-removal)').each(function() {
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
$('.cart__item:not(.item-pending-removal)').each(function() {
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
            var $cartEmptyMsg = $('.cart__empty_container'); // Используем новый класс для контейнера
            var $cartItemsContainer = $('.cart__items');

            if (itemCount > 0 && totalQuantity > 0) {
                $cartTotalDiv.show();
                // ИЗМЕНЕНИЕ ЗДЕСЬ: Показываем кнопку "Перейти к оформлению"
                // только если форма оформления заказа еще не видна.
                if (!$checkoutFormDiv.is(':visible')) { 
                    $proceedBtn.show().prop('disabled', false);
                } else {
                    $proceedBtn.hide(); // Если форма уже видна, кнопка должна быть скрыта
                }
                if ($cartEmptyMsg.length > 0) $cartEmptyMsg.hide();
            } else {
                $cartTotalDiv.hide();
                $proceedBtn.hide();
                $checkoutFormDiv.hide(); 
                if ($cartItemsContainer.children('.cart__item:not(.item-pending-removal)').length === 0) {
                    if ($cartEmptyMsg.length === 0) {
                        $cartItemsContainer.after('<div class="cart__empty_container"><img src="img/ponke-ponkesol.gif" alt="Корзина пуста" class="cart__empty-image"><p class="cart__empty_text">Корзина пуста.</p><a href="Продукты/index.php" class="btn-primary empty-cart-link-styled">Перейти к покупкам</a></div>');
                    } else {
                         $cartEmptyMsg.find('.cart__empty_text').text('Корзина пуста.');
                         $cartEmptyMsg.show();
                    }
                }
            }
            updateCartIconInHeader();
        }

        let removalTimers = {};

        $('.cart__items').on('click', '.cart__item-remove', function(event) {
            event.stopPropagation();
            var productId = $(this).data('product-id-remove');
            var $cartItem = $(this).closest('.cart__item');
            if (removalTimers[productId]) { return; }
            $cartItem.addClass('item-pending-removal'); 
            updateTotalPriceAndCheckoutInterface();
            var toastMessage = 'Товар удален. <button type="button" class="btn btn-link btn-sm toastr-undo-btn" data-undo-product-id="' + productId + '">Отменить</button>';
            var $toast = toastr.info(toastMessage, '', {
                "closeButton": false, "timeOut": 5000, "extendedTimeOut": 2000, "tapToDismiss": false,
                "onHidden": function() {
                    if (removalTimers[productId]) {
                        performActualRemoval(productId, $cartItem);
                        delete removalTimers[productId];
                    }
                }
            });
            removalTimers[productId] = $toast; 
        });

        $(document).on('click', '.toastr-undo-btn', function() {
            var productIdToUndo = $(this).data('undo-product-id');
            var $cartItemToUndo = $(`.cart__item[data-product-id="${productIdToUndo}"]`);
            if (removalTimers[productIdToUndo]) {
                toastr.clear(removalTimers[productIdToUndo]);
                delete removalTimers[productIdToUndo];
                $cartItemToUndo.removeClass('item-pending-removal');
                updateTotalPriceAndCheckoutInterface();
                toastr.success('Удаление товара отменено.');
            }
        });

        function performActualRemoval(productId, $cartItemElement) {
            $.ajax({
                type: 'POST', url: 'Продукты/remove_from_cart.php', 
                data: { product_id: productId, remove_all_units: true },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.removed_all === true) {
                        $cartItemElement.remove();
                        updateTotalPriceAndCheckoutInterface();
                    } else {
                        $cartItemElement.removeClass('item-pending-removal');
                        updateTotalPriceAndCheckoutInterface();
                        toastr.error(response.message || 'Не удалось окончательно удалить товар.');
                    }
                },
                error: function(xhr) {
                    $cartItemElement.removeClass('item-pending-removal');
                    updateTotalPriceAndCheckoutInterface();
                    toastr.error('Ошибка сервера при удалении товара.');
                }
            });
        }

        $('.cart__items').on('click', '.quantity-increase', function() {
            const productId = $(this).data('product-id-ctrl');
            const $cartItem = $(this).closest('.cart__item');
            if ($cartItem.hasClass('item-pending-removal')) return;
            $.ajax({
                type: 'POST', url: 'Продукты/add_to_cart.php', 
                data: { product_id: productId }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && typeof response.quantity !== 'undefined') {
                        updateCartItemDisplay(productId, response.quantity);
                        updateTotalPriceAndCheckoutInterface();
                    } else {
                        toastr.error(response.message || 'Не удалось увеличить количество.');
                    }
                }, error: function(xhr) { toastr.error('Ошибка сервера.'); }
            });
        });

        $('.cart__items').on('click', '.quantity-decrease', function() {
            const productId = $(this).data('product-id-ctrl');
            const $inputField = $(`.quantity-input[data-product-id-input="${productId}"]`);
            let currentQuantity = parseInt($inputField.val());
            const $cartItem = $(this).closest('.cart__item');
            if ($cartItem.hasClass('item-pending-removal')) return;
            if (currentQuantity <= 1) { return; }
            $.ajax({
                type: 'POST', url: 'Продукты/remove_from_cart.php', 
                data: { product_id: productId }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && typeof response.quantity !== 'undefined') {
                        if (response.quantity > 0) {
                            updateCartItemDisplay(productId, response.quantity);
                            updateTotalPriceAndCheckoutInterface();
                        } 
                    } else {
                        toastr.error(response.message || 'Не удалось уменьшить количество.');
                    }
                }, error: function(xhr) { toastr.error('Ошибка сервера.');}
            });
        });

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
            $checkoutErrorsOnPage.html('').hide();
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
                errors.forEach(function(error) { errorHtml += '<li>' + error + '</li>'; });
                errorHtml += '</ul>';
                $checkoutErrorsOnPage.html(errorHtml).show();
                return; 
            }
            $thisButton.prop('disabled', true).text('Оформляется...');
            $.ajax({
                type: 'POST', url: 'checkout.php', 
                data: {
                    customer_name: name, customer_email: email, customer_phone: phone,
                    delivery_address: address, payment_method: paymentMethod, order_comment: comment
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success(response.message || 'Заказ успешно оформлен!');
                        $('.cart__items').empty(); 
                        $('.cart__total').hide();
                        $proceedToCheckoutBtn.hide();
                        $checkoutFormContainer.hide(); 
                        var $cartEmptyCont = $('.cart__empty_container');
                        if ($cartEmptyCont.length === 0) {
                            $('.cart').append('<div class="cart__empty_container"><img src="img/ponke-ponkesol.gif" alt="Корзина пуста" class="cart__empty-image"><p class="cart__empty_text">Корзина пуста. Ваш заказ оформлен!</p><a href="Продукты/index.php" class="btn-primary-v2 empty-cart-link">Перейти к покупкам</a></div>');
                        } else {
                            $cartEmptyCont.find('.cart__empty_text').text('Корзина пуста. Ваш заказ оформлен!');
                            $cartEmptyCont.show();
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
                    toastr.error('Произошла ошибка при связи с сервером (оформление заказа).');
                    $thisButton.prop('disabled', false).text('Оформить заказ');
                    $checkoutErrorsOnPage.html('<ul><li>Произошла ошибка при связи с сервером.</li></ul>').show();
                }
            });
        });
        updateTotalPriceAndCheckoutInterface(); 
        toastr.options = {
            "closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true,
            "positionClass": "toast-top-right", "preventDuplicates": false, "onclick": null,
            "showDuration": "300", "hideDuration": "1000", "timeOut": "4000",
            "extendedTimeOut": "1000", "showEasing": "swing", "hideEasing": "linear",
            "showMethod": "fadeIn", "hideMethod": "fadeOut",
            "escapeHtml": false
        };
    });
    </script>
</body>
</html>