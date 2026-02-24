<?php
set_time_limit(0);
//ini_set('memory_limit', '1024M');
session_start();
if (!isset($_SESSION['IDUser'])) {
    header('Location: index.php');
    exit();
}
/*
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../storage/logs/write_off.log');*/

require_once __DIR__ . '/../Entity/InventoryItem.php';
require_once __DIR__ . '/../BusinessLogic/ItemRepairController.php';

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();

$container = new ItemController();
$repairContainer = new ItemRepairController();
$statusUser = $_SESSION["Status"];

$names = [];
$locations = [];

$startTime = microtime(true);

$repairItems = $repairContainer->writeOffItems();
/*
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
error_log("Время загрузки repairItems: " . $loadTime . " секунд. Загружено объектов: " . ($repairItems ? count($repairItems) : 0));*/


$startTime = microtime(true);

// Формируем уникальные значения для фильтров
$uniqueNames = [];
$uniqueLocations = [];

foreach ($repairItems as $item) {

    if (!in_array($item->InventoryItem->NameTMC, $uniqueNames)) {
        $uniqueNames[] = $item->InventoryItem->NameTMC;
    }
    if (!in_array($item->InventoryItem->Location->NameLocation, $uniqueLocations)) {
        $uniqueLocations[] = $item->InventoryItem->Location->NameLocation;
    }
}

sort($uniqueNames);
sort($uniqueLocations);

// Группируем данные по ID_TMC для основной таблицы
$groupedItems = [];
foreach ($repairItems as $item) {
    $id = $item->ID_TMC;
    if (!isset($groupedItems[$id])) {
        $groupedItems[$id] = [
            'main' => $item,
            'repairs' => []
        ];
    }
    $groupedItems[$id]['repairs'][] = $item;
}

//print_r($groupedItems);

// Вычисляем общую сумму ремонта
$totalRepairCost = 0;
foreach ($groupedItems as $item) {
    foreach ($item['repairs'] as $repair) {
        $totalRepairCost = $totalRepairCost + $repair->RepairCost;
    }
}

/*$endTime = microtime(true);
$loadTime = $endTime - $startTime;
error_log("Время группировки данных по ID_TMC для основной таблицы: " . $loadTime . " секунд.");*/

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Списание/затраты на ремонт</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="\..\..\styles\filterStyle.css" rel="stylesheet">
    <link href="\..\..\styles\writeOff.css" rel="stylesheet">

    <script type="module" src="/src/constants/actions.js"></script>
    <script type="module" src="/src/constants/statusItem.js"></script>
    <script type="module" src="/src/constants/statusService.js"></script>
    <script type="module" src="/src/constants/typeMessage.js"></script>
    <script type="module" src="/js/updateFunctions.js"></script>
    <script type="module" src="/js/modals/setting.js"></script>
    

</head>

