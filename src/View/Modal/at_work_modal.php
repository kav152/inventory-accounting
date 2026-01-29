<?php if ($brigadesToItemsCount > 0): ?>
    <style>
        .brigade-content table {
            width: 100%;
            border-collapse: separate;
            /* Раздельные границы */
            border-spacing: 0 8px;
            /* Вертикальные отступы между строками */
            margin: -4px 0;
            /* Компенсируем отступы для выравнивания */
        }

        .brigade-content thead {
            position: sticky;
            top: 0;
            /*background-color: #2c2c34;*/
            /* Цвет фона заголовка */
            z-index: 10;
        }

        .brigade-content th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #3a3a44;
        }

        .brigade-content tbody tr {
            /* background-color: #3a3a44;*/
            /* Цвет фона строк */
            transition: all 0.2s ease;
            border-radius: 6px;
            /* Закругленные углы */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Тень для разделения */
        }

        .brigade-content td {
            padding: 12px 15px;
            border-top: 1px solid #44444e;
            border-bottom: 1px solid #44444e;
        }

        /* Закругленные углы для первой и последней ячейки */
        .brigade-content td:first-child {
            border-left: 1px solid #44444e;
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .brigade-content td:last-child {
            border-right: 1px solid #44444e;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        /* Стили для выделения строк */
        .brigade-content .row-container1 {
            cursor: pointer;
            user-select: none;
        }

        .brigade-content .row-container1:hover {
            background-color: #42434a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .brigade-content .row-container1.selected {
            background: #1f93f1 !important;
            color: white;
        }

        /* Убираем двойные границы между ячейками */
        .brigade-content tr td {
            border-top: none;
            border-bottom: none;
        }

        /* ================================================================== */
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


    <div class="modal fade" id="atWorkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ТМЦ в работе</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="brigade-actions mb-3">
                        <button class="btn btn-primary" id="btnReturnTMC">Вернуть ТМЦ</button>
                        <!--button-- class="btn btn-warning" id="btnSendToService"
                            onclick="sendToService('row-container1', ServiceStatus.sendService)">Отправить в
                            сервис</!--button-->
                    </div>

                    <div class="brigade-list">
                        <?php foreach ($atWorkGroups as $group): ?>
                            <div class="brigade-group" data-group-id="<?= $group['id'] ?>">
                                <div class="brigade-header" data-bs-toggle="collapse" href="#collapseGroup<?= $group['id'] ?>">
                                    <div class="brigade-title">
                                        <svg class="collapse-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
                                        </svg>
                                        <h6 class="m-0">
                                            Бригада: <?= $group['name'] ?>, отв. - <?= $group['brigadir'] ?>.
                                            Кол-во ТМЦ: <span class="items-count"><?= $group['count'] ?></span>
                                        </h6>
                                    </div>
                                </div>

                                <div class="collapse" id="collapseGroup<?= $group['id'] ?>">
                                    <div class="brigade-content">
                                        <table class="atWorkTable">
                                            <thead>
                                                <tr>
                                                    <th>Ид.</th>
                                                    <th>Наименование</th>
                                                    <th>Сер. номер</th>
                                                    <th>Ответств.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($group['items'] as $item): ?>
                                                    <tr class="row-container1" data-id="<?= $item->ID_TMC ?>"
                                                        data-brigade="<?= $group['id'] ?>" data-status="<?= $item->Status ?>">
                                                        <td><?= $item->ID_TMC ?></td>
                                                        <td><?= $item->NameTMC ?></td>
                                                        <td><?= $item->SerialNumber ?></td>
                                                        <td><?= $item->User->FIO ?></td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-warning btn-sm btn-service"
                                                                data-tmc-id="<?= $item->ID_TMC ?>"
                                                                onclick="showServiceForm(this, <?= $item->ID_TMC ?>)">
                                                                <i class="bi bi-tools me-1"></i>В сервис
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="service-form-row" style="display: none;">
                                                        <td colspan="5">
                                                            <div class="service-form-section collapsed" id="serviceForm-<?= $item->ID_TMC ?>">
                                                                <form class="service-form" data-tmc-id="<?= $item->ID_TMC ?>">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <label for="reason-<?= $item->ID_TMC ?>" class="form-label">
                                                                                Причина ремонта
                                                                            </label>
                                                                            <textarea class="form-control repair-reason-input"
                                                                                id="reason-<?= $item->ID_TMC ?>"
                                                                                name="reason"
                                                                                placeholder="Укажите причину ремонта"
                                                                                rows="3"
                                                                                required></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-3">
                                                                        <button type="button" class="btn btn-primary btn-sm" onclick="sendServiceForm(<?= $item->ID_TMC ?>)">
                                                                            <i class="bi bi-check-lg me-1"></i>Отправить
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-secondary btn-sm"
                                                                            onclick="hideServiceForm(<?= $item->ID_TMC ?>)">
                                                                            <i class="bi bi-x-lg me-1"></i>Отмена
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include __DIR__ . '/message_modal.php';
    ?>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastSelectedRow = null;
            const modal = document.getElementById('atWorkModal');

            // Обработчик выделения строк
            modal.addEventListener('click', function(e) {
                const row = e.target.closest('.row-container1');
                if (!row) return;

                if (e.ctrlKey) {
                    row.classList.toggle('selected');
                    lastSelectedRow = row;
                    return;
                }

                const tbody = row.closest('tbody');
                const rows = tbody.querySelectorAll('.row-container1');

                if (e.shiftKey && lastSelectedRow) {
                    const startIdx = Array.from(rows).indexOf(lastSelectedRow);
                    const endIdx = Array.from(rows).indexOf(row);
                    const [start, end] = [Math.min(startIdx, endIdx), Math.max(startIdx, endIdx)];

                    rows.forEach(r => r.classList.remove('selected'));
                    for (let i = start; i <= end; i++) {
                        rows[i].classList.add('selected');
                    }
                } else {
                    rows.forEach(r => r.classList.remove('selected'));
                    row.classList.add('selected');
                    lastSelectedRow = row;
                }
            });

            // Обработчик возврата ТМЦ
            document.getElementById('btnReturnTMC').addEventListener('click', function() {
                const selectedRows = modal.querySelectorAll('.row-container1.selected');
                if (selectedRows.length === 0) {
                    alert('Выберите ТМЦ для возврата');
                    return;
                }

                const tmcIds = Array.from(selectedRows).map(row =>
                    row.getAttribute('data-id')
                );

                // Получаем brigade_id из первой выбранной строки
                const brigadeId = selectedRows[0].getAttribute('data-brigade');

                try {
                    const result = executeEntityAction({
                        action: Action.UPDATE,
                        formData: data,
                        url: "/src/BusinessLogic/Actions/processCUDReturnFromWork.php",
                        successMessage: "ТМЦ успешно переданы на склад",
                    });

                    updateInventoryStatus(window.selectedTMCIds, StatusItem.AtWorkTMC);
                    updateCounters({
                        brigadesToItemsCount: window.selectedTMCIds.length,
                    });

                    modal.hide();
                } catch (error) {
                    console.error("Error:", error);
                    showNotification(
                        TypeMessage.error,
                        "Произошла ошибка при передаче ТМЦ на склад",
                    );
                }
            });
        });
    </script>
<?php endif; ?>