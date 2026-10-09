<?php
session_start();
if (!isset($_SESSION['IDUser'])) {
    header('Location: index.php');
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/analytics.log');
@set_time_limit(120);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../BusinessLogic/ItemRepairController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();

$container = new ItemController();
$repairContainer = new ItemRepairController();
$statusUser = $_SESSION["Status"];

// Получение данных для фильтров
$inventoryItems = $container->getInventoryItems($_SESSION["Status"], $_SESSION["IDUser"]);
$brands = [];
$models = [];
$locations = [];
$suppliers = [];
$names = [];

if ($inventoryItems) {
    foreach ($inventoryItems as $item) {
        if (!in_array($item->NameTMC, $names)) {
            $names[] = $item->NameTMC;
        }
        if ($item->BrandTMC && !in_array($item->BrandTMC->NameBrand, $brands)) {
            $brands[] = $item->BrandTMC->NameBrand;
        }
        if ($item->ModelTMC && !in_array($item->ModelTMC->NameModel, $models)) {
            $models[] = $item->ModelTMC->NameModel;
        }
        // только объекты (не сервис/поставщики)
        if ($item->Location && empty($item->Location->IsRepair) && !in_array($item->Location->NameLocation, $locations)) {
            $locations[] = $item->Location->NameLocation;
        }
    }
}

sort($names);
sort($brands);
sort($models);
sort($locations);

// Затраты на ремонт для финансового дашборда
$repairSpendRows = [];
try {
    $repairSpendRows = $repairContainer->getRepairSpendForAnalytics();
} catch (Throwable $e) {
    error_log('analytics repair spend: ' . $e->getMessage());
}

// Дополняем фильтры значениями из ремонтов
foreach ($repairSpendRows as $row) {
    if (!empty($row['name']) && !in_array($row['name'], $names, true)) {
        $names[] = $row['name'];
    }
    if (!empty($row['brand']) && !in_array($row['brand'], $brands, true)) {
        $brands[] = $row['brand'];
    }
    if (!empty($row['model']) && !in_array($row['model'], $models, true)) {
        $models[] = $row['model'];
    }
    // объекты — только в локации
    if (!empty($row['location']) && !in_array($row['location'], $locations, true)) {
        $locations[] = $row['location'];
    }
    // сервис (IsRepair=1) — только в поставщики
    if (!empty($row['supplier']) && !in_array($row['supplier'], $suppliers, true)) {
        $suppliers[] = $row['supplier'];
    }
}

// поставщики из справочника (IsRepair=1)
try {
    $serviceLocations = $container->getLocations(true);
    if ($serviceLocations) {
        foreach ($serviceLocations as $loc) {
            $name = trim((string) ($loc->NameLocation ?? ''));
            if ($name !== '' && !in_array($name, $suppliers, true)) {
                $suppliers[] = $name;
            }
        }
    }
} catch (Throwable $e) {
    error_log('analytics suppliers: ' . $e->getMessage());
}

sort($names);
sort($brands);
sort($models);
sort($locations);
sort($suppliers);

// Лёгкий JSON для графиков (без полного toArray сущностей)
$parkRows = [];
if ($inventoryItems) {
    foreach ($inventoryItems as $item) {
        $parkRows[] = [
            'name' => (string) ($item->NameTMC ?? ''),
            'brand' => (string) ($item->BrandTMC?->NameBrand ?? ''),
            'model' => (string) ($item->ModelTMC?->NameModel ?? ''),
            'location' => (string) ($item->Location?->NameLocation ?? ''),
            'status' => (int) ($item->Status ?? 0),
        ];
    }
}

$defaultDateTo = date('Y-m-d');
$defaultDateFrom = date('Y-m-d', strtotime('-12 months'));
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика ТМЦ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Локальный Chart.js: CDN часто падает (QUIC/сеть) → Chart is not defined -->
    <script src="/js/lib/chart.umd.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding-bottom: 20px;
        }
        .header-section {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .filter-group {
            margin-bottom: 15px;
            position: relative;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .filter-search {
            position: relative;
            margin-bottom: 10px;
        }
        .filter-search input {
            padding-right: 40px;
        }
        .filter-search-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .filter-search-clear:hover {
            color: #dc3545;
        }
        .filter-options {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
        }
        .filter-option {
            margin-bottom: 5px;
        }
        .scrollable-list {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .color-badge {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .exit-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #343a40;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        .analytics-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .analytics-tab {
            border: 1px solid #dee2e6;
            background: #fff;
            color: #495057;
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            text-decoration: none;
        }
        .analytics-tab.active {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }
        .analytics-panel { display: none; }
        .analytics-panel.active { display: block; }
        .finance-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .finance-kpi {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 14px 16px;
        }
        .finance-kpi .label {
            display: block;
            font-size: 0.78rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .finance-kpi .value {
            font-size: 1.35rem;
            font-weight: 750;
            color: #212529;
        }
        .finance-kpi.accent {
            background: linear-gradient(135deg, #0f766e, #0d9488);
            color: #fff;
        }
        .finance-kpi.accent .label { color: rgba(255,255,255,0.85); }
        .finance-kpi.accent .value { color: #fff; }
        .date-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
            margin-bottom: 16px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .date-filters label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
            display: block;
        }
        .date-filters .date-field { min-width: 160px; }
        .spend-table-wrap {
            max-height: 420px;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        #spendTable th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 1;
        }
        @media (max-width: 992px) {
            .finance-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 576px) {
            .finance-kpis { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <!-- Кнопка выхода -->
    <a href="home.php" class="btn btn-primary exit-btn">
        <i class="bi bi-arrow-left"></i> На главную
    </a>

    <div class="container-fluid py-4">
        <div class="analytics-tabs">
            <button type="button" class="analytics-tab active" data-panel="park">Парк ТМЦ</button>
            <button type="button" class="analytics-tab" data-panel="finance">Финансы / ремонт</button>
        </div>

        <!-- Фильтры -->
        <div class="filter-section">
            <h4 class="mb-2">Фильтры</h4>
            <div class="date-filters" id="financeDateFilters" style="display:none;">
                <div class="date-field">
                    <label for="spendDateFrom">Дата трат с</label>
                    <input type="date" id="spendDateFrom" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($defaultDateFrom) ?>">
                </div>
                <div class="date-field">
                    <label for="spendDateTo">Дата трат по</label>
                    <input type="date" id="spendDateTo" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($defaultDateTo) ?>">
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="spendDateClear">
                        Сбросить период
                    </button>
                </div>
                <div class="ms-auto text-muted small align-self-center">
                    Период по дате отправки в сервис (DateToService)
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-lg">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Наименование</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="name">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..." data-filter="name">
                            <span class="filter-search-clear" data-filter="name">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="name-options">
                            <?php foreach ($names as $name): ?>
                            <div class="form-check filter-option">
                                <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($name) ?>" id="name-<?= md5($name) ?>" data-filter="name">
                                <label class="form-check-label" for="name-<?= md5($name) ?>">
                                    <?= htmlspecialchars($name) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Бренд</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="brand">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..." data-filter="brand">
                            <span class="filter-search-clear" data-filter="brand">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="brand-options">
                            <?php foreach ($brands as $brand): ?>
                            <div class="form-check filter-option">
                                <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($brand) ?>" id="brand-<?= md5($brand) ?>" data-filter="brand">
                                <label class="form-check-label" for="brand-<?= md5($brand) ?>">
                                    <?= htmlspecialchars($brand) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Модель</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="model">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..." data-filter="model">
                            <span class="filter-search-clear" data-filter="model">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="model-options">
                            <?php foreach ($models as $model): ?>
                            <div class="form-check filter-option">
                                <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($model) ?>" id="model-<?= md5($model) ?>" data-filter="model">
                                <label class="form-check-label" for="model-<?= md5($model) ?>">
                                    <?= htmlspecialchars($model) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Локация</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="location">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..." data-filter="location">
                            <span class="filter-search-clear" data-filter="location">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="location-options">
                            <?php foreach ($locations as $location): ?>
                            <div class="form-check filter-option">
                                <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($location) ?>" id="location-<?= md5($location) ?>" data-filter="location">
                                <label class="form-check-label" for="location-<?= md5($location) ?>">
                                    <?= htmlspecialchars($location) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg" id="supplierFilterCol">
                    <div class="filter-group">
                        <div class="filter-header">
                            <h5>Поставщик</h5>
                            <button class="btn btn-sm btn-outline-secondary clear-filter" data-filter="supplier">
                                <i class="bi bi-x-lg"></i> Очистить
                            </button>
                        </div>
                        <div class="filter-search">
                            <input type="text" class="form-control form-control-sm search-input" placeholder="Поиск..." data-filter="supplier">
                            <span class="filter-search-clear" data-filter="supplier">
                                <i class="bi bi-x"></i>
                            </span>
                        </div>
                        <div class="filter-options" id="supplier-options">
                            <?php foreach ($suppliers as $supplier): ?>
                            <div class="form-check filter-option">
                                <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($supplier) ?>" id="supplier-<?= md5($supplier) ?>" data-filter="supplier">
                                <label class="form-check-label" for="supplier-<?= md5($supplier) ?>">
                                    <?= htmlspecialchars($supplier) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Парк ТМЦ -->
        <div class="analytics-panel active" id="panel-park">
        <div class="chart-section">
            <!-- Первый ряд: Бренды и модели -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Распределение по брендам</div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="brandChart"></canvas>
                            </div>
                            <div class="chart-title">Детализация по брендам</div>
                            <div class="scrollable-list" id="brandList"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Распределение по моделям</div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="modelChart"></canvas>
                            </div>
                            <div class="chart-title">Детализация по моделям</div>
                            <div class="scrollable-list" id="modelList"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Второй ряд: Локации и списанные ТМЦ -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Распределение по локациям</div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="locationChart"></canvas>
                            </div>
                            <div class="chart-title">Детализация по локациям</div>
                            <div class="scrollable-list" id="locationList"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Списанные ТМЦ по локациям</div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="writtenOffChart"></canvas>
                            </div>
                            <div class="chart-title">Детализация списанных ТМЦ</div>
                            <div class="scrollable-list" id="writtenOffList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Финансы / ремонт -->
        <div class="analytics-panel" id="panel-finance">
            <div class="finance-kpis">
                <div class="finance-kpi accent">
                    <span class="label">Сумма ремонта за период</span>
                    <span class="value" id="kpiTotalSpend">0,00 ₽</span>
                </div>
                <div class="finance-kpi">
                    <span class="label">Записей ремонта</span>
                    <span class="value" id="kpiRepairCount">0</span>
                </div>
                <div class="finance-kpi">
                    <span class="label">Средний чек</span>
                    <span class="value" id="kpiAvgSpend">0,00 ₽</span>
                </div>
                <div class="finance-kpi">
                    <span class="label">Со счётом</span>
                    <span class="value" id="kpiWithInvoice">0</span>
                </div>
            </div>
            <div class="chart-section">
                <div class="row mb-4">
                    <div class="col-md-7">
                        <div class="card h-100">
                            <div class="card-header">Траты на ремонт по месяцам</div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="spendByMonthChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-header">Траты по поставщикам</div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="spendBySupplierChart"></canvas>
                                </div>
                                <div class="chart-title">Детализация</div>
                                <div class="scrollable-list" id="spendSupplierList"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Даты и суммы трат</span>
                        <span class="text-muted small" id="spendTableCount">0 записей</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="spend-table-wrap">
                            <table class="table table-sm table-hover mb-0" id="spendTable">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>ID</th>
                                        <th>Наименование</th>
                                        <th>Бренд</th>
                                        <th>Локация</th>
                                        <th>Поставщик</th>
                                        <th>Счёт</th>
                                        <th class="text-end">Сумма</th>
                                    </tr>
                                </thead>
                                <tbody id="spendTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Глобальные переменные для хранения данных и диаграмм
        let brandChart, modelChart, locationChart, writtenOffChart;
        let spendByMonthChart, spendBySupplierChart;
        let brandColors = {}, modelColors = {}, locationColors = {}, writtenOffColors = {};
        let allData = <?= json_encode($parkRows, JSON_UNESCAPED_UNICODE) ?>;
        let allSpendData = <?= json_encode($repairSpendRows, JSON_UNESCAPED_UNICODE) ?>;
        let activePanel = 'park';
        let chartLibWarned = false;

        function ensureChartLib() {
            if (typeof Chart !== 'undefined') return true;
            if (!chartLibWarned) {
                chartLibWarned = true;
                console.error('Chart.js не загружен (/js/lib/chart.umd.min.js)');
                const banner = document.createElement('div');
                banner.className = 'alert alert-danger mx-3';
                banner.textContent = 'Не удалось загрузить библиотеку графиков. Обновите страницу или проверьте файл /js/lib/chart.umd.min.js';
                document.querySelector('.container-fluid')?.prepend(banner);
            }
            return false;
        }

        function getCommonFilters() {
            return {
                name: Array.from(document.querySelectorAll('input[data-filter="name"]:checked')).map(cb => cb.value),
                brand: Array.from(document.querySelectorAll('input[data-filter="brand"]:checked')).map(cb => cb.value),
                model: Array.from(document.querySelectorAll('input[data-filter="model"]:checked')).map(cb => cb.value),
                location: Array.from(document.querySelectorAll('input[data-filter="location"]:checked')).map(cb => cb.value),
                supplier: Array.from(document.querySelectorAll('input[data-filter="supplier"]:checked')).map(cb => cb.value)
            };
        }

        function formatMoney(value) {
            return Number(value || 0).toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ₽';
        }

        function formatDateRu(iso) {
            if (!iso) return '—';
            const parts = String(iso).split('-');
            if (parts.length !== 3) return iso;
            return parts[2] + '.' + parts[1] + '.' + parts[0];
        }

        // Функция применения фильтров
        function applyFilters() {
            const filters = getCommonFilters();

            let filteredData = allData.filter(item => {
                if (filters.name.length > 0 && !filters.name.includes(item.name)) return false;
                if (filters.brand.length > 0 && !filters.brand.includes(item.brand)) return false;
                if (filters.model.length > 0 && !filters.model.includes(item.model)) return false;
                if (filters.location.length > 0 && !filters.location.includes(item.location)) return false;
                return true;
            });

            updateCharts(filteredData);
            updateFinanceDashboard();
        }

        function getFilteredSpendData() {
            const filters = getCommonFilters();
            const dateFrom = document.getElementById('spendDateFrom')?.value || '';
            const dateTo = document.getElementById('spendDateTo')?.value || '';

            return (allSpendData || []).filter(row => {
                if (filters.name.length > 0 && !filters.name.includes(row.name)) return false;
                if (filters.brand.length > 0 && !filters.brand.includes(row.brand)) return false;
                if (filters.model.length > 0 && !filters.model.includes(row.model)) return false;
                if (filters.location.length > 0 && !filters.location.includes(row.location)) return false;
                if (filters.supplier.length > 0 && !filters.supplier.includes(row.supplier)) return false;
                if (dateFrom && (!row.date || row.date < dateFrom)) return false;
                if (dateTo && (!row.date || row.date > dateTo)) return false;
                return true;
            });
        }

        function updateFinanceDashboard() {
            const rows = getFilteredSpendData();
            const total = rows.reduce((sum, r) => sum + (Number(r.cost) || 0), 0);
            const withInvoice = rows.filter(r => (r.invoice || '').trim() !== '').length;
            const avg = rows.length ? total / rows.length : 0;

            document.getElementById('kpiTotalSpend').textContent = formatMoney(total);
            document.getElementById('kpiRepairCount').textContent = String(rows.length);
            document.getElementById('kpiAvgSpend').textContent = formatMoney(avg);
            document.getElementById('kpiWithInvoice').textContent = String(withInvoice);
            document.getElementById('spendTableCount').textContent = rows.length + ' записей';

            updateSpendByMonthChart(rows);
            updateSpendBySupplierChart(rows);
            updateSpendTable(rows);
        }

        function updateSpendByMonthChart(rows) {
            if (!ensureChartLib()) return;
            const byMonth = {};
            rows.forEach(row => {
                if (!row.date) return;
                const key = row.date.slice(0, 7); // YYYY-MM
                byMonth[key] = (byMonth[key] || 0) + (Number(row.cost) || 0);
            });
            const labels = Object.keys(byMonth).sort();
            const values = labels.map(k => byMonth[k]);
            const labelRu = labels.map(k => {
                const [y, m] = k.split('-');
                return m + '.' + y;
            });

            if (spendByMonthChart) spendByMonthChart.destroy();
            const canvas = document.getElementById('spendByMonthChart');
            if (!canvas) return;
            spendByMonthChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labelRu,
                    datasets: [{
                        label: 'Сумма ремонта, ₽',
                        data: values,
                        backgroundColor: 'rgba(15, 118, 110, 0.7)',
                        borderColor: '#0f766e',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => Number(v).toLocaleString('ru-RU')
                            }
                        }
                    }
                }
            });
        }

        function updateSpendBySupplierChart(rows) {
            if (!ensureChartLib()) return;
            const bySupplier = {};
            rows.forEach(row => {
                const name = row.supplier || 'не указан';
                bySupplier[name] = (bySupplier[name] || 0) + (Number(row.cost) || 0);
            });
            const entries = Object.entries(bySupplier).sort((a, b) => b[1] - a[1]);
            const labels = entries.map(e => e[0]);
            const values = entries.map(e => e[1]);
            const colors = generateColors(labels.length);
            const total = values.reduce((s, v) => s + v, 0) || 1;

            if (spendBySupplierChart) spendBySupplierChart.destroy();
            const canvas = document.getElementById('spendBySupplierChart');
            if (!canvas) return;
            spendBySupplierChart = new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: values, backgroundColor: colors }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            const list = document.getElementById('spendSupplierList');
            list.innerHTML = '';
            entries.forEach(([name, sum], i) => {
                const pct = ((sum / total) * 100).toFixed(1);
                list.innerHTML += `
                    <div class="list-item">
                        <div>
                            <span class="color-badge" style="background-color: ${colors[i]}"></span>
                            <span>${name}</span>
                        </div>
                        <div>${formatMoney(sum)} (${pct}%)</div>
                    </div>`;
            });
        }

        function updateSpendTable(rows) {
            const tbody = document.getElementById('spendTableBody');
            if (!tbody) return;
            const sorted = [...rows].sort((a, b) => String(b.date || '').localeCompare(String(a.date || '')));
            if (sorted.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Нет трат за выбранный период / фильтры</td></tr>';
                return;
            }
            tbody.innerHTML = sorted.map(row => `
                <tr>
                    <td>${formatDateRu(row.date)}</td>
                    <td>${row.idTmc || '—'}</td>
                    <td>${escapeHtml(row.name || '—')}</td>
                    <td>${escapeHtml(row.brand || '—')}</td>
                    <td>${escapeHtml(row.location || '—')}</td>
                    <td>${escapeHtml(row.supplier || '—')}</td>
                    <td>${escapeHtml(row.invoice || '—')}</td>
                    <td class="text-end fw-semibold">${formatMoney(row.cost)}</td>
                </tr>
            `).join('');
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function switchPanel(panel) {
            activePanel = panel;
            document.querySelectorAll('.analytics-tab').forEach(tab => {
                tab.classList.toggle('active', tab.getAttribute('data-panel') === panel);
            });
            document.querySelectorAll('.analytics-panel').forEach(el => {
                el.classList.toggle('active', el.id === 'panel-' + panel);
            });
            const dateFilters = document.getElementById('financeDateFilters');
            if (dateFilters) {
                dateFilters.style.display = panel === 'finance' ? 'flex' : 'none';
            }
            if (panel === 'finance') {
                updateFinanceDashboard();
            }
        }

        // Функция обновления диаграмм
        function updateCharts(data) {
            if (!ensureChartLib()) return;
            updateBrandChart(data);
            updateModelChart(data);
            updateLocationChart(data);
            updateWrittenOffChart(data);
        }

        // Функции обновления конкретных диаграмм
        function updateBrandChart(data) {
            if (!ensureChartLib()) return;
            const brandCounts = {};
            data.forEach(item => {
                if (item.brand) {
                    brandCounts[item.brand] = (brandCounts[item.brand] || 0) + 1;
                }
            });

            const brands = Object.keys(brandCounts);
            const counts = Object.values(brandCounts);

            // Генерация цветов
            const colors = generateColors(brands.length);
            brands.forEach((brand, index) => {
                brandColors[brand] = colors[index];
            });

            // Обновление круговой диаграммы
            if (brandChart) brandChart.destroy();
            
            const ctx = document.getElementById('brandChart').getContext('2d');
            brandChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: brands,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Обновление списка
            const brandList = document.getElementById('brandList');
            brandList.innerHTML = '';
            brands.forEach((brand, index) => {
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
                brandList.innerHTML += `
                    <div class="list-item">
                        <div>
                            <span class="color-badge" style="background-color: ${colors[index]}"></span>
                            <span>${brand}</span>
                        </div>
                        <div>${counts[index]} (${percent}%)</div>
                    </div>`;
            });
        }

        function updateModelChart(data) {
            if (!ensureChartLib()) return;
            const modelCounts = {};
            data.forEach(item => {
                if (item.model) {
                    modelCounts[item.model] = (modelCounts[item.model] || 0) + 1;
                }
            });

            const models = Object.keys(modelCounts);
            const counts = Object.values(modelCounts);

            // Генерация цветов
            const colors = generateColors(models.length);
            models.forEach((model, index) => {
                modelColors[model] = colors[index];
            });

            // Обновление круговой диаграммы
            if (modelChart) modelChart.destroy();
            
            const ctx = document.getElementById('modelChart').getContext('2d');
            modelChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: models,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Обновление списка
            const modelList = document.getElementById('modelList');
            modelList.innerHTML = '';
            models.forEach((model, index) => {
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
                modelList.innerHTML += `
                    <div class="list-item">
                        <div>
                            <span class="color-badge" style="background-color: ${colors[index]}"></span>
                            <span>${model}</span>
                        </div>
                        <div>${counts[index]} (${percent}%)</div>
                    </div>`;
            });
        }

        function updateLocationChart(data) {
            if (!ensureChartLib()) return;
            const locationCounts = {};
            data.forEach(item => {
                if (item.location) {
                    locationCounts[item.location] = (locationCounts[item.location] || 0) + 1;
                }
            });

            const locations = Object.keys(locationCounts);
            const counts = Object.values(locationCounts);

            // Генерация цветов
            const colors = generateColors(locations.length);
            locations.forEach((location, index) => {
                locationColors[location] = colors[index];
            });

            // Обновление круговой диаграммы
            if (locationChart) locationChart.destroy();
            
            const ctx = document.getElementById('locationChart').getContext('2d');
            locationChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: locations,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Обновление списка
            const locationList = document.getElementById('locationList');
            locationList.innerHTML = '';
            locations.forEach((location, index) => {
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
                locationList.innerHTML += `
                    <div class="list-item">
                        <div>
                            <span class="color-badge" style="background-color: ${colors[index]}"></span>
                            <span>${location}</span>
                        </div>
                        <div>${counts[index]} (${percent}%)</div>
                    </div>`;
            });
        }

        function updateWrittenOffChart(data) {
            if (!ensureChartLib()) return;
            const writtenOffCounts = {};
            data.forEach(item => {
                // StatusItem::WrittenOff = 3
                if (item.location && Number(item.status) === 3) {
                    writtenOffCounts[item.location] = (writtenOffCounts[item.location] || 0) + 1;
                }
            });

            const locations = Object.keys(writtenOffCounts);
            const counts = Object.values(writtenOffCounts);

            // Генерация цветов
            const colors = generateColors(locations.length);
            locations.forEach((location, index) => {
                writtenOffColors[location] = colors[index];
            });

            // Обновление круговой диаграммы
            if (writtenOffChart) writtenOffChart.destroy();
            
            const ctx = document.getElementById('writtenOffChart').getContext('2d');
            writtenOffChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: locations,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Обновление списка
            const writtenOffList = document.getElementById('writtenOffList');
            writtenOffList.innerHTML = '';
            locations.forEach((location, index) => {
                const percent = data.length ? ((counts[index] / data.length) * 100).toFixed(1) : '0.0';
                writtenOffList.innerHTML += `
                    <div class="list-item">
                        <div>
                            <span class="color-badge" style="background-color: ${colors[index]}"></span>
                            <span>${location}</span>
                        </div>
                        <div>${counts[index]} (${percent}%)</div>
                    </div>`;
            });
        }

        // Вспомогательная функция для генерации цветов
        function generateColors(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const hue = (i * 360 / Math.max(count, 1)) % 360;
                colors.push(`hsl(${hue}, 70%, 60%)`);
            }
            return colors;
        }

        // Функция поиска в фильтрах
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

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация диаграмм с полными данными
            updateCharts(allData);
            updateFinanceDashboard();
            
            // Добавление обработчиков событий для фильтров
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            document.getElementById('spendDateFrom')?.addEventListener('change', updateFinanceDashboard);
            document.getElementById('spendDateTo')?.addEventListener('change', updateFinanceDashboard);
            document.getElementById('spendDateClear')?.addEventListener('click', function() {
                const from = document.getElementById('spendDateFrom');
                const to = document.getElementById('spendDateTo');
                if (from) from.value = '';
                if (to) to.value = '';
                updateFinanceDashboard();
            });

            document.querySelectorAll('.analytics-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchPanel(this.getAttribute('data-panel'));
                });
            });
            
            // Настройка поиска в фильтрах
            setupFilterSearch();
        });
    </script>
</body>

</html>