<body>
    <?php include __DIR__ . '/Modal/message_modal.php'; ?>
    <?php include __DIR__ . '/Modal/report_modal.php'; ?>
    <!--?php include __DIR__ . '/Modal/basket_modal.php'; ?-->


    <!-- Боковое меню -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="#" onclick="editSelected()"><i class="bi bi-pencil"></i> Редактировать</a></li>
            <li><a href="#" onclick="generateReport()"><i class="bi bi-file-earmark-pdf"></i> Сформировать отчет</a>
            </li>
            <li><a href="#" onclick="openRepairBasketModal(Action.CREATE)"><i class="bi bi-cart"></i> Корзина</a></li>
            <li><a href="#" onclick="returnToWorkTMC()"><i class="bi bi-arrow-return-left"></i> Вернуть в работу</a>
            </li>
            <li><a href="home.php"><i class="bi bi-house"></i> На главную</a></li>
        </ul>
    </div>

    <div class="main-content">
        <!-- Фильтры -->
        <!--div class="filter-section">
            <h4 class="mb-3">Фильтры</h4>
            <div class="row">
                <div-- class="col-md-6">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Наименование</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="name">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..."
                                data-filter="name">
                            <span class="filter-search-clear" data-filter="name">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="name-options">
                            <?php foreach ($uniqueNames as $name): ?>
                                <div class="form-check filter-option">
                                    <input class="form-check-input filter-checkbox" type="checkbox"
                                        value="<?= htmlspecialchars($name) ?>" id="name-<?= md5($name) ?>"
                                        data-filter="name">
                                    <label class="form-check-label" for="name-<?= md5($name) ?>">
                                        <?= htmlspecialchars($name) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div-->

                <!--div class="col-md-6">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Локация</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="location">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..."
                                data-filter="location">
                            <span class="filter-search-clear" data-filter="location">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="location-options">
                            <?php foreach ($uniqueLocations as $location): ?>
                                <div class="form-check filter-option">
                                    <input class="form-check-input filter-checkbox" type="checkbox"
                                        value="<?= htmlspecialchars($location) ?>" id="location-<?= md5($location) ?>"
                                        data-filter="location">
                                    <label class="form-check-label" for="location-<?= md5($location) ?>">
                                        <?= htmlspecialchars($location) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </!--div>
            </div>
        </div-->

        <!-- Таблица с данными -->
        <div class="table-section">
            <div class="table-responsive" id="idTableResponsive">
                <table class="table table-striped table-hover" id="writeOffTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Регистр</th>
                            <th>Наименование</th>
                            <th>Бренд</th>
                            <th>Серийный номер</th>
                            <th>Ответственный</th>
                            <th>Статус</th>
                            <th>Локация</th>
                            <th>№ УПД</th>
                            <th>Сумма ремонта</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedItems as $id => $itemData):
                            $mainItem = $itemData['main'];
                            $repairs = $itemData['repairs'];
                            $totalCost = 0;
                            foreach ($repairs as $repair) {
                                $totalCost += $repair->RepairCost;
                            }
                        ?>
                            <tr class="main-row" data-id="<?= $mainItem->ID_TMC ?>"

                                data-status="<?= $mainItem->InventoryItem->Status ?>"
                                data-name="<?= htmlspecialchars($mainItem->InventoryItem->NameTMC) ?>"
                                data-location="<?= htmlspecialchars($mainItem->InventoryItem->Location->NameLocation ?? '') ?>"
                                data-total-cost="<?= $totalCost ?>">
                                <td><?= $mainItem->ID_TMC ?></td>
                                <td><?= htmlspecialchars($mainItem->InventoryItem->NameTMC) ?></td>
                                <td><?= htmlspecialchars($mainItem->InventoryItem->BrandTMC->NameBrand ?? '') ?></td>
                                <td><?= htmlspecialchars($mainItem->InventoryItem->SerialNumber ?? '') ?></td>
                                <td><?= htmlspecialchars($mainItem->RegistrationInventoryItem->User->FIO ?? '') ?></td>
                                <td><?= (new StatusItem())->getDescription($mainItem->InventoryItem->Status) ?></td>
                                <td><?= htmlspecialchars($mainItem->InventoryItem->Location->NameLocation ?? '') ?></td>
                                <td>
                                    <?php
                                    $invoices = [];
                                    foreach ($repairs as $repair) {
                                        if (!empty($repair->InvoiceNumber)) {
                                            $invoices[] = $repair->InvoiceNumber;
                                        }
                                    }
                                    echo htmlspecialchars(implode(', ', array_unique($invoices)));
                                    ?>
                                </td>
                                <td class="cost-cell"><?= number_format($totalCost, 2, ',', ' ') ?> руб.</td>
                                <?php if (StatusItem::WrittenOff == $mainItem->InventoryItem->Status): ?>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $mainItem->ID_TMC ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                <?php endif; ?>

                            </tr>
                            <tr class="repair-details-row" id="details-<?= $mainItem->ID_TMC ?>" style="display: none;">
                                <td colspan="10">
                                    <div class="repair-details">
                                        <h6>История ремонтов:</h6>
                                        <table class="table table-sm repair-table">
                                            <thead>
                                                <tr>
                                                    <th>№ Счета</th>
                                                    <th>Стоимость</th>
                                                    <th>Дата отправки</th>
                                                    <th>Дата возвращения</th>
                                                    <th>Примечания</th>
                                                    <th>Сервис</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($repairs as $repair): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($repair->InvoiceNumber ?? '') ?></td>
                                                        <td><?= number_format($repair->RepairCost, 2, ',', ' ') ?> руб.</td>
                                                        <td><?= $repair->DateToService ? date('d.m.Y', strtotime($repair->DateToService)) : '' ?>
                                                        </td>
                                                        <td><?= $repair->DateReturnService ? date('d.m.Y', strtotime($repair->DateReturnService)) : '' ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($repair->RepairDescription ?? '') ?></td>
                                                        <td><?= htmlspecialchars($repair->Location->NameLocation ?? '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Общая сумма -->
        <div class="summary-section" id="total-summary">
            Общая сумма ремонта ТМЦ: <?= number_format($totalRepairCost, 2, ',', ' ') ?> руб.
        </div>
    </div>


    <script type="module" src="/js/writeOffFunctions.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <script type="module">
        import {
            initFilter
        } from '../../js/filters/filterConfigs.js';

        document.addEventListener('DOMContentLoaded', function() {
            const analyticsFilter = initFilter('WRITE_OFF', {
                onRowCountChanged: (visible, total) => {
                    console.log(`Показано ${visible} из ${total} записей`);
                }
            });

            // При размонтировании компонента очищаем фильтр
            window.addEventListener('beforeunload', function() {
                if (window.homeFilter && window.homeFilter.destroy) {
                    window.homeFilter.destroy();
                }
            });
        });
    </script>

    <script>
        // Глобальные переменные
        let allItems = <?= json_encode($groupedItems) ?>;
        let selectedRow = null;
        let initialTotal = <?= $totalRepairCost ?>;

        // Функция применения фильтров
        function applyFilters() {
            const filters = {
                name: Array.from(document.querySelectorAll('input[data-filter="name"]:checked')).map(cb => cb.value),
                location: Array.from(document.querySelectorAll('input[data-filter="location"]:checked')).map(cb => cb.value)
            };

            const rows = document.querySelectorAll('.main-row');
            let visibleCount = 0;
            let filteredTotal = 0;

            rows.forEach(row => {
                let visible = true;
                const name = row.getAttribute('data-name');
                const location = row.getAttribute('data-location');
                const cost = parseFloat(row.getAttribute('data-total-cost'));

                if (filters.name.length > 0 && !filters.name.includes(name)) {
                    visible = false;
                }
                if (filters.location.length > 0 && !filters.location.includes(location)) {
                    visible = false;
                }

                row.style.display = visible ? '' : 'none';

                // Скрываем соответствующий ряд с деталями
                /*const id = row.getAttribute('data-id');
                const detailsRow = document.getElementById('details-' + id);
                if (detailsRow) {
                    detailsRow.style.display = visible ? '' : 'none';
                }*/
                const id = row.getAttribute('data-id');
                const detailsRow = document.getElementById('details-' + id);
                if (detailsRow) {
                    // Не показываем детали при фильтрации, если строка не выбрана
                    if (row.classList.contains('selected') && visible) {
                        detailsRow.style.display = '';
                    } else {
                        detailsRow.style.display = 'none';
                    }
                }

                if (visible) {
                    visibleCount++;
                    filteredTotal += cost;
                }
            });

            // Обновляем общую сумму
            updateTotalSum(filteredTotal);
        }

        // Функция обновления общей суммы
        function updateTotalSum(sum) {
            const formattedSum = new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(sum);

            document.getElementById('total-summary').textContent =
                `Общая сумма ремонта ТМЦ: ${formattedSum} руб.`;
        }

        // Функция настройки поиска в фильтрах
        function setupFilterSearch() {
            document.querySelectorAll('.search-input').forEach(input => {
                input.addEventListener('input', function() {
                    const filterType = this.getAttribute('data-filter');
                    const searchValue = this.value.toLowerCase();
                    const options = document.querySelectorAll(`#${filterType}-options .filter-option`);

                    options.forEach(option => {
                        const label = option.querySelector('label').textContent.toLowerCase();
                        if (label.includes(searchValue)) {
                            option.style.display = 'block';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            });

            // Очистка поиска
            document.querySelectorAll('.filter-search-clear').forEach(clearBtn => {
                clearBtn.addEventListener('click', function() {
                    const filterType = this.getAttribute('data-filter');
                    const input = document.querySelector(`.search-input[data-filter="${filterType}"]`);
                    input.value = '';

                    const options = document.querySelectorAll(`#${filterType}-options .filter-option`);
                    options.forEach(option => {
                        option.style.display = 'block';
                    });
                });
            });

            // Очистка фильтров
            document.querySelectorAll('.clear-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filterType = this.getAttribute('data-filter');
                    const checkboxes = document.querySelectorAll(`input[data-filter="${filterType}"]:checked`);
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    applyFilters();
                });
            });
        }

        // Замените функцию selectRow на эту:
        function selectRow(row) {
            // Снимаем выделение со всех строк
            document.querySelectorAll('.main-row').forEach(r => {
                r.classList.remove('selected');
            });

            // Выделяем текущую строку
            row.classList.add('selected');
            selectedRow = row;

            // Показываем/скрываем детали
            const id = row.getAttribute('data-id');

            // Скрываем все детали
            document.querySelectorAll('.repair-details-row').forEach(dr => {
                dr.style.display = 'none';
            });

            // Показываем детали выбранной строки только если она видима
            if (row.style.display !== 'none') {
                const detailsRow = document.getElementById('details-' + id);
                if (detailsRow) {
                    detailsRow.style.display = 'table-row';
                }
            }
        }
        // Функция выделения строки
        /*function selectRow(row) {
            // Снимаем выделение со всех строк
            document.querySelectorAll('.main-row').forEach(r => {
                r.classList.remove('selected');
            });

            // Выделяем текущую строку
            row.classList.add('selected');
            selectedRow = row;

            // Показываем/скрываем детали
            const id = row.getAttribute('data-id');
            const detailsRow = document.getElementById('details-' + id);

            // Скрываем все детали
            document.querySelectorAll('.repair-details-row').forEach(dr => {
                dr.style.display = 'none';
            });

            // Показываем детали выбранной строки
            if (detailsRow) {
                detailsRow.style.display = 'table-row';
            }
        }*/

        // Функция редактирования выбранной записи
        function editSelected() {
            if (!selectedRow) {
                showNotification(TypeMessage.notification, 'Пожалуйста, выберите запись для редактирования.');
                return;
            }

            const id = selectedRow.getAttribute('data-id');

            //validStatuses = [StatusItem.Repair, StatusItem.NotDistributed]; // Добавляем в конец массива

            window.openModalAction("edit_write_off", null, null, {
                id: id
            });
            // Редирект на страницу редактирования или открытие модального окна
            //alert('Редактирование записи с ID: ' + id);
            // window.location.href = `edit_write_off.php?id=${id}`;
        }


        function generateReport() {
            // Показываем модальное окно
            document.getElementById("reportModal").style.display = "block";
        }
        // Функция печати отчета
        function printReport() {
            const printContent = document.getElementById('reportContent').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;

            // Перезагружаем страницу для восстановления функциональности
            location.reload();
        }

        // Функция экспорта в PDF (заглушка)
        function exportToPDF() {
            alert('Функция экспорта в PDF будет реализована в будущем');
        }

        // Закрытие модального окна
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('reportModal').style.display = 'none';
        });

        // Закрытие модального окна при клике вне его
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('reportModal')) {
                document.getElementById('reportModal').style.display = 'none';
            }
        });

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Настройка поиска в фильтрах
            setupFilterSearch();

            // Добавление обработчиков событий для фильтров
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            // Обработчики для строк таблицы
            /*  document.querySelectorAll('.main-row').forEach(row => {
                  row.addEventListener('click', function (e) {
                      // Не выделяем строку при клике на кнопку удаления
                      if (!e.target.closest('.delete-btn')) {
                          console.log('выделяем строку при клике');
                          selectRow(this);
                      }
                  });
              });*/

            document.querySelectorAll('.main-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Не выделяем строку при клике на кнопку удаления или если строка скрыта
                    if (!e.target.closest('.delete-btn') && this.style.display !== 'none') {
                        selectRow(this);
                    }
                });
            });

            // Обработчики для кнопок удаления
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-id');
                    deleteRow(id);
                });
            });
        });
    </script>

    <script type="module" src="/js/modals/modalLoader.js"></script>
    <script type="module" src="/js/modals/repairBasketModal.js"></script>

    <div id="modalContainer"></div>
</body>

</html>