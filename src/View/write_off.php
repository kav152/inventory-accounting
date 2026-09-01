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

$pageFilter = (string) ($_GET['filter'] ?? '');
$onlyWrittenOff = ($pageFilter === 'written-off');
// pending / verified — вкладки архива на странице списания
$archiveFilter = in_array($pageFilter, ['pending', 'verified'], true) ? $pageFilter : '';
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';

// счёт считаем заполненным, если не пустой и не прочерк/нули
function repairHasInvoice($repair): bool
{
    $invoice = trim((string) ($repair->InvoiceNumber ?? ''));
    return $invoice !== '' && $invoice !== '-' && !preg_match('/^0+$/', $invoice);
}

// по всем ремонтам строки — иначе «ожидает счёт»
function repairsAreVerified(array $repairs): bool
{
    if ($repairs === []) {
        return false;
    }
    foreach ($repairs as $repair) {
        if (!repairHasInvoice($repair)) {
            return false;
        }
    }
    return true;
}

if ($onlyWrittenOff) {
    $groupedItems = $repairContainer->getWrittenOffGroupedItems();
    $repairItems = [];
    foreach ($groupedItems as $group) {
        foreach ($group['repairs'] as $repair) {
            $repairItems[] = $repair;
        }
    }
} else {
    $repairItems = $repairContainer->writeOffItems();
}

/*
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
error_log("Время загрузки repairItems: " . $loadTime . " секунд. Загружено объектов: " . ($repairItems ? count($repairItems) : 0));*/


$startTime = microtime(true);

// Формируем уникальные значения для фильтров
$uniqueNames = [];
$uniqueLocations = [];

foreach ($repairItems as $item) {
    if (!isset($item->InventoryItem)) {
        continue;
    }
    if (!in_array($item->InventoryItem->NameTMC, $uniqueNames)) {
        $uniqueNames[] = $item->InventoryItem->NameTMC;
    }
    $locName = $item->InventoryItem->Location->NameLocation ?? '';
    if ($locName !== '' && !in_array($locName, $uniqueLocations)) {
        $uniqueLocations[] = $locName;
    }
}

sort($uniqueNames);
sort($uniqueLocations);

// Группируем данные по ID_TMC для основной таблицы
if (!$onlyWrittenOff) {
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
}

$pendingCount = 0;
$verifiedCount = 0;
// счётчики для шапки — считаем до фильтра вкладок
foreach ($groupedItems as $item) {
    if (repairsAreVerified($item['repairs'])) {
        $verifiedCount++;
    } else {
        $pendingCount++;
    }
}

// ?filter=pending|verified
if ($archiveFilter !== '' && !$onlyWrittenOff) {
    $groupedItems = array_filter($groupedItems, static function ($item) use ($archiveFilter) {
        $verified = repairsAreVerified($item['repairs']);
        return $archiveFilter === 'verified' ? $verified : !$verified;
    });
}

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
    <title>Архив ремонтов / списание</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php
      $filterCssVer = @filemtime(__DIR__ . '/../../styles/filterStyle.css') ?: time();
      $writeOffCssVer = @filemtime(__DIR__ . '/../../styles/writeOff.css') ?: time();
    ?>
    <link href="/styles/filterStyle.css?v=<?= $filterCssVer ?>" rel="stylesheet">
    <link href="/styles/writeOff.css?v=<?= $writeOffCssVer ?>" rel="stylesheet">
    <style>
      /* Safety net: selected row must stay light even if main CSS is stale */
      #writeOffTable tbody tr.main-row.selected td {
        background: #ecfdf8 !important;
        color: #0f172a !important;
      }
      #writeOffTable tbody tr.main-row.selected td:first-child {
        box-shadow: inset 3px 0 0 #0d9488;
      }
      .btn-action span { display: none !important; }
    </style>

    <script type="module" src="/src/constants/actions.js"></script>
    <script type="module" src="/src/constants/statusItem.js"></script>
    <script type="module" src="/src/constants/statusService.js"></script>
    <script type="module" src="/src/constants/typeMessage.js"></script>
    <script type="module" src="/js/updateFunctions.js"></script>
    <script type="module" src="/js/modals/setting.js"></script>
    

</head>

