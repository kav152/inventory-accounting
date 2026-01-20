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
                        <button class="btn btn-warning" id="btnSendToService"
                            onclick="sendToService('row-container1', ServiceStatus.sendService)">Отправить в
                            сервис</button>
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
                                        <table>
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
        document.addEventListener('DOMContentLoaded', function () {
            let lastSelectedRow = null;
            const modal = document.getElementById('atWorkModal');

            // Обработчик выделения строк
            modal.addEventListener('click', function (e) {
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
            document.getElementById('btnReturnTMC').addEventListener('click', function () {
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

                // Передаем оба параметра
                fetch('/src/BusinessLogic/ActionsTMC/processReturnFromWork.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tmc_ids: tmcIds,
                        brigade_id: brigadeId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Скрываем возвращенные строки
                            selectedRows.forEach(row => {
                                row.style.display = 'none';
                            });

                            // Обновляем счетчики
                            updateCounters(selectedRows.length);


                            if (typeof window.updateInventoryStatus === 'function') {
                                window.updateInventoryStatus(tmcIds, StatusItem.Released); // 0 = NotDistributed
                            }

                            showNotification(TypeMessage.notification, `ТМЦ возвращено на склад в кол-ве: ${selectedRows.length}`);
                        } else {
                            showNotification(TypeMessage.error, 'Ошибка: ' + data.message);
                        }
                    });
            });

            // Функция обновления счетчиков
            /*  function updateCounters(removedCount) {
                  // Обновляем общий счетчик в уведомлении
                  const atWorkBadge = document.getElementById('atWorkBadge');
                  const atWorkNotification = document.getElementById('atWorkNotification');

                  if (atWorkBadge && atWorkNotification) {
                      const currentCount = parseInt(atWorkBadge.textContent);
                      const newCount = currentCount - removedCount;

                      atWorkBadge.textContent = newCount;
                      atWorkNotification.textContent = `Выдано в работу ${newCount} ТМЦ`;

                      if (newCount <= 0) {
                          atWorkBadge.style.display = 'none';
                          atWorkNotification.style.display = 'none';
                      }
                  }

                  // Обновляем счетчики в группах
                  const groups = modal.querySelectorAll('.brigade-group');
                  groups.forEach(group => {
                      const groupId = group.getAttribute('data-group-id');
                      const visibleRows = group.querySelectorAll('.row-container1:not([style*="display: none"])');
                      const countEl = group.querySelector('.items-count');

                      if (countEl) {
                          countEl.textContent = visibleRows.length;

                          // Скрываем группу если нет видимых строк
                          if (visibleRows.length === 0) {
                              group.style.display = 'none';
                          }
                      }
                  });
              }*/

            // Обработчик кнопки "Отправить в сервис"
            // document.getElementById('btnSendToService').addEventListener('click', function()
            /* document.getElementById('btnSendToService').addEventListener('click', function () {
                 const selectedRows = modal.querySelectorAll('.row-container1.selected');
                 if (selectedRows.length === 0) {
                     alert('Выберите ТМЦ для отправки в сервис');
                     return;
                 }

                 // Очищаем контейнер
                 const container = document.getElementById('serviceItemsContainer');
                 container.innerHTML = '';

                 // Заполняем контейнер выбранными ТМЦ
                 selectedRows.forEach(row => {
                     const id = row.getAttribute('data-id');
                     const name = row.cells[1].textContent; // Наименование из второго столбца

                     const tr = document.createElement('tr');
                     tr.innerHTML = `
                             <td>${id}</td>
                             <td>${name}</td>
                             <td>
                                 <textarea class="repair-reason-input" 
                                         data-id="${id}" 
                                         required></textarea>
                             </td>
                         `;
                     container.appendChild(tr);
                 });

                 // Показываем модальное окно отправки в сервис
                 const serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
                 serviceModal.show();
             });*/


            // Обработчик кнопки "Отправить" в сервисном окне
            /* document.getElementById('btnSubmitService').addEventListener('click', function () {
                 const inputs = document.querySelectorAll('#serviceItemsContainer .repair-reason-input');
                 let allFilled = true;
                 const items = [];

                 inputs.forEach(input => {
                     if (!input.value.trim()) {
                         input.classList.add('error');
                         allFilled = false;
                     } else {
                         input.classList.remove('error');
                         items.push({
                             id: input.getAttribute('data-id'),
                             reason: input.value.trim()
                         });
                     }
                 });

                 if (!allFilled) {
                     alert('Заполните причины ремонта для всех выбранных ТМЦ');
                     return;
                 }
                 let statusService = document.getElementById("serviceModal").getAttribute("data-status");
                 // Отправляем данные на сервер
                 fetch('/src/BusinessLogic/ActionsTMC/processSendToService.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({
                         items: items,
                         statusService: statusService                       
                     })
                 })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             // Закрываем модальное окно отправки в сервис
                             const serviceModal = bootstrap.Modal.getInstance(document.getElementById('serviceModal'));
                             serviceModal.hide();

                             const selectedRows = modal.querySelectorAll('.row-container1.selected');
                             //console.log(selectedRows);

                             // Скрываем отправленные строки в основном модальном окне
                             selectedRows.forEach(row => {
                                 row.style.display = 'none';
                             });

                             // Обновляем счетчики
                             updateCounters(selectedRows.length);

                             // Обновляем статусы в главной таблице
                             if (typeof window.updateInventoryStatus === 'function') {
                                 const tmcIds = items.map(item => item.id);
                                 if(statusService == ServiceStatus.sendService){
                                     window.updateInventoryStatus(tmcIds, StatusItem.ConfirmRepairTMC);                                    
                                 }                                    

                                 if(statusService == ServiceStatus.returnService)
                                     window.updateInventoryStatus(tmcIds, StatusItem.Released);
                             }

                             //alert(`Отправлено в сервис: ${selectedRows.length} ТМЦ`);
                         } else {
                             alert('Ошибка: ' + data.message);
                         }
                     });
             });*/
        });
    </script>
<?php endif; ?>