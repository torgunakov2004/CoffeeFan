<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['admin']) && !(isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'])) {
    header('Location: admin_login.php');
    exit();
}
$admin_name = $_SESSION['admin']['name'] ?? ($_SESSION['user']['first_name'] ?? 'Администратор');
$base_url_prefix_for_links = '';

$add_promo_data = [
    'title' => $_SESSION['old_add_form_data']['title'] ?? '',
    'description' => $_SESSION['old_add_form_data']['description'] ?? '',
    'conditions' => $_SESSION['old_add_form_data']['conditions'] ?? '',
    'link' => $_SESSION['old_add_form_data']['link'] ?? '',
    'is_active' => isset($_SESSION['old_add_form_data']['is_active']) ? 1 : 0
];
$add_errors_form = $_SESSION['form_add_errors'] ?? [];
unset($_SESSION['form_add_errors'], $_SESSION['old_add_form_data']);

$stmt_promos = $connect->prepare("SELECT id, title, image, conditions, is_active, created_at FROM promotions ORDER BY created_at DESC");
$promotions_arr = [];
if ($stmt_promos) {
    $stmt_promos->execute();
    $result_promos = $stmt_promos->get_result();
    while ($row = $result_promos->fetch_assoc()) {
        $promotions_arr[] = $row;
    }
    $stmt_promos->close();
} else {
    error_log("Promotions List: Failed to prepare promotions list query: " . $connect->error);
}

