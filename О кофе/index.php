<?php
session_start();
require_once '../config/connect.php'; // Подключение к базе данных

// Проверка подключения к базе данных
if (!$connect) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Получение всех тестов
$tests = mysqli_query($connect, "SELECT * FROM `tests`");
if (!$tests) {
    die("Ошибка выполнения запроса: " . mysqli_error($connect));
}

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $name = isset($_SESSION['user']) ? $_SESSION['user']['name'] : htmlspecialchars(trim($_POST['name']));
    $review = htmlspecialchars(trim($_POST['review']));
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (!empty($name) && !empty($review)) {
        $stmt = $connect->prepare("INSERT INTO `reviews` (`name`, `review`, `rating`, `status`) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("ssi", $name, $review, $rating);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Отзыв успешно отправлен на модерацию!";
        } else {
            $_SESSION['error'] = "Ошибка при отправке отзыва: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Пожалуйста, заполните все поля.";
    }

    header('Location: index.php');
    exit();
}

// Получение всех одобренных отзывов
$reviews_query_sql = "
    SELECT r.*, u.avatar as user_true_avatar 
    FROM `reviews` r
    LEFT JOIN `user` u ON r.user_id = u.id
    WHERE r.status = 'approved' 
    ORDER BY r.id DESC
";
$reviews = mysqli_query($connect, $reviews_query_sql);
if (!$reviews) {
    die("Ошибка выполнения запроса отзывов: " . mysqli_error($connect));
}

    // Получаем количество товаров в корзине для текущего пользователя
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
    }

    // Проверка наличия товаров в корзине
    $has_items_in_cart = !empty($cart_quantities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeFan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/menu_style.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a class="nav-main__link" href="../Рецепты/index.php">Рецепты</a>
                        </li>

                        <li class="nav-main__item">
                        <a class="nav-main__link" href="../Акции/index.php">Акции</a>
                        </li>
                    </ul>
                    <img class="header__logo" src="../img/logo.svg" alt="#">
                    <ul class="nav-main__list">
                        <li class="nav-main__item">
                            <a class="nav-main__link nav-main__link_selected" href="../О кофе/index.php">О кофе</a>
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
                    <button class="header-action__cart-1 material-icons-outlined <?php echo $has_items_in_cart ? 'active' : ''; ?>">shopping_cart</button>
                </a>
                <nav class="profile">
                    <nav class="account">
                        <img src="<?php echo $_SESSION['user']['avatar'] ?? '../img/icons8.png'; ?>" class="profile-avatar" alt="Аватар профиля">
                    </nav>
                        <?php if (!$_SESSION): ?>
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
                                <li><a class="menu-item" href="profile.php"><i class="icon-user"></i>Мой профиль</a></li>
                                <li><a class="menu-item" href="orders.php"><i class="icon-orders"></i>Мои заказы</a></li>
                                <li><a class="menu-item" href="favorites.php"><i class="icon-heart"></i>Избранное</a></li>
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
            <h3 class="section-subtitle">Качество, проверенное временем</h3>
            <section class="tests">
                <!-- Тест 1 -->
                <div class="test">
                    <h2 class="test-title">Полезность кофе</h2>
                    <p class="test-description">Кофе содержит множество антиоксидантов и полезных веществ, которые могут улучшить здоровье.</p>
                    <div class="chart-container">
                        <canvas id="chart-1" class="chart"></canvas>
                    </div>
                </div>

                <!-- Тест 2 -->
                <div class="test">
                    <h2 class="test-title">Что добавляют в пакетики кофе?</h2>
                    <p class="test-description">Многие производители добавляют в кофе различные добавки, такие как ароматизаторы, сахар и консерванты.</p>
                    <div class="chart-container">
                        <canvas id="chart-2" class="chart"></canvas>
                    </div>                
                </div>

                <!-- Тест 3 -->
                <div class="test">
                    <h2 class="test-title">Натуральный кофе vs. растворимый</h2>
                    <p class="test-description">Натуральный кофе, приготовленный из свежемолотых зерен, содержит больше антиоксидантов.</p>
                    <div class="chart-container">
                        <canvas id="chart-3" class="chart"></canvas>
                    </div>                
                </div>

                <!-- Тест 4 -->
                <div class="test">
                    <h2 class="test-title">Кофе и его влияние на организм</h2>
                    <p class="test-description">Кофе может улучшить физическую работоспособность, повышая уровень адреналина в крови.</p>
                    <div class="chart-container">
                        <canvas id="chart-4" class="chart"></canvas>
                    </div>                
                </div>
            </section>

            <!-- Отзывы -->
            <h3 class="section-subtitle">Отзывы</h3>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Форма отправки отзыва -->
            <form id="review-form" class="review-form">
                <?php if (!isset($_SESSION['user'])): ?>
                    <input type="text" name="name" placeholder="Ваше имя" required>
                <?php else: ?>
                    <input type="hidden" name="name" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>">
                <?php endif; ?>
                
                <textarea name="review" placeholder="Ваш отзыв" required></textarea>
                
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="rating">
                        <p>Оцените продукт:</p>
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                                <label for="star<?= $i ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">Отправить отзыв</button>
            </form>

            <!-- Секция отзывов -->
            <section id="testimonial-section" class="section-main testimonial-carousel"> <?php // Используем классы со страницы Продукты ?>
                <div class="testimonial-wrap"> <?php // Используем классы со страницы Продукты ?>
                    <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                            <div class="testimonial">
                                <div class="testimonial-data">
                                    <?php
                                        $avatar_to_display = $review['user_true_avatar'] ?? '../img/testimonial-1.jpg'; // Аватар пользователя или дефолтный
                                        // Если $review['user_true_avatar'] это NULL или пустая строка, будет использован дефолтный
                                        if (empty($review['user_true_avatar'])) {
                                            $avatar_to_display = '../img/testimonial-1.jpg'; // Явное указание дефолтного, если из БД пусто
                                        }
                                    ?>
                                    <img class="testimonial__img" src="<?php echo htmlspecialchars($avatar_to_display); ?>" alt="Аватар пользователя <?php echo htmlspecialchars($review['name']); ?>">
                                    <div>
                                        <h3 class="testimonial__name"><?php echo htmlspecialchars($review['name']); ?></h3>
                                        <p class="testimonial__text section__text"><?php echo htmlspecialchars($review['review']); ?></p>
                                        <?php if (isset($review['rating']) && $review['rating'] > 0): ?>
                                            <div class="review-rating-testimonial">
                                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                                    <span class="star-testimonial <?= ($s <= $review['rating']) ? 'filled' : '' ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-products-message">Отзывов пока нет.</p> <?php // или другой класс, если нужно ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer id="footer-section">
        <div class="container">
            <div class="footer">
  
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                <p class="footer-copyright__text">e-Wiwonti © 2025. Все права защищены</p>
            </div>
        </div>
    </footer>

    <script>
        // Данные для диаграмм
        const chartsData = [
            {
                labels: ['Снижение риска диабета', 'Улучшение памяти', 'Снижение риска инсульта', 'Антиоксиданты'],
                datasets: [{
                    label: 'Полезные свойства кофе',
                    data: [30, 25, 20, 25],
                    backgroundColor: ['#C99E71', '#bd864b', '#f0a500', '#ffcc00'],
                }]
            },
            {
                labels: ['Ароматизаторы', 'Сахар', 'Консерванты', 'Натуральный кофе'],
                datasets: [{
                    label: 'Добавки в кофе',
                    data: [20, 30, 25, 25],
                    backgroundColor: ['#C99E71', '#bd864b', '#f0a500', '#ffcc00'],
                }]
            },
            {
                labels: ['Натуральный кофе', 'Растворимый кофе'],
                datasets: [{
                    label: 'Сравнение кофе',
                    data: [70, 30],
                    backgroundColor: ['#C99E71', '#bd864b'],
                }]
            },
            {
                labels: ['Уровень энергии', 'Концентрация', 'Качество сна', 'Уровень тревожности'],
                datasets: [{
                    label: 'Влияние кофе на организм',
                    data: [70, 60, 30, 50],
                    backgroundColor: ['#6a994e', '#ffb703', '#fb8500', '#d00000'],
                }]
            }
        ];

        // Создание диаграмм
        chartsData.forEach((data, index) => {
            const ctx = document.getElementById(`chart-${index + 1}`).getContext('2d');
            const chartType = index === 3 ? 'bar' : 'doughnut'; // Для последней диаграммы используем столбчатую диаграмму
            new Chart(ctx, {
                type: chartType,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Отключаем автоматическое поддержание пропорций
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    size: 20,
                                    family: 'Urbanist',
                                },
                                color: '#FFFFFF'
                            }
                        }
                    },
                }
            });
        });

        $(document).ready(function() {
            // Обработка отправки отзыва
            $('#review-form').on('submit', function(e) {
                e.preventDefault(); // Предотвращаем стандартную отправку формы
                
                $.ajax({
                    type: 'POST',
                    url: 'submit_review.php', // Файл-обработчик
                    data: $(this).serialize(), // Сериализуем данные формы
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Показываем уведомление об успехе
                            toastr.success(response.message);
                            
                            // Очищаем форму
                            $('#review-form')[0].reset();
                            
                            // Если нужно обновить список отзывов
                            // loadReviews();
                        } else {
                            // Показываем ошибку
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Произошла ошибка при отправке отзыва');
                        console.error(error);
                    }
                });
            });
        });
    </script>
</body>
</html>