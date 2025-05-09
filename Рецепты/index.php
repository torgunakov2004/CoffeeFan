<?php
    session_start();
    require_once '../config/connect.php'; // Убедитесь, что путь правильный

    $recipes_query = mysqli_query($connect, "SELECT * FROM `recipes`");

    $cart_quantities = [];
    if (isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
        $query_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
        $stmt_cart = $connect->prepare($query_cart);
        if ($stmt_cart) {
            $stmt_cart->bind_param("i", $user_id);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            while ($row_cart = $result_cart->fetch_assoc()) {
                $cart_quantities[$row_cart['product_id']] = $row_cart['quantity'];
            }
            $stmt_cart->close();
        } else {
            error_log("Ошибка подготовки запроса корзины на странице рецептов: " . $connect->error);
        }
    }
    $has_items_in_cart = !empty($cart_quantities);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeeeFan - Рецепты</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>


    <header id="header-section">
        <div class="container container-header">
            <div class="header">
                <nav class="nav-main">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../index.php">Главная</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Продукты/index.php">Продукты</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link nav-main__link_selected" href="#">Рецепты</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Акции/index.php">Акции</a>
                        </li>
                    </ul>
                    <img class="header__logo" src="../img/logo.svg" alt="CoffeeeFan Logo">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../О кофе/index.php">О кофе</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Новости/index.php">Новости</a>
                        </li>
                        <li class="nav-main__item">
                            <a class="nav-main__link" href="../Контакты/index.php">Контакты</a>
                        </li>
                    </ul>
                </nav>
                <div class="header-action">
                    <a href="../local_mall.php">
                        <button class="header-action__cart-1 material-icons-outlined <?php echo $has_items_in_cart ? 'active' : ''; ?>" title="Корзина">shopping_cart</button>
                    </a>
                    <nav class="profile">
                        <nav class="account">
                            <img src="<?php echo $_SESSION['user']['avatar'] ?? '../img/icons8.png'; ?>" class="profile-avatar" alt="Аватар профиля">
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
                                        <img src="<?php echo $_SESSION['user']['avatar'] ?? '../img/default-avatar.jpg'; ?>" alt="Аватар">
                                    </div>
                                    <div class="user-details">
                                        <span class="user-name"><?= htmlspecialchars($_SESSION["user"]['first_name'] ?? ($_SESSION["user"]['name'] ?? 'Пользователь')) ?></span>
                                        <span class="user-email"><?= htmlspecialchars($_SESSION["user"]['email']) ?></span>
                                    </div>
                                </li>
                                <li class="menu-divider"></li>
                                <li><a class="menu-item" href="../profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                <li><a class="menu-item" href="../orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                <li><a class="menu-item" href="../favorites.php"><i class="icon-heart"></i>Избранное</a></li>
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

    <main>
        <div class="container">
            <h3 class="section-subtitle">Вкусные идеи для каждого дня!</h3>

            <div class="recipes-filter-bar">
                <div class="search-container recipes-search-container">
                    <label for="search-recipe-input" class="sr-only">Поиск по названию...</label>
                    <input type="text" id="search-recipe-input" placeholder="Поиск по названию..." class="search-input main-search-input">
                    <img src="../img/icon_2.png" alt="Поиск" class="search-icon">
                </div>
                <?php if (isset($_SESSION['user'])): ?>
                <div class="my-recipes-button-container">
                    <a class="btn-my-recipes" href="my_recipes.php">
                        <span class="material-icons-outlined">folder_special</span>
                        Мои рецепты
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($recipes_query && mysqli_num_rows($recipes_query) > 0): ?>
                <ul class="card-list recipes">
                    <?php while ($recipe = mysqli_fetch_assoc($recipes_query)): ?>
                        <li data-id="<?php echo $recipe['id']; ?>" class="recipe-list-item">
                            <article>
                                <section>
                                    <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="recipe-image">
                                    <div class="content">
                                        <h2 class="recipe-content-title"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                                        <div class="recipe-details">
                                            <p><strong>Ингредиенты:</strong><br><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>
                                            <p><strong>Инструкции:</strong><br><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>
                                        </div>
                                        <?php if (isset($_SESSION['user'])): ?>
                                        <button class="save-recipe-btn btn-primary" data-recipe-id="<?php echo $recipe['id']; ?>">Сохранить</button>
                                        <?php endif; ?> 
                                    </div>
                                </section>
                            </article>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-recipes-message">Рецепты не найдены.</p>
            <?php endif; ?>

        </div>
    </main>
    <?php include_once '../footer.php'; ?>    
    <script>
        $(document).ready(function() {
            $('.save-recipe-btn').on('click', function(e) {
                const button = $(this);
                // Убрал проверку tagName, т.к. теперь это всегда кнопка
                e.preventDefault();
                const recipeId = button.data('recipe-id');

                $.ajax({
                    type: 'POST',
                    url: 'save_recipe.php', // Убедитесь, что путь правильный
                    data: JSON.stringify({ recipe_id: recipeId }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Рецепт успешно сохранен!');
                        } else {
                             if (response.message === 'Не авторизован') {
                                 toastr.warning('Пожалуйста, войдите, чтобы сохранить рецепт.');
                             } else {
                                toastr.error(response.message || 'Не удалось сохранить рецепт.');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ошибка сохранения:', status, error, xhr.responseText);
                        toastr.error('Произошла ошибка при сохранении рецепта.');
                    }
                });
            });

             const searchRecipeInput = $('#search-recipe-input');
             const recipeItems = $('.recipe-list-item');
             const cardListRecipes = $('.card-list.recipes'); // Контейнер карточек

             searchRecipeInput.on('input', function() {
                 const searchTerm = searchRecipeInput.val().toLowerCase().trim();
                 let foundItems = 0;
                 recipeItems.each(function() {
                     const title = $(this).find('.recipe-content-title').text().toLowerCase();
                     if (title.includes(searchTerm)) {
                         $(this).show();
                         foundItems++;
                     } else {
                         $(this).hide();
                     }
                 });

                 const noRecipesMessageJS = cardListRecipes.parent().find('.no-recipes-message-js');
                 const initialNoRecipesMessage = cardListRecipes.parent().find('.no-recipes-message');


                 if (initialNoRecipesMessage.length > 0 && recipeItems.length === 0 && !searchTerm) {
                    // Если изначально рецептов не было (сообщение от PHP) и поиск пуст, ничего не делаем с JS сообщением
                 } else if (recipeItems.length > 0 || initialNoRecipesMessage.length === 0) {
                     if (foundItems === 0) {
                        if (noRecipesMessageJS.length === 0) {
                            cardListRecipes.after('<p class="no-recipes-message no-recipes-message-js">Рецепты по вашему запросу не найдены.</p>');
                        }
                    } else {
                        noRecipesMessageJS.remove();
                    }
                 }
             });
        });
    </script>
</body>
</html>