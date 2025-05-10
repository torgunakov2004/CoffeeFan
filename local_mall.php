<?php
session_start();
require_once 'config/connect.php'; // Подключение к базе данных

if (!isset($_SESSION['user'])) {
    header("Location: auth/authorization.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// Получение товаров из корзины, включая состав и особенности
$query = "SELECT p.id, p.title, p.price, p.image, p.composition, p.features, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
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
    <link rel="stylesheet" href="styles.css">    
</head>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once 'header_footer_elements/header.php'; 
    ?>
    <main>
        <section id="cart-section" class="section-main">
            <div class="container">
                <h3 class="section-subtitle">Корзина</h3>
                <div class="cart">
                    <?php if (count($cart_items) > 0): ?>
                        <div class="cart__items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart__item"
                                     data-product-id="<?= $item['id'] ?>"
                                     data-title="<?= htmlspecialchars($item['title']) ?>"
                                     data-image="<?= htmlspecialchars($item['image']) ?>"
                                     data-composition="<?= htmlspecialchars($item['composition'] ?? 'Информация о составе отсутствует.') ?>"
                                     data-features="<?= htmlspecialchars($item['features'] ?? 'Информация об особенностях отсутствует.') ?>"
                                >
                                    <img class="cart__item-image" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                    <div class="cart__item-info">
                                        <h3 class="cart__item-title cart__item-title--clickable"><?= htmlspecialchars($item['title']) ?></h3>
                                        <p class="cart__item-price">Цена: <?= number_format((float)$item['price'], 2, '.', '') ?>₽</p>
                                        <p class="cart__item-quantity">Количество: <?= htmlspecialchars($item['quantity']) ?></p>
                                        <p class="cart__item-subtotal">Сумма: <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, '.', '') ?>₽</p>
                                    </div>
                                    <button class="cart__item-remove btn-danger" data-product-id-remove="<?= $item['id'] ?>"> <!-- Изменен data атрибут -->
                                        <span class="material-icons-outlined">delete</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cart__total">
                            <p class="cart__total-text">Общая сумма:</p>
                            <p class="cart__total-price">0.00₽</p>
                        </div>
                        <button class="cart__checkout btn-primary">Оформить заказ</button>
                    <?php else: ?>
                        <p class="cart__empty">Корзина пуста.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Модальное окно для деталей продукта -->
    <div id="product-details-modal" class="modal">
        <div class="modal-content">
            <span class="delete-recipe-btn">✖</span>
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
            // --- СУЩЕСТВУЮЩИЙ КОД ДЛЯ УДАЛЕНИЯ И МОДАЛЬНОГО ОКНА ---
            $('.cart__item-remove').on('click', function(event) {
                event.stopPropagation();
                var productId = $(this).data('product-id-remove');
                var cartItem = $(this).closest('.cart__item');
                 $.ajax({
                    type: 'POST',
                    url: 'Продукты/remove_from_cart.php', // Путь к скрипту удаления
                    data: { product_id: productId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            if (response.quantity > 0) {
                                // Обновляем количество и подытог, если товар не полностью удален
                                cartItem.find('.cart__item-quantity').text('Количество: ' + response.quantity);
                                // Пересчитываем подытог для этого элемента (если нужно)
                                var priceText = cartItem.find('.cart__item-price').text();
                                var price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                                if (!isNaN(price)) {
                                   cartItem.find('.cart__item-subtotal').text('Сумма: ' + (price * response.quantity).toFixed(2) + '₽');
                                }
                            } else {
                                cartItem.remove(); // Удаляем элемент из DOM
                            }
                            updateTotalPrice(); // Обновляем общую сумму
                            toastr.success(response.message || 'Товар обновлен в корзине');

                            // Проверяем, пуста ли корзина после удаления
                            if ($('.cart__item').length === 0) {
                                $('.cart__total').hide();
                                $('.cart__checkout').hide();
                                var $cartEmpty = $('.cart__empty');
                                if ($cartEmpty.length === 0) {
                                    $('.cart__items').after('<p class="cart__empty">Корзина пуста.</p>');
                                } else {
                                    $cartEmpty.text('Корзина пуста.').show();
                                }
                                // Обновляем иконку корзины в шапке
                                $('.header-action__cart-1').removeClass('active');
                            }
                        } else {
                            toastr.error(response.message || 'Ошибка при удалении товара.');
                        }
                    },
                    error: function(xhr) {
                        console.error('Ошибка AJAX при удалении:', xhr.responseText);
                        toastr.error('Произошла ошибка при связи с сервером (удаление).');
                    }
                });
            });

            function updateTotalPrice() {
                var totalPrice = 0;
                $('.cart__item').each(function() {
                    var $this = $(this);
                    var priceText = $this.find('.cart__item-price').text();
                    var price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                    
                    var quantityText = $this.find('.cart__item-quantity').text();
                    var quantity = parseInt(quantityText.replace(/[^0-9]/g, ''));

                    if (!isNaN(price) && !isNaN(quantity)) {
                        var itemSubtotal = price * quantity;
                        totalPrice += itemSubtotal;
                        $this.find('.cart__item-subtotal').text('Сумма: ' + itemSubtotal.toFixed(2) + '₽');
                    } else {
                        // Если не удалось получить цену или количество, можно отметить ошибку
                        // $this.find('.cart__item-subtotal').text('Сумма: Ошибка');
                        console.warn("Не удалось рассчитать подытог для элемента:", $this.data('title'));
                    }
                });
                $('.cart__total-price').text(totalPrice.toFixed(2) + '₽');

                var $cartItemsContainer = $('.cart__items');
                var $cartEmptyMessage = $('.cart__empty');
                var $cartTotal = $('.cart__total');
                var $cartCheckoutBtn = $('.cart__checkout');

                if(totalPrice > 0 && $cartItemsContainer.children('.cart__item').length > 0){
                    $cartTotal.show();
                    $cartCheckoutBtn.show();
                    if ($cartEmptyMessage.length > 0) $cartEmptyMessage.hide();
                } else {
                    $cartTotal.hide();
                    $cartCheckoutBtn.hide();
                     // Если товаров нет, показываем сообщение "Корзина пуста"
                    if ($cartItemsContainer.children('.cart__item').length === 0) {
                        if ($cartEmptyMessage.length === 0) {
                            // Если сообщения нет, добавляем его после контейнера товаров
                            $cartItemsContainer.after('<p class="cart__empty">Корзина пуста.</p>');
                        } else {
                            $cartEmptyMessage.text('Корзина пуста.').show();
                        }
                    }
                }
            }
            updateTotalPrice(); // Вызываем при загрузке страницы

            var modal = $('#product-details-modal');
            var modalCloseBtn = $('.delete-recipe-btn'); // Крестик в модальном окне

            $('.cart__item').on('click', function(e) {
                // Предотвращаем открытие модального окна, если клик был по кнопке удаления
                if ($(e.target).closest('.cart__item-remove').length) {
                    return;
                }
                var cartItem = $(this);
                $('#modal-product-image').attr('src', cartItem.data('image'));
                $('#modal-product-title').text(cartItem.data('title'));
                $('#modal-product-composition').html( (cartItem.data('composition') || 'Информация о составе отсутствует.').replace(/\n/g, '<br>') );
                $('#modal-product-features').html( (cartItem.data('features') || 'Информация об особенностях отсутствует.').replace(/\n/g, '<br>') );
                modal.css('display', 'flex');
            });

            modalCloseBtn.on('click', function() {
                modal.hide();
            });

            $(window).on('click', function(event) {
                if ($(event.target).is(modal)) {
                    modal.hide();
                }
            });
            // --- КОНЕЦ СУЩЕСТВУЮЩЕГО КОДА ---


            // +++++ НОВЫЙ КОД ДЛЯ КНОПКИ "ОФОРМИТЬ ЗАКАЗ" +++++
            $('.cart__checkout').on('click', function() {
                var $checkoutButton = $(this);
                $checkoutButton.prop('disabled', true).text('Оформляется...');

                $.ajax({
                    type: 'POST',
                    url: 'checkout.php', // Путь к вашему checkout.php (он в корне)
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            toastr.success(response.message || 'Заказ успешно оформлен!');
                            // Очищаем визуальное представление корзины
                            $('.cart__items').empty(); // Удаляем все товары
                            $('.cart__total').hide();
                            $checkoutButton.hide();
                            // Показываем сообщение "Корзина пуста"
                            var $cartEmpty = $('.cart__empty');
                            if ($cartEmpty.length === 0) {
                                $('.cart').append('<p class="cart__empty">Корзина пуста. Ваш заказ оформлен!</p>');
                            } else {
                                $cartEmpty.text('Корзина пуста. Ваш заказ оформлен!').show();
                            }
                            // Обновляем иконку корзины в шапке
                            $('.header-action__cart-1').removeClass('active');
                        } else {
                            toastr.error(response.message || 'Ошибка при оформлении заказа.');
                            $checkoutButton.prop('disabled', false).text('Оформить заказ');
                        }
                    },
                    error: function(xhr) {
                        console.error('Ошибка AJAX при оформлении заказа:', xhr.responseText);
                        toastr.error('Произошла ошибка при связи с сервером (оформление заказа).');
                        $checkoutButton.prop('disabled', false).text('Оформить заказ');
                    }
                });
            });
            // +++++ КОНЕЦ НОВОГО КОДА +++++
        });
    </script>
</body>
</html>