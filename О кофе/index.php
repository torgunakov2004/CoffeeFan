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

// Обработка отправки отзыва (ОСТАЕТСЯ БЕЗ ИЗМЕНЕНИЙ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review_form_token'])) { // Используем новое имя для токена формы
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Ошибка безопасности: неверный CSRF токен.";
        header('Location: index.php');
        exit();
    }

    $name = '';
    $user_id_for_review = null;

    if (isset($_SESSION['user'])) {
        $name = $_SESSION['user']['first_name'] ?? $_SESSION['user']['name']; // Приоритет first_name
        $user_id_for_review = $_SESSION['user']['id'];
    } else {
        $name = htmlspecialchars(trim($_POST['name']));
    }
    
    $review_text = htmlspecialchars(trim($_POST['review'])); // Переименовал переменную во избежание конфликта
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (!empty($name) && !empty($review_text)) {
        $stmt = $connect->prepare("INSERT INTO `reviews` (`name`, `review`, `rating`, `status`, `user_id`) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param("ssii", $name, $review_text, $rating, $user_id_for_review);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Отзыв успешно отправлен на модерацию!";
        } else {
            $_SESSION['error'] = "Ошибка при отправке отзыва: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Пожалуйста, заполните все поля.";
    }

    unset($_SESSION['csrf_token']); // Удаляем токен после использования
    header('Location: index.php#reviews-section'); // Перенаправляем к секции отзывов
    exit();
}

