/**
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 17.09.2024, CreativeMotion
 * @version 1.0
 */


(function ($) {
    'use strict';

    $(document).ready(function () {

        // Загрузка доступных месяцев для каждого пользователя
        $('.payway-month-selector').each(function () {
            var $select = $(this);
            var userId = $select.data('user-id');
            var $balanceValue = $select.closest('.payway-balance-container').find('.payway-balance-value');

            $.ajax({
                url: paywayData.apiUrl + 'stats/available-months',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': paywayData.nonce
                },
                data: {
                    user_id: userId
                },
                success: function (months) {
                    if (months.length > 0) {
                        // Добавляем месяцы в выпадающий список
                        months.forEach(function (month) {
                            var date = new Date(month + '-01');
                            var monthName = date.toLocaleString('ru-RU', {month: 'long', year: 'numeric'});
                            $select.append('<option value="' + month + '">' + monthName + '</option>');
                        });

                        // Устанавливаем последний месяц по умолчанию
                        var lastMonth = months[0]; // Первый месяц в списке (последний по дате)
                        $select.val(lastMonth);

                        // Загружаем баланс для выбранного месяца по умолчанию
                        $.ajax({
                            url: paywayData.apiUrl + 'stats/monthly-balance',
                            method: 'GET',
                            headers: {
                                'X-WP-Nonce': paywayData.nonce
                            },
                            data: {
                                month: lastMonth,
                                user_id: userId
                            },
                            success: function (response) {
                                $balanceValue.text(response.balance.toFixed(2));
                            },
                            error: function (xhr, status, error) {
                                console.error('Ошибка при загрузке баланса:', error);
                            }
                        });
                    } else {
                        // Если месяцы отсутствуют, отображаем сообщение
                        $select.append('<option value="">Нет данных</option>');
                        $balanceValue.text('0.00');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Ошибка при загрузке месяцев:', error);
                    $select.append('<option value="">Ошибка загрузки</option>');
                    $balanceValue.text('0.00');
                }
            });
        });

        // Обработка выбора месяца
        $('.payway-month-selector').change(function () {
            var $select = $(this);
            var userId = $select.data('user-id');
            var month = $select.val();
            var $balanceValue = $select.closest('.payway-balance-container').find('.payway-balance-value');

            if (!month) return;

            $.ajax({
                url: paywayData.apiUrl + 'stats/monthly-balance',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': paywayData.nonce
                },
                data: {
                    month: month,
                    user_id: userId
                },
                success: function (response) {
                    $balanceValue.text(response.balance.toFixed(2));
                },
                error: function (xhr, status, error) {
                    console.error('Ошибка при загрузке баланса:', error);
                }
            });
        });

        $('.payway-user-balance-input, .payway-user-withdrawal-balance-input').change(function () {
            var balance = $(this).val(),
                defaultBalance = $(this).data('default-balace'),
                entity = $(this).hasClass('payway-user-balance-input') ? 'base' : 'withdrawal';

            if ('' == balance || balance < 0) {
                balance = defaultBalance ? defaultBalance : 0;
                $(this).val(balance);
            }

            $.ajax(ajaxurl, {
                type: 'post',
                dataType: 'json',
                data: {
                    action: 'payway-update-user-balance',
                    balance: $(this).val(),
                    user_id: $(this).data('user-id'),
                    entity: entity
                },
                success: function (response) {
                    console.log(response);
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    $(this).val($(this).data('default-value'));
                    console.log(xhr.status, xhr.responseText, thrownError);

                    alert('Error: [' + thrownError + '] Status: [' + xhr.status + '] Error massage: [' + xhr.responseText + ']');
                }
            });
        });
    });


})(jQuery);
