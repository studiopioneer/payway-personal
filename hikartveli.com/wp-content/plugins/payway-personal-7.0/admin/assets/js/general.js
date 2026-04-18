/**
 * PaywayAdminActions - класс для обработки административных действий в WordPress.
 *
 * @author Alexander Kovalev
 * @version 2.3
 * @date 17.09.2024
 */

(function ($) {
    class PaywayAdminActions {
        constructor() {
            this.ajaxUrl = window.ajaxurl || ''; // URL для AJAX-запросов
            this.initEventListeners();
        }

        /**
         * Инициализация обработчиков событий
         */
        initEventListeners() {
            // Клик по кнопке удаления сущности
            $(document).on('click', '.payway-action-delete', (e) =>
                this.handleDeleteClick(e)
            );

            // Изменение статуса сущности
            $(document).on('change', '.payway-status-select', (e) =>
                this.handleStatusChange(e)
            );

            // Клик по кнопке подтверждения отклонённого статуса
            $(document).on('click', '#payway-rejected-status-button', (e) =>
                this.handleRejectedStatusClick(e)
            );
        }

        /**
         * Обработчик клика на кнопку удаления
         * @param {Event} event
         */
        handleDeleteClick(event) {
            event.preventDefault();

            const target = $(event.target);
            const id = target.data('id');
            const entity = target.data('entity');

            if (window.confirm("Вы действительно хотите удалить проект?")) {
                this.sendRequest({
                    action: 'payway-delete-entity',
                    id,
                    entity
                }).then(() => {
                    // Удаляем строку после успешного ответа
                    target.closest('tr').remove();
                }).catch(error => {
                    console.error('Error during deletion:', error);
                    alert(`Error: ${error.message}`);
                });
            }
        }

        /**
         * Обработчик изменения статуса
         * @param {Event} event
         */
        handleStatusChange(event) {
            const target = $(event.target);
            const id = target.data('id');
            const entity = target.data('entity');
            const status = target.val();

            const data = {
                action: 'payway-update-status',
                id,
                status,
                entity
            };

            if (status === "rejected") {
                // Открываем модальное окно и сохраняем данные для дальнейшей обработки
                tb_show(
                    'Почему вы отклонили проект (заказ)?',
                    '/?TB_inline&inlineId=payway-status-modal&width=600&height=250'
                );
                const rejectedButton = $('#payway-rejected-status-button');
                if (rejectedButton.length) {
                    rejectedButton.data('entityData', data);
                }
            } else {
                // Запускаем запрос изменения статуса
                this.sendRequest(data).catch(error => {
                    console.error('Error updating status:', error);
                    alert(`Error: ${error.message}`);
                });
            }
        }

        /**
         * Обработчик клика на кнопку подтверждения отклонённого статуса
         * @param {Event} event
         */
        handleRejectedStatusClick(event) {
            event.preventDefault();

            const target = $(event.target);
            const data = target.data('entityData') || {};
            const commentInput = $('#payway-rejected-status-comment');

            if (commentInput.length) {
                data.review_comments = commentInput.val();
            }

            tb_remove(); // Закрытие модального окна

            this.sendRequest(data).catch(error => {
                console.error('Error during rejected status update:', error);
                alert(`Error: ${error.message}`);
            });
        }

        /**
         * Функция для выполнения AJAX-запросов
         * @param {Object} data - Данные для отправки
         * @returns {Promise} - Промис с результатом запроса
         */
        sendRequest(data) {
            return $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: data
            }).then((response) => {
                console.log('Response:', response);
                return response;
            }).catch((xhr, ajaxOptions, thrownError) => {
                console.error('AJAX request error:', xhr.status, xhr.responseText, thrownError);
                throw new Error(xhr.responseText || 'Unknown error occurred');
            });
        }
    }

    $(document).ready(function () {
        new PaywayAdminActions();
    });
})(jQuery);