// Генерация CSRF токена для формы отзыва
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Получение всех одобренных отзывов
$reviews_query_result = mysqli_query($connect, "SELECT r.*, u.avatar as user_avatar_path FROM `reviews` r LEFT JOIN `user` u ON r.user_id = u.id WHERE r.`status` = 'approved' ORDER BY r.`id` DESC");
if (!$reviews_query_result) {
    die("Ошибка выполнения запроса отзывов: " . mysqli_error($connect));
}

    // Получаем количество товаров в корзине для текущего пользователя
    $cart_quantities = [];
    if (isset($_SESSION['user']['id'])) { // Проверяем наличие user ID
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
            error_log("Ошибка подготовки запроса корзины на странице О кофе: " . $connect->error);
        }
    }

    // Проверка наличия товаров в корзине
    $has_items_in_cart = !empty($cart_quantities);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoffeFan - О кофе</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="/menu_style.css"> Убрал, если это для общего меню, оно уже в style.css -->
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
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
            <section id="reviews-section" class="section-main"> <!-- Добавил section-main для консистентности -->
                <h3 class="section-subtitle">Отзывы наших клиентов</h3>
                <?php if (isset($_SESSION['message'])): ?>
                    <script>
                        $(document).ready(function() {
                            toastr.success('<?php echo $_SESSION['message']; ?>');
                        });
                    </script>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                     <script>
                        $(document).ready(function() {
                            toastr.error('<?php echo $_SESSION['error']; ?>');
                        });
                    </script>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Форма отправки отзыва -->
                <form id="review-form" class="review-form" method="POST" action="index.php#reviews-section">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <?php if (!isset($_SESSION['user'])): // Показываем поле имени, если пользователь не авторизован ?>
                        <div class="form-group-review"> <?php // Добавим класс для возможной стилизации ?>
                            <label for="reviewer_name">Ваше имя:</label>
                            <input type="text" id="reviewer_name" name="name" placeholder="Введите ваше имя" required>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group-review"> <?php // Обернем и textarea для единообразия ?>
                        <label for="review_text_area">Ваш отзыв:</label>
                        <textarea id="review_text_area" name="review" placeholder="Ваш отзыв (минимум 10 символов)" required minlength="10"></textarea>
                    </div>
                    
                    <div class="rating">
                        <p>Оцените наш сервис (необязательно):</p>
                        <div class="stars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                                <label for="star<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <button type="submit" name="submit_review_form_token" class="btn-primary">Отправить отзыв</button>
                </form>

                <!-- Секция отображения отзывов -->
                <div class="testimonial-wrap-column"> <?php // Новый класс для вертикального расположения ?>
                    <?php if ($reviews_query_result && mysqli_num_rows($reviews_query_result) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($reviews_query_result)): ?>
                            <div class="testimonial">
                                <div class="testimonial-data">
                                    <?php
                                    $display_initials = true; // Флаг, что по умолчанию показываем инициалы
                                    $avatar_to_display_src = ''; // Путь к аватару для тега img

                                    // Проверяем, есть ли информация об аватаре пользователя для этого отзыва
                                    if (!empty($review['user_avatar_path'])) {
                                        // Путь из БД (user_avatar_path) хранится от корня сайта (например, "uploads/avatars/file.jpg")
                                        // Формируем путь к файлу относительно текущего скрипта (О кофе/index.php)
                                        $path_to_user_avatar_from_script = '../' . ltrim($review['user_avatar_path'], '/');

                                        if (file_exists($path_to_user_avatar_from_script)) {
                                            $avatar_to_display_src = htmlspecialchars($path_to_user_avatar_from_script);
                                            $display_initials = false; // Пользовательский аватар найден, инициалы не нужны
                                        }
                                    }

                                    // Если нужно показать инициалы (пользовательский аватар не найден или отзыв от гостя)
                                    if ($display_initials) {
                                        // Убедимся, что mbstring доступна, иначе используем стандартные функции
                                        $initial = '';
                                        if (!empty($review['name'])) {
                                            if (function_exists('mb_strtoupper') && function_exists('mb_substr')) {
                                                $initial = htmlspecialchars(mb_strtoupper(mb_substr($review['name'], 0, 1, 'UTF-8')));
                                            } elseif (function_exists('strtoupper') && function_exists('substr')) {
                                                $initial = htmlspecialchars(strtoupper(substr($review['name'], 0, 1)));
                                            }
                                        }
                                        echo '<div class="testimonial__img_initials">' . $initial . '</div>';
                                    } else {
                                        // Показываем аватар пользователя
                                        echo '<img class="testimonial__img" src="' . $avatar_to_display_src . '" alt="Аватар ' . htmlspecialchars($review['name']) . '">';
                                    }
                                    ?>

                                    <div>
                                        <h3 class="testimonial__name"><?php echo htmlspecialchars($review['name']); ?></h3>
                                        
                                        <?php // НОВОЕ: Добавляем дату и время отзыва ?>
                                        <?php if (!empty($review['created_at'])): ?>
                                            <p class="testimonial__date"><?php echo display_date_in_user_timezone($review['created_at']); ?></p>
                                        <?php endif; ?>

                                        <p class="testimonial__text section__text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                        <?php if (isset($review['rating']) && $review['rating'] > 0): ?>
                                            <div class="testimonial__rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo ($i <= $review['rating']) ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-reviews-message">Отзывов пока нет. Будьте первым!</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <?php include_once '../footer.php'; ?>
    <script>
        // Данные для диаграмм (ОСТАЮТСЯ БЕЗ ИЗМЕНЕНИЙ)
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
                    data: [70, 60, 30, 50], // Примерные данные
                    backgroundColor: ['#6a994e', '#ffb703', '#fb8500', '#d00000'],
                }]
            }
        ];

        // Создание диаграмм (ОСТАЮТСЯ БЕЗ ИЗМЕНЕНИЙ)
        chartsData.forEach((data, index) => {
            const ctx = document.getElementById(`chart-${index + 1}`).getContext('2d');
            const chartType = index === 3 ? 'bar' : 'doughnut';
            new Chart(ctx, {
                type: chartType,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    size: 14, // Уменьшил для лучшего вида на мобильных
                                    family: 'Urbanist',
                                },
                                color: '#FFFFFF'
                            }
                        }
                    },
                }
            });
        });

        // Настройка Toastr (ОСТАЕТСЯ БЕЗ ИЗМЕНЕНИЙ)
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
        
        // AJAX для формы отзыва не нужен, т.к. форма теперь отправляется стандартно.
        // PHP обработчик вверху файла index.php сам установит сессионные сообщения для toastr.
    </script>
</body>
</html>