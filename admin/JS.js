$(document).ready(function() {
        // Обработчик события для чекбоксов
        $('input[type="checkbox"]').change(function() {
            const productId = $(this).attr('name').match(/\d+/)[0]; // Получаем ID продукта
            const isPopular = $(this).is(':checked') ? 1 : 0; // Определяем статус популярности

            // Отправка AJAX-запроса
            $.ajax({
                url: 'update_popularity.php', // Путь к вашему PHP-скрипту
                type: 'POST',
                data: {
                    id: productId,
                    is_popular: isPopular
                },
                success: function(response) {
                    console.log(response); // Выводим ответ для отладки
                },
                error: function(xhr, status, error) {
                    console.error(error); // Выводим ошибку для отладки
                }
            });

            // Показываем кнопку "Сохранить"
            $('#save-popularity').show();
        });

        // Обработчик события для кнопки "Сохранить"
        $('#save-popularity').click(function() {
            // Здесь можно добавить логику для сохранения всех изменений
            alert('Изменения сохранены!');
            $(this).hide(); // Скрываем кнопку после сохранения
        });
    });