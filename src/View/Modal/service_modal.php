<!-- Модальное окно для отправки в сервис -->
<style>
    .service-items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        background-color: white;
        /* Белый фон таблицы */
    }

    .service-items-table th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        background-color: white;
        /* Белый фон заголовков */
        color: black;
        /* Черный текст */
        border-bottom: 2px solid black;
        /* Черная разделительная линия */
    }

    .service-items-table td {
        padding: 12px 15px;
        background-color: white;
        /* Белый фон строк */
        /*border: 1px solid black;*/
        /* Черный контур ячеек */
        /* color: black;*/
        /* Черный текст */
        vertical-align: top;
        /* Выравнивание по верхнему краю */
    }

    /* Убираем дублирование границ между ячейками */
    .service-items-table tr td+td {
        border-left: none;
    }

    .service-items-table tr td {
        border-top: none;
        border-bottom: none;
    }

    /* Первая и последняя ячейка в строке */
    .service-items-table tr td:first-child {
        border-left: 1px solid black;
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }

    .service-items-table tr td:last-child {
        border-right: 1px solid black;
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
    }

    /* Особые стили для столбца с причиной ремонта */
    .service-items-table td:nth-child(3) {
        min-width: 300px;
        /* Минимальная ширина столбца */
        width: 50%;
        /* Стартовая ширина */
        max-width: 600px;
        /* Максимальная ширина */
    }

    .repair-reason-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        /* Серая граница */
        border-radius: 4px;
        background-color: white;
        /* Белый фон */
        color: black;
        /* Черный текст */
        box-sizing: border-box;
        min-height: 40px;
        /* Начальная высота */
        resize: both;
        /* Разрешаем изменять размер */
        white-space: pre-wrap;
        /* Сохраняем пробелы и переносы */
        word-wrap: break-word;
        /* Перенос длинных слов */
        overflow: auto;
        /* Добавляем скролл при необходимости */
    }

    .repair-reason-input.error {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
</style>
<div class="modal fade" id="serviceModal" data-status="0" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title">Отправить в сервис</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="service-items-table">
                    <thead>
                        <tr>
                            <th>Ид.</th>
                            <th>Наименование</th>
                            <th id="colReason">Причина ремонта</th>
                        </tr>
                    </thead>
                    <tbody id="selectedServiceItemsContainer">
                        <!-- Сюда будут вставлены строки -->
                    </tbody>
                </table>

            </div>

            <div class="mt-3 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitService">Передать</button>
            </div>
        </div>
    </div>
</div>