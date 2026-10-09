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
$onlyPropose = ($pageFilter === 'propose');
// pending / verified / confirm / propose — вкладки архива
$archiveFilter = in_array($pageFilter, ['pending', 'verified', 'propose', 'confirm'], true) ? $pageFilter : '';
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';

// счёт считаем заполненным, если не пустой и не прочерк/нули/плейсхолдер
function repairHasInvoice($repair): bool
{
    $invoice = trim((string) ($repair->InvoiceNumber ?? ''));
    if ($invoice === '' || $invoice === '-') {
        return false;
    }
    if (preg_match('/^0+$/', $invoice)) {
        return false;
    }
    $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $invoice));
    $normalized = str_replace('ё', 'е', $normalized);
    $placeholders = [
        'без счета',
        'без счета.',
        'нет счета',
        'нет счета.',
        'без счет',
        'без счет.',
    ];
    return !in_array($normalized, $placeholders, true);
}

// подпись статуса ТМЦ на объекте для архива
function archiveObjectStatusLabel(int $status): string
{
    $map = [
        StatusItem::Repair => 'Подтвердить ремонт',
        StatusItem::ConfirmRepairTMC => 'Подтвердить ремонт',
        StatusItem::ProposeWriteOff => 'Предложение списания',
        StatusItem::Released => 'На объекте',
        StatusItem::AtWorkTMC => 'В работе',
        StatusItem::WrittenOff => 'Списано',
    ];
    return $map[$status] ?? (StatusItem::getDescription($status) ?? '—');
}