<body class="writeoff-page">
    <?php include __DIR__ . '/Modal/message_modal.php'; ?>
    <?php include __DIR__ . '/Modal/report_modal.php'; ?>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark"><i class="bi bi-wrench-adjustable"></i></div>
            <div class="brand-text">
                <strong>ТМЦ</strong>
                <span>Архив ремонтов</span>
            </div>
        </div>
        <div class="sidebar-label">Действия</div>
        <ul class="sidebar-menu">
            <li><a href="#" onclick="editSelected()"><i class="bi bi-pencil-square"></i><span>Редактировать</span></a></li>
            <li><a href="#" onclick="generateReport()"><i class="bi bi-file-earmark-bar-graph"></i><span>Сформировать отчет</span></a></li>
            <li><a href="#" onclick="openRepairBasketModal(Action.CREATE)"><i class="bi bi-cart3"></i><span>Корзина</span></a></li>
            <li><a href="#" onclick="returnToWorkTMC()"><i class="bi bi-arrow-counterclockwise"></i><span>Вернуть в работу</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="/home" class="sidebar-home"><i class="bi bi-house-door"></i><span>На главную</span></a>
        </div>
    </aside>

    <div class="main-content">
        <header class="page-hero">
            <div class="hero-copy">
                <p class="hero-kicker">Архив ремонтов</p>
                <h1 class="page-title"><?= $onlyWrittenOff ? 'Все списанные' : 'Списание / ремонт' ?></h1>
                <p class="page-subtitle">
                    <?= $onlyWrittenOff
                        ? 'Только ТМЦ со статусом «Списано» — счета и история ремонтов'
                        : 'Кладовщик отправляет ТМЦ в сервис — запись появляется здесь. Администратор указывает № счёта, после сохранения строка получает статус «Проверено».' ?>
                </p>
            </div>
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-label">Записей</span>
                    <strong class="stat-value"><?= count($groupedItems) ?></strong>
                </div>
                <?php if (!$onlyWrittenOff): ?>
                <div class="stat-card">
                    <span class="stat-label">Ожидают счёт</span>
                    <strong class="stat-value stat-pending"><?= $pendingCount ?></strong>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Проверено</span>
                    <strong class="stat-value stat-verified"><?= $verifiedCount ?></strong>
                </div>
                <?php endif; ?>
                <div class="stat-card stat-card-accent">
                    <span class="stat-label">Сумма ремонта</span>
                    <strong class="stat-value" id="hero-total-sum"><?= number_format($totalRepairCost, 2, ',', ' ') ?> ₽</strong>
                </div>
            </div>
        </header>

        <section class="table-section">
            <div class="table-toolbar">
                <div class="toolbar-title">
                    <i class="bi bi-table"></i>
                    <span><?= $onlyWrittenOff ? 'Реестр списанных' : 'Реестр ТМЦ' ?></span>
                </div>
                <div class="toolbar-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="writeOffSearchInput" class="form-control form-control-sm"
                        placeholder="Поиск: id, наименование, серийный, бренд, локация…"
                        autocomplete="off">
                </div>
                <?php if (!$onlyWrittenOff): ?>
                <?php // быстрый отбор для админа ?>
                <div class="archive-tabs">
                    <a href="/src/View/write_off.php" class="archive-tab<?= $archiveFilter === '' ? ' active' : '' ?>">Все</a>
                    <a href="/src/View/write_off.php?filter=pending" class="archive-tab<?= $archiveFilter === 'pending' ? ' active' : '' ?>">Ожидают счёт</a>
                    <a href="/src/View/write_off.php?filter=verified" class="archive-tab<?= $archiveFilter === 'verified' ? ' active' : '' ?>">Проверено</a>
                </div>
                <?php endif; ?>
                <div class="toolbar-hint">
                    <?php if ($onlyWrittenOff): ?>
                        <a href="/src/View/write_off.php" style="color:inherit;text-decoration:none;">← Все записи</a>
                    <?php else: ?>
                        Клик по строке — история ремонтов
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive" id="idTableResponsive">
                <table class="table write-off-table" id="writeOffTable">
                    <thead>
                        <tr>
                            <th>Регистр</th>
                            <th>Наименование</th>
                            <th>Бренд</th>
                            <th>Серийный номер</th>
                            <th>Ответственный</th>
                            <th>Статус</th>
                            <th>Проверка</th>
                            <th>Локация</th>
                            <th>№ счета</th>
                            <th>Сумма ремонта</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedItems as $id => $itemData):
                            $mainItem = $itemData['main'];
                            $repairs = $itemData['repairs'];
                            $totalCost = 0;
                            $invoices = [];
                            foreach ($repairs as $repair) {
                                $totalCost += $repair->RepairCost;
                                if (repairHasInvoice($repair)) {
                                    $invoices[] = trim((string) $repair->InvoiceNumber);
                                }
                            }
                            $invoices = array_values(array_unique($invoices));
                            $isVerified = repairsAreVerified($repairs);
                            $statusValue = (int) $mainItem->InventoryItem->Status;
                            $statusText = (new StatusItem())->getDescription($statusValue);
                            $verificationText = $isVerified ? 'проверено' : 'ожидает счёт';
                            $statusClass = $statusValue === StatusItem::WrittenOff
                                ? 'status-written-off'
                                : ($statusValue === StatusItem::Repair ? 'status-repair' : 'status-default');
                            $brand = $mainItem->InventoryItem->BrandTMC->NameBrand ?? '';
                            $searchBlob = mb_strtolower(trim(implode(' ', [
                                (string) $mainItem->ID_TMC,
                                (string) ($mainItem->InventoryItem->NameTMC ?? ''),
                                (string) ($mainItem->InventoryItem->SerialNumber ?? ''),
                                (string) $brand,
                                (string) ($mainItem->InventoryItem->Location->NameLocation ?? ''),
                                (string) ($statusText ?? ''),
                                (string) $verificationText,
                            ])));
                        ?>
                            <tr class="main-row" data-id="<?= $mainItem->ID_TMC ?>"
                                data-status="<?= $statusValue ?>"
                                data-verified="<?= $isVerified ? '1' : '0' ?>"
                                data-name="<?= htmlspecialchars($mainItem->InventoryItem->NameTMC) ?>"
                                data-location="<?= htmlspecialchars($mainItem->InventoryItem->Location->NameLocation ?? '') ?>"
                                data-total-cost="<?= $totalCost ?>"
                                data-search="<?= htmlspecialchars($searchBlob) ?>">
                                <td><span class="id-chip"><?= $mainItem->ID_TMC ?></span></td>
                                <td class="col-name"><?= htmlspecialchars($mainItem->InventoryItem->NameTMC) ?></td>
                                <td>
                                    <?php if ($brand !== ''): ?>
                                        <span class="brand-chip"><?= htmlspecialchars($brand) ?></span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-serial"><?= htmlspecialchars($mainItem->InventoryItem->SerialNumber ?? '') ?: '—' ?></td>
                                <td><?= htmlspecialchars($mainItem->RegistrationInventoryItem->User->FIO ?? '') ?: '—' ?></td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                </td>
                                <td>
                                    <?php if ($isVerified): ?>
                                        <span class="status-badge status-verified"><i class="bi bi-check-circle-fill"></i> Проверено</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending-invoice"><i class="bi bi-hourglass-split"></i> Ожидает счёт</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($mainItem->InventoryItem->Location->NameLocation ?? '') ?: '—' ?></td>
                                <td class="col-doc">
                                    <?php if ($invoices): ?>
                                        <div class="doc-stack">
                                            <span class="doc-chip" title="<?= htmlspecialchars(implode(', ', $invoices)) ?>">
                                                <?= htmlspecialchars($invoices[0]) ?>
                                            </span>
                                            <?php if (count($invoices) > 1): ?>
                                                <span class="doc-more">+<?= count($invoices) - 1 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cost-cell"><?= number_format($totalCost, 2, ',', ' ') ?> ₽</td>
                                <td class="action-buttons" onclick="event.stopPropagation()">
                                    <?php if ($statusValue === StatusItem::WrittenOff): ?>
                                        <button type="button" class="btn-action btn-restore restore-btn"
                                            title="Вернуть из списания" data-id="<?= $mainItem->ID_TMC ?>"
                                            onclick="returnToWorkTMC(<?= (int) $mainItem->ID_TMC ?>)">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn-action btn-edit edit-btn"
                                        title="Редактировать" data-id="<?= $mainItem->ID_TMC ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-delete delete-btn"
                                        title="В корзину" data-id="<?= $mainItem->ID_TMC ?>">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="repair-details-row" id="details-<?= $mainItem->ID_TMC ?>" style="display: none;">
                                <td colspan="11">
                                    <div class="repair-details">
                                        <div class="repair-details-head">
                                            <h6>История ремонтов</h6>
                                            <span class="details-count"><?= count($repairs) ?> записей</span>
                                        </div>
                                        <table class="table table-sm repair-table">
                                            <thead>
                                                <tr>
                                                    <th>№ счета</th>
                                                    <th>Проверка</th>
                                                    <th>Стоимость</th>
                                                    <th>Дата отправки</th>
                                                    <th>Дата возвращения</th>
                                                    <th>Примечания</th>
                                                    <th>Сервис</th>
                                                    <th>Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($repairs as $repair): ?>
                                                    <tr class="repair-line" data-repair-id="<?= (int) $repair->ID_Repair ?>"
                                                        data-tmc-id="<?= (int) $mainItem->ID_TMC ?>">
                                                        <td><?= htmlspecialchars($repair->InvoiceNumber ?? '') ?: '—' ?></td>
                                                        <td>
                                                            <?php if (repairHasInvoice($repair)): ?>
                                                                <span class="status-badge status-verified">Проверено</span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-pending-invoice">Ожидает счёт</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= number_format($repair->RepairCost, 2, ',', ' ') ?> ₽</td>
                                                        <td><?= $repair->DateToService ? date('d.m.Y', strtotime($repair->DateToService)) : '—' ?></td>
                                                        <td><?= $repair->DateReturnService ? date('d.m.Y', strtotime($repair->DateReturnService)) : '—' ?></td>
                                                        <td><?= htmlspecialchars($repair->RepairDescription ?? '') ?: '—' ?></td>
                                                        <td><?= htmlspecialchars($repair->Location->NameLocation ?? '') ?: '—' ?></td>
                                                        <td class="action-buttons" onclick="event.stopPropagation()">
                                                            <button type="button"
                                                                class="btn-action btn-edit repair-edit-btn"
                                                                title="Изменить запись"
                                                                data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                data-repair-id="<?= (int) $repair->ID_Repair ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button"
                                                                class="btn-action btn-delete repair-delete-btn"
                                                                title="Удалить запись"
                                                                data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                data-repair-id="<?= (int) $repair->ID_Repair ?>">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </td>
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
        </section>

        <div class="summary-section" id="total-summary">
            <div class="summary-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="summary-text">
                <span class="summary-label">Общая сумма ремонта ТМЦ</span>
                <strong class="summary-amount"><?= number_format($totalRepairCost, 2, ',', ' ') ?> ₽</strong>
            </div>
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
            const searchValue = (document.getElementById('writeOffSearchInput')?.value || '')
                .trim()
                .toLowerCase();

            const rows = document.querySelectorAll('.main-row');
            let visibleCount = 0;
            let filteredTotal = 0;

            rows.forEach(row => {
                let visible = true;
                const name = row.getAttribute('data-name');
                const location = row.getAttribute('data-location');
                const cost = parseFloat(row.getAttribute('data-total-cost'));
                const searchBlob = row.getAttribute('data-search') || '';

                if (filters.name.length > 0 && !filters.name.includes(name)) {
                    visible = false;
                }
                if (filters.location.length > 0 && !filters.location.includes(location)) {
                    visible = false;
                }
                if (searchValue && !searchBlob.includes(searchValue)) {
                    visible = false;
                }

                row.style.display = visible ? '' : 'none';

                const id = row.getAttribute('data-id');
                const detailsRow = document.getElementById('details-' + id);
                if (detailsRow) {
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

            const summaryEl = document.getElementById('total-summary');
            if (summaryEl) {
                summaryEl.innerHTML = `
                    <div class="summary-icon"><i class="bi bi-cash-coin"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Общая сумма ремонта ТМЦ</span>
                        <strong class="summary-amount">${formattedSum} ₽</strong>
                    </div>`;
            }
            const heroSum = document.getElementById('hero-total-sum');
            if (heroSum) heroSum.textContent = `${formattedSum} ₽`;
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
        function editSelected(idFromBtn = null, repairId = null) {
            const id = idFromBtn || (selectedRow ? selectedRow.getAttribute('data-id') : null);
            if (!id) {
                showNotification(TypeMessage.notification, 'Пожалуйста, выберите запись для редактирования.');
                return;
            }

            const params = { id: id };
            if (repairId) {
                params.repairId = repairId;
            }

            window.openModalAction("edit_write_off", null, null, params);
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

            const searchInput = document.getElementById('writeOffSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        applyFilters();
                    }
                });
            }

            // Добавление обработчиков событий для фильтров
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            // Обработчики для строк таблицы
            document.querySelectorAll('.main-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Не выделяем строку при клике на кнопки действий
                    if (!e.target.closest('.delete-btn, .edit-btn, .restore-btn') && this.style.display !== 'none') {
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

            // Обработчики для кнопок редактирования
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-id');
                    const row = this.closest('.main-row');
                    if (row) selectRow(row);
                    editSelected(id);
                });
            });

            // Действия в истории ремонтов (каждая запись)
            document.querySelectorAll('.repair-edit-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const tmcId = this.getAttribute('data-tmc-id');
                    const repairId = this.getAttribute('data-repair-id');
                    const mainRow = document.querySelector(`.main-row[data-id="${tmcId}"]`);
                    if (mainRow) selectRow(mainRow);
                    editSelected(tmcId, repairId);
                });
            });

            document.querySelectorAll('.repair-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const repairId = this.getAttribute('data-repair-id');
                    const tmcId = this.getAttribute('data-tmc-id');
                    deleteRepairLine(repairId, tmcId);
                });
            });
        });
    </script>

    <script type="module" src="/js/modals/modalLoader.js"></script>
    <script type="module" src="/js/modals/repairBasketModal.js"></script>

    <div id="modalContainer"></div>
</body>

</html>