<?php
session_start();
require_once '../config/connect.php'; 

$news = mysqli_query($connect, "SELECT * FROM `news` ORDER BY `date` DESC");

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffeee shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<body>
    <?php
        $current_page_is_faq = true; 
        include_once '../header_footer_elements/header.php'; 
    ?>
    <section id="section-news" class="section-main">
        <div class="container">
            <h3 class="section-subtitle">Наши актуальные новости</h3>
            <div class="news-wrap">
                <?php while ($news_item = mysqli_fetch_assoc($news)): ?>
                    <div class="news-card">
                    <img src="../<?php echo htmlspecialchars(ltrim($news_item['image'], '/')); ?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-card__image">
                        <div class="news-card__content">
                            <time class="news__date"><?php echo date('d.m.Y H:i', strtotime($news_item['date'])); ?></time> 
                            <h3 class="news-card__title"><?php echo htmlspecialchars($news_item['title']); ?></h3>
                            <p class="news-card__text"><?php echo htmlspecialchars($news_item['content_preview']); ?></p>
                            <a class="news-card__link" href="news_detail.php?id=<?php echo $news_item['id']; ?>">Читать далее <span class="material-icons-outlined">arrow_forward</span></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php include_once '../footer.php'; ?>
</body>
</html>