function archiveObjectStatusClass(int $status): string
{
    if ($status === StatusItem::Repair || $status === StatusItem::ConfirmRepairTMC) {
        return 'status-confirm-repair';
    }
    if ($status === StatusItem::ProposeWriteOff) {
        return 'status-propose-writeoff';
    }
    if ($status === StatusItem::AtWorkTMC) {
        return 'status-at-work';
    }
    if ($status === StatusItem::Released) {
        return 'status-on-site';
    }
    if ($status === StatusItem::WrittenOff) {
        return 'status-written-off';
    }
    return 'status-default';
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
    $repairItems = $repairContainer->writeOffItems() ?? [];
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
$proposeCount = 0;
$confirmRepairCount = 0;
// счётчики для шапки — считаем до фильтра вкладок
foreach ($groupedItems as $item) {
    $st = (int) ($item['main']->InventoryItem->Status ?? -1);
    if ($st === StatusItem::ProposeWriteOff) {
        $proposeCount++;
    }
    // «В ремонте» + «Подтвердить ремонт»
    if ($st === StatusItem::Repair || $st === StatusItem::ConfirmRepairTMC) {
        $confirmRepairCount++;
    }
    if (repairsAreVerified($item['repairs'])) {
        $verifiedCount++;
    } else {
        $pendingCount++;
    }
}

// ?filter=pending|verified|propose|confirm
$visibleRecordCount = count($groupedItems);
if ($archiveFilter === 'verified') {
    $visibleRecordCount = $verifiedCount;
} elseif ($archiveFilter === 'pending') {
    $visibleRecordCount = $pendingCount;
} elseif ($archiveFilter === 'confirm') {
    $visibleRecordCount = $confirmRepairCount;
    $groupedItems = array_filter(
        $groupedItems,
        static function ($item) {
            $st = (int) ($item['main']->InventoryItem->Status ?? -1);
            return $st === StatusItem::Repair || $st === StatusItem::ConfirmRepairTMC;
        }
    );
} elseif ($archiveFilter === 'propose') {
    $visibleRecordCount = $proposeCount;
    $groupedItems = array_filter(
        $groupedItems,
        static function ($item) {
            return (int) ($item['main']->InventoryItem->Status ?? -1) === StatusItem::ProposeWriteOff;
        }
    );
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
    <link href="/css/lib/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib/bootstrap-icons.min.css">
    <?php
      $writeOffCssVer = @filemtime(__DIR__ . '/../../styles/writeOff.css') ?: time();
    ?>
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
            <li><a href="#" onclick="event.preventDefault(); returnToWorkTMC();"><i class="bi bi-arrow-counterclockwise"></i><span>Вернуть в работу</span></a></li>
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
                        : 'Очередь согласования ремонта, счета и история сервиса. Статус на объекте всегда совпадает с открытой записью ремонта. Списанные — отдельный реестр.' ?>
                </p>
            </div>
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-label">Записей</span>
                    <strong class="stat-value"><?= $visibleRecordCount ?? count($groupedItems) ?></strong>
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
                <div class="archive-tabs" data-archive-filter="<?= htmlspecialchars($archiveFilter) ?>">
                    <a href="write_off.php" class="archive-tab<?= $archiveFilter === '' ? ' active' : '' ?>" data-filter="">Все</a>
                    <a href="write_off.php?filter=confirm" class="archive-tab<?= $archiveFilter === 'confirm' ? ' active' : '' ?>" data-filter="confirm">
                        Согласование ремонта<?= $confirmRepairCount > 0 ? ' (' . $confirmRepairCount . ')' : '' ?>
                    </a>
                    <a href="write_off.php?filter=pending" class="archive-tab<?= $archiveFilter === 'pending' ? ' active' : '' ?>" data-filter="pending">Ожидают счёт</a>
                    <a href="write_off.php?filter=verified" class="archive-tab<?= $archiveFilter === 'verified' ? ' active' : '' ?>" data-filter="verified">Проверено</a>
                    <a href="write_off.php?filter=propose" class="archive-tab<?= $archiveFilter === 'propose' ? ' active' : '' ?>" data-filter="propose">
                        Согласование списания<?= $proposeCount > 0 ? ' (' . $proposeCount . ')' : '' ?>
                    </a>
                    <a href="write_off.php?filter=written-off" class="archive-tab<?= $onlyWrittenOff ? ' active' : '' ?>" data-filter="written-off">Списанные</a>
                </div>
                <?php endif; ?>
                <div class="toolbar-hint" style="display:flex;gap:8px;align-items:center;">
                    <?php if ($onlyWrittenOff): ?>
                        <a href="/src/View/write_off.php" style="color:inherit;text-decoration:none;">← Все записи</a>
                    <?php else: ?>
                        <span>Клик по строке — детали; «История» — сдача/приём и затраты</span>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOpenRepairHistory"
                        title="История выбранного ТМЦ" disabled>
                        <i class="bi bi-clock-history"></i> История
                    </button>
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
                            <th>Статус на объекте</th>
                            <th>Проверка</th>
                            <th>Локация</th>
                            <th>№ счета</th>
                            <th>Сумма ремонта</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // debug: сколько групп перед отрисовкой
                        echo '<!-- written_off_groups=' . count($groupedItems) . ' -->';
                        foreach ($groupedItems as $id => $itemData):
                            try {
                            $mainItem = $itemData['main'] ?? null;
                            if (!$mainItem || empty($mainItem->InventoryItem)) {
                                echo '<!-- skip id=' . htmlspecialchars((string) $id) . ' no main/inventory -->';
                                continue;
                            }
                            $repairs = $itemData['repairs'] ?? [];
                            if (!is_array($repairs)) {
                                $repairs = is_iterable($repairs) ? iterator_to_array($repairs) : [];
                            }
                            $totalCost = 0;
                            $invoices = [];
                            $updList = [];
                            foreach ($repairs as $repair) {
                                $totalCost += (float) ($repair->RepairCost ?? 0);
                                if (repairHasInvoice($repair)) {
                                    $invoices[] = trim((string) $repair->InvoiceNumber);
                                }
                                $updVal = trim((string) ($repair->UPD ?? ''));
                                if ($updVal !== '') {
                                    $updList[] = $updVal;
                                }
                            }
                            $invoices = array_values(array_unique($invoices));
                            $updList = array_values(array_unique($updList));
                            $isVerified = repairsAreVerified($repairs);
                            $statusValue = (int) ($mainItem->InventoryItem->Status ?? -1);
                            $statusText = archiveObjectStatusLabel($statusValue);
                            $verificationText = $isVerified ? 'проверено' : 'ожидает счёт';
                            $statusClass = archiveObjectStatusClass($statusValue);
                            $brand = (string) ($mainItem->InventoryItem->BrandTMC?->NameBrand ?? '');
                            $locName = (string) ($mainItem->InventoryItem->Location?->NameLocation ?? '');
                            $fio = (string) ($mainItem->RegistrationInventoryItem?->User?->FIO ?? '');
                            $searchBlob = mb_strtolower(trim(implode(' ', [
                                (string) ($mainItem->ID_TMC ?? $id),
                                (string) ($mainItem->InventoryItem->NameTMC ?? ''),
                                (string) ($mainItem->InventoryItem->SerialNumber ?? ''),
                                $brand,
                                $locName,
                                (string) ($statusText ?? ''),
                                (string) $verificationText,
                                implode(' ', $invoices),
                                implode(' ', $updList),
                            ])));
                        ?>
                            <tr class="main-row" data-id="<?= (int) ($mainItem->ID_TMC ?? $id) ?>"
                                data-status="<?= $statusValue ?>"
                                data-verified="<?= $isVerified ? '1' : '0' ?>"
                                data-name="<?= htmlspecialchars($mainItem->InventoryItem->NameTMC ?? '') ?>"
                                data-location="<?= htmlspecialchars($locName) ?>"
                                data-total-cost="<?= $totalCost ?>"
                                data-search="<?= htmlspecialchars($searchBlob) ?>">
                                <td><span class="id-chip"><?= (int) ($mainItem->ID_TMC ?? $id) ?></span></td>
                                <td class="col-name"><?= htmlspecialchars($mainItem->InventoryItem->NameTMC ?? '') ?></td>
                                <td>
                                    <?php if ($brand !== ''): ?>
                                        <span class="brand-chip"><?= htmlspecialchars($brand) ?></span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-serial"><?= htmlspecialchars($mainItem->InventoryItem->SerialNumber ?? '') ?: '—' ?></td>
                                <td><?= htmlspecialchars($fio) ?: '—' ?></td>
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
                                <td><?= htmlspecialchars($locName) ?: '—' ?></td>
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
                                <td class="col-doc">
                                    <?php if ($updList): ?>
                                        <div class="doc-stack">
                                            <span class="doc-chip" title="<?= htmlspecialchars(implode(', ', $updList)) ?>">
                                                <?= htmlspecialchars($updList[0]) ?>
                                            </span>
                                            <?php if (count($updList) > 1): ?>
                                                <span class="doc-more">+<?= count($updList) - 1 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cost-cell"><?= number_format($totalCost, 2, ',', ' ') ?> ₽</td>
                                <td class="action-buttons" onclick="event.stopPropagation()">
                                    <?php if ($statusValue === StatusItem::ProposeWriteOff): ?>
                                        <?php
                                            $latestRepairId = 0;
                                            if (!empty($repairs)) {
                                                $last = end($repairs);
                                                $latestRepairId = (int) ($last->ID_Repair ?? 0);
                                            }
                                        ?>
                                        <button type="button" class="btn btn-sm btn-success propose-approve-btn"
                                            title="Утвердить списание"
                                            data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                            data-repair-id="<?= $latestRepairId ?>">
                                            Утвердить
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger propose-reject-btn"
                                            title="Отклонить предложение"
                                            data-tmc-id="<?= (int) $mainItem->ID_TMC ?>">
                                            Отклонить
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($statusValue === StatusItem::WrittenOff): ?>
                                        <button type="button" class="btn-action btn-restore restore-btn"
                                            title="Вернуть из списания" data-id="<?= $mainItem->ID_TMC ?>"
                                            onclick="returnToWorkTMC(<?= (int) $mainItem->ID_TMC ?>)">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn-action btn-history history-btn"
                                        title="История сдачи/приёма и затраты" data-id="<?= (int) $mainItem->ID_TMC ?>">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-edit edit-btn"
                                        title="Редактировать" data-id="<?= $mainItem->ID_TMC ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="repair-details-row" id="details-<?= $mainItem->ID_TMC ?>" style="display: none;">
                                <td colspan="12">
                                    <div class="repair-details">
                                        <div class="repair-details-head">
                                            <h6>История ремонтов</h6>
                                            <span class="details-count"><?= count($repairs) ?> записей</span>
                                        </div>
                                        <table class="table table-sm repair-table">
                                            <thead>
                                                <tr>
                                                    <th>№ счета</th>
                                                    <th>№ УПД</th>
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
                                                <?php foreach ($repairs as $repair):
                                                    $lineVerified = repairHasInvoice($repair);
                                                    $lineRepairId = (int) $repair->ID_Repair;
                                                    $lineLocationId = (int) ($repair->IDLocation ?? $repair->Location?->IDLocation ?? 0);
                                                    $lineUpd = trim((string) ($repair->UPD ?? ''));
                                                    $dateToVal = $repair->DateToService ? date('d.m.Y', strtotime($repair->DateToService)) : '';
                                                    $dateRetVal = $repair->DateReturnService ? date('d.m.Y', strtotime($repair->DateReturnService)) : '';
                                                    $isPropose = ($statusValue === StatusItem::ProposeWriteOff);
                                                ?>
                                                    <tr class="repair-line" data-repair-id="<?= $lineRepairId ?>"
                                                        data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                        data-location-id="<?= $lineLocationId ?>"
                                                        data-saved-date-to="<?= htmlspecialchars($dateToVal) ?>"
                                                        data-saved-date-return="<?= htmlspecialchars($dateRetVal) ?>"
                                                        data-saved-invoice="<?= htmlspecialchars(trim((string) ($repair->InvoiceNumber ?? ''))) ?>"
                                                        data-saved-upd="<?= htmlspecialchars($lineUpd) ?>"
                                                        data-saved-cost="<?= htmlspecialchars((string) (float) $repair->RepairCost) ?>">
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm repair-invoice-input"
                                                                placeholder="№ счёта"
                                                                value="<?= htmlspecialchars(trim((string) ($repair->InvoiceNumber ?? ''))) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm repair-upd-input"
                                                                placeholder="№ УПД"
                                                                value="<?= htmlspecialchars($lineUpd) ?>">
                                                        </td>
                                                        <td>
                                                            <?php if ($isPropose): ?>
                                                                <span class="status-badge status-propose-writeoff">Предложение</span>
                                                            <?php elseif ($lineVerified): ?>
                                                                <span class="status-badge status-verified">Проверено</span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-pending-invoice">Ожидает счёт</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0"
                                                                class="form-control form-control-sm repair-cost-input"
                                                                value="<?= (float) $repair->RepairCost ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm repair-date-to-input"
                                                                placeholder="дд.мм.гггг"
                                                                inputmode="numeric"
                                                                autocomplete="off"
                                                                value="<?= htmlspecialchars($dateToVal) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm repair-date-return-input"
                                                                placeholder="дд.мм.гггг"
                                                                inputmode="numeric"
                                                                autocomplete="off"
                                                                value="<?= htmlspecialchars($dateRetVal) ?>">
                                                        </td>
                                                        <td><?= htmlspecialchars($repair->RepairDescription ?? '') ?: '—' ?></td>
                                                        <td><?= htmlspecialchars($repair->Location?->NameLocation ?? '') ?: '—' ?></td>
                                                        <td class="action-buttons repair-actions" onclick="event.stopPropagation()">
                                                            <?php if ($isPropose): ?>
                                                                <button type="button"
                                                                    class="btn-action btn-delete repair-delete-btn"
                                                                    title="В корзину"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    <i class="bi bi-trash3"></i>
                                                                </button>
                                                            <?php elseif (!$lineVerified): ?>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-success repair-approve-btn"
                                                                    title="Согласовать ремонт"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    Согласовать
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger repair-reject-btn"
                                                                    title="Отказать"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    Отказать
                                                                </button>
                                                                <button type="button"
                                                                    class="btn-action btn-delete repair-delete-btn"
                                                                    title="В корзину"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    <i class="bi bi-trash3"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button"
                                                                    class="btn-action btn-edit repair-edit-btn"
                                                                    title="Изменить запись"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn-action btn-delete repair-delete-btn"
                                                                    title="В корзину"
                                                                    data-tmc-id="<?= (int) $mainItem->ID_TMC ?>"
                                                                    data-repair-id="<?= $lineRepairId ?>">
                                                                    <i class="bi bi-trash3"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            } catch (Throwable $e) {
                                echo '<!-- write_off row error id=' . htmlspecialchars((string) $id) . ': '
                                    . htmlspecialchars($e->getMessage()) . ' -->';
                                echo '<tr class="main-row" data-id="' . (int) $id . '" data-status="3" data-verified="0" data-name="" data-location="" data-total-cost="0" data-search="">';
                                echo '<td colspan="12" class="text-danger">Ошибка строки ТМЦ #'
                                    . (int) $id . ': ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                        endforeach;
                        ?>
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


    <script type="module" src="/js/writeOffFunctions.js?v=<?= @filemtime(__DIR__ . '/../../js/writeOffFunctions.js') ?: time() ?>"></script>
    <script src="/js/lib/bootstrap.bundle.min.js"></script>

    <script>
        // Глобальные переменные
        // не json_encode($groupedItems) — объекты Entity ломают скрипт (let allItems = ;)
        let selectedRow = null;
        window.selectedRow = null;
        let initialTotal = <?= (float) $totalRepairCost ?>;
        window.writeOffArchiveFilter = <?= json_encode($archiveFilter) ?>;

        // Функция применения фильтров
        function applyFilters() {
            const filters = {
                name: Array.from(document.querySelectorAll('input[data-filter="name"]:checked')).map(cb => cb.value),
                location: Array.from(document.querySelectorAll('input[data-filter="location"]:checked')).map(cb => cb.value)
            };
            const searchValue = (document.getElementById('writeOffSearchInput')?.value || '')
                .trim()
                .toLowerCase();
            const archiveFilter = window.writeOffArchiveFilter || '';

            const rows = document.querySelectorAll('.main-row');
            let visibleCount = 0;
            let filteredTotal = 0;

            rows.forEach(row => {
                let visible = true;
                const name = row.getAttribute('data-name');
                const location = row.getAttribute('data-location');
                const cost = parseFloat(row.getAttribute('data-total-cost'));
                const searchBlob = row.getAttribute('data-search') || '';
                const isVerified = row.getAttribute('data-verified') === '1';

                if (archiveFilter === 'verified' && !isVerified) {
                    visible = false;
                } else if (archiveFilter === 'pending' && isVerified) {
                    visible = false;
                } else if (archiveFilter === 'confirm') {
                    const st = parseInt(row.getAttribute('data-status') || '-1', 10);
                    if (st !== 2 && st !== 21) visible = false; // «В ремонте» + «Подтвердить ремонт»
                } else if (archiveFilter === 'propose') {
                    const st = parseInt(row.getAttribute('data-status') || '-1', 10);
                    if (st !== 22) visible = false;
                }

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

            const recordsStat = document.querySelector('.hero-stats .stat-card .stat-value');
            if (recordsStat && archiveFilter !== '') {
                recordsStat.textContent = String(visibleCount);
            }
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
            window.selectedRow = row;

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

            const histBtn = document.getElementById('btnOpenRepairHistory');
            if (histBtn) {
                histBtn.disabled = !id;
                histBtn.dataset.tmcId = id || '';
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
            const id = idFromBtn
                || (window.selectedRow ? window.selectedRow.getAttribute('data-id') : null)
                || document.querySelector('#writeOffTable tbody tr.main-row.selected')?.getAttribute('data-id');
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
                    if (!e.target.closest('.edit-btn, .restore-btn, .history-btn') && this.style.display !== 'none') {
                        selectRow(this);
                    }
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

            document.querySelectorAll('.repair-approve-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (typeof window.approveRepairLine === 'function') {
                        window.approveRepairLine(this);
                    }
                });
            });

            document.querySelectorAll('.repair-reject-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (typeof window.rejectRepairLine === 'function') {
                        window.rejectRepairLine(this);
                    }
                });
            });

            // даты — не давать клику по строке перехватывать фокус; сохранение в writeOffFunctions.js
            document.querySelectorAll('.repair-date-to-input, .repair-date-return-input').forEach(input => {
                input.addEventListener('mousedown', (e) => e.stopPropagation());
                input.addEventListener('click', (e) => e.stopPropagation());
                input.addEventListener('focus', (e) => e.stopPropagation());
            });

            document.querySelectorAll('.repair-invoice-input, .repair-upd-input, .repair-cost-input').forEach(input => {
                input.addEventListener('mousedown', (e) => e.stopPropagation());
                input.addEventListener('click', (e) => e.stopPropagation());
                input.addEventListener('focus', (e) => e.stopPropagation());
                input.addEventListener('blur', function() {
                    const row = this.closest('.repair-line');
                    if (row && typeof window.saveRepairLineFields === 'function') {
                        window.saveRepairLineFields(row);
                    }
                });
            });

            document.querySelectorAll('.propose-approve-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (typeof window.approveProposeWriteOff === 'function') {
                        window.approveProposeWriteOff(this);
                    }
                });
            });

            document.querySelectorAll('.propose-reject-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (typeof window.rejectProposeWriteOff === 'function') {
                        window.rejectProposeWriteOff(this);
                    }
                });
            });

            document.querySelectorAll('.history-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-id');
                    if (id && typeof window.openRepairHistory === 'function') {
                        window.openRepairHistory(id);
                    }
                });
            });

            const toolbarHist = document.getElementById('btnOpenRepairHistory');
            if (toolbarHist) {
                toolbarHist.addEventListener('click', function() {
                    const id = this.dataset.tmcId
                        || document.querySelector('#writeOffTable tbody tr.main-row.selected')?.getAttribute('data-id');
                    if (!id) {
                        showNotification(TypeMessage.notification, 'Выберите ТМЦ в таблице или найдите через поиск');
                        return;
                    }
                    if (typeof window.openRepairHistory === 'function') {
                        window.openRepairHistory(id);
                    }
                });
            }

            applyFilters();
        });
    </script>

    <script type="module" src="/js/modals/modalLoader.js"></script>
    <script type="module" src="/js/modals/repairBasketModal.js"></script>

    <div class="modal fade" id="repairHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock-history"></i> История ремонта</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="repairHistoryModalBody">
                    <div class="text-muted">Загрузка…</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalContainer"></div>
</body>

</html>