$message = '';
$message_type = 'info';
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Акциями - CoffeeFan</title>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <img class="header__logo" src="<?php echo $base_url_prefix_for_links; ?>../img/logo.svg" alt="CoffeeFan Logo">
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Главная</a></li>
                    <li><a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">Продукты</a></li>
                    <li><a href="manage_menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_menu.php' ? 'active' : ''; ?>">Меню</a></li>
                    <li><a href="manage_recipes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipes.php' ? 'active' : ''; ?>">Рецепты</a></li>
                    <li><a href="promotions_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'promotions_list.php' ? 'active' : ''; ?>">Акции</a></li>
                    <li><a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">Отзывы</a></li>
                    <li><a href="manage_news.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_news.php' ? 'active' : ''; ?>">Новости</a></li>
                    <li><a href="manage_advertisements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_advertisements.php' ? 'active' : ''; ?>">Реклама</a></li>
                    <li><a href="manage_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">Заказы</a></li>
                    <li class="site-link"><a href="../index.php">На сайт</a></li> 
                    <li class="logout-link"><a href="logout.php">Разлогиниться</a></li> 
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo nl2br(htmlspecialchars($message)); ?></div>
        <?php endif; ?>

        <button type="button" class="add-new-btn toggle-add-form-btn" id="toggleAddPromotionFormBtn">
            <span class="material-icons-outlined">add_circle_outline</span>Добавить новую акцию
        </button>

        <div class="admin-content form-container" id="addPromotionFormContainer" style="display: <?php echo !empty($add_errors_form) ? 'block' : 'none'; ?>;">
            <div id="add-form-anchor"></div>
            <h2>Добавить новую акцию</h2>
            <?php if (!empty($add_errors_form)): ?>
                <div class="message error">
                    <strong>Обнаружены ошибки при добавлении:</strong><br>
                    <?php foreach ($add_errors_form as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="process_promotion.php" method="post" enctype="multipart/form-data" class="admin-form" id="addPromotionForm">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="title_add_promo">Заголовок акции *</label>
                    <input type="text" id="title_add_promo" name="title" value="<?php echo htmlspecialchars($add_promo_data['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description_add_promo">Полное описание *</label>
                    <textarea id="description_add_promo" name="description" rows="5" required><?php echo htmlspecialchars($add_promo_data['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="conditions_add_promo">Краткие условия (для карточки, необязательно)</label>
                    <input type="text" id="conditions_add_promo" name="conditions" value="<?php echo htmlspecialchars($add_promo_data['conditions']); ?>" placeholder="Например: Скидка 15% до конца недели">
                </div>
                <div class="form-group">
                    <label for="link_add_promo">Ссылка (URL, необязательно)</label>
                    <input type="url" id="link_add_promo" name="link" value="<?php echo htmlspecialchars($add_promo_data['link']); ?>" placeholder="https://example.com/promo-page">
                </div>
                <div class="form-group">
                    <label for="image_add_form_promo">Изображение *</label>
                    <input type="file" id="image_add_form_promo" name="image" accept="image/jpeg, image/png, image/gif, image/webp" required>
                </div>
                 <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($add_promo_data['is_active'] == 1) ? 'checked' : ''; ?>>
                        Акция активна (видна на сайте)
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">Добавить акцию</button>
                    <button type="button" class="btn-cancel" id="cancelAddPromotionBtn">Отмена</button>
                </div>
            </form>
        </div>

        <div class="admin-content">
            <div class="table-controls">
                <h2>Список акций</h2>
                <div class="search-input-container">
                     <input type="text" id="promotionsSearchInput" placeholder="Поиск по заголовку, условиям...">
                </div>
            </div>
            <table class="admin-table" id="promotionsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id" data-sort-type="number">ID</th>
                        <th>Изображение</th>
                        <th class="sortable" data-sort="title">Заголовок</th>
                        <th class="sortable" data-sort="conditions">Условия</th>
                        <th class="sortable" data-sort="is_active" data-sort-type="boolean">Активна</th>
                        <th class="sortable" data-sort="created_at" data-sort-type="date">Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="promotionsTableBody">
                    <?php if (!empty($promotions_arr)): ?>
                        <?php foreach ($promotions_arr as $promo): ?>
                            <tr>
                                <td><?php echo $promo['id']; ?></td>
                                <td>
                                    <?php if (!empty($promo['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($promo['image'],'/')); ?>" alt="Превью" class="thumbnail">
                                    <?php else: ?>
                                        Нет фото
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($promo['title']); ?></td>
                                <td><?php echo htmlspecialchars($promo['conditions'] ?? 'N/A'); ?></td>
                                <td>
                                     <form action="process_promotion.php" method="post" style="display:inline;" class="toggle-status-form">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $promo['is_active']; ?>">
                                        <button type="submit" class="status-toggle-btn <?php echo $promo['is_active'] ? 'active' : 'inactive'; ?>" 
                                                title="<?php echo $promo['is_active'] ? 'Деактивировать' : 'Активировать'; ?>">
                                            <?php echo $promo['is_active'] ? 'Да' : 'Нет'; ?>
                                        </button>
                                     </form>
                                </td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($promo['created_at']))); ?></td>
                                <td class="actions">
                                    <a href="promotion_form.php?id=<?php echo $promo['id']; ?>" class="edit-btn">Редакт.</a>
                                    <form action="process_promotion.php" method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту акцию? Это действие необратимо.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <button type="submit" class="delete-btn">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noPromotionsRow">
                            <td colspan="7">Акций пока нет.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p id="promotionsTableEmptyMessage" style="display:none; text-align:center; padding: 20px; color: #777;">Акции по вашему запросу не найдены.</p>
        </div>
    </div>
    <style>
        .status-toggle-btn { padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; color: white; min-width: 60px; text-align: center; }
        .status-toggle-btn.active { background-color: #28a745; }
        .status-toggle-btn.inactive { background-color: #dc3545; }
    </style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const $addPromotionFormContainer = $('#addPromotionFormContainer');
    const $toggleAddPromotionFormBtn = $('#toggleAddPromotionFormBtn');
    const $cancelAddPromotionBtn = $('#cancelAddPromotionBtn');

    if ($addPromotionFormContainer.find('.message.error').length > 0) {
        $addPromotionFormContainer.show();
        $toggleAddPromotionFormBtn.find('.material-icons-outlined').text('remove_circle_outline');
        $toggleAddPromotionFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Скрыть форму');
    }

    $toggleAddPromotionFormBtn.on('click', function() {
        $addPromotionFormContainer.slideToggle(300);
        $(this).find('.material-icons-outlined').text(
            $addPromotionFormContainer.is(':visible') ? 'remove_circle_outline' : 'add_circle_outline'
        );
        $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).first().replaceWith(
            $addPromotionFormContainer.is(':visible') ? 'Скрыть форму' : 'Добавить новую акцию'
        );
         if (!$addPromotionFormContainer.is(':visible')) {
            $('#addPromotionForm')[0].reset();
            $addPromotionFormContainer.find('.message.error').remove();
        }
    });
    $cancelAddPromotionBtn.on('click', function() {
        $addPromotionFormContainer.slideUp(300);
        $toggleAddPromotionFormBtn.find('.material-icons-outlined').text('add_circle_outline');
        $toggleAddPromotionFormBtn.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith('Добавить новую акцию');
        $('#addPromotionForm')[0].reset();
        $addPromotionFormContainer.find('.message.error').remove();
    });

    const $promotionsTable = $('#promotionsTable');
    const $promotionsTableBody = $('#promotionsTableBody');
    const $promotionsSearchInput = $('#promotionsSearchInput');
    const $noPromotionsRowPHP = $('#noPromotionsRow');
    const $promotionsEmptyMessageJS = $('#promotionsTableEmptyMessage');

    $promotionsTable.find('th.sortable').on('click', function() {
        const $th = $(this);
        const column = $th.data('sort');
        if (!column) return;
        const type = $th.data('sort-type') || 'string';
        let currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
        $promotionsTable.find('th.sortable').removeClass('asc desc');
        $th.addClass(currentOrder);
        const rows = $promotionsTableBody.find('tr:not(#noPromotionsRow)').get();
        
        const colIndex = $th.index();

        rows.sort(function(a, b) {
            let valA, valB;
            
            if (type === 'boolean' && column === 'is_active') {
                 valA = $(a).children('td').eq(colIndex).find('button').text().trim().toLowerCase() === 'да';
                 valB = $(b).children('td').eq(colIndex).find('button').text().trim().toLowerCase() === 'да';
            } else {
                valA = $(a).children('td').eq(colIndex).text().trim();
                valB = $(b).children('td').eq(colIndex).text().trim();
            }

            if (type === 'number') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else if (type === 'date') {
                valA = new Date(valA.split('.').reverse().join('-').split(' ')[0]).getTime() || 0;
                valB = new Date(valB.split('.').reverse().join('-').split(' ')[0]).getTime() || 0;
            } else {
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
            }
            if (valA < valB) return currentOrder === 'asc' ? -1 : 1;
            if (valA > valB) return currentOrder === 'asc' ? 1 : -1;
            return 0;
        });
        $.each(rows, function(index, row) {
            $promotionsTableBody.append(row);
        });
    });

    $promotionsSearchInput.on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleRows = 0;
        $promotionsTableBody.find('tr:not(#noPromotionsRow)').each(function() {
            const $row = $(this);
            let rowText = '';
            $row.children('td').each(function(index) {
                const th = $promotionsTable.find('thead th').eq(index);
                if (th.data('sort') === 'title' || th.data('sort') === 'conditions') {
                    rowText += $(this).text().toLowerCase() + ' ';
                }
            });
            if (rowText.includes(searchTerm)) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        if ($noPromotionsRowPHP.length > 0 && $promotionsTableBody.find('tr:not(#noPromotionsRow):visible').length === 0 && searchTerm === "") {
            $noPromotionsRowPHP.show();
            $promotionsEmptyMessageJS.hide();
        } else if (visibleRows === 0 && $promotionsTableBody.find('tr:not(#noPromotionsRow)').length > 0) {
            $noPromotionsRowPHP.hide();
            $promotionsEmptyMessageJS.show();
        } else {
            $noPromotionsRowPHP.hide();
            $promotionsEmptyMessageJS.hide();
        }
    });
    if ($promotionsTableBody.find('tr:not(#noPromotionsRow)').length === 0 && $noPromotionsRowPHP.is(':visible')) {
    } else {
         $promotionsSearchInput.trigger('keyup');
    }
});
</script>
</body>
</html>