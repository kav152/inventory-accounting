<?php
session_start();
if (!isset($_SESSION['IDUser'])) {
    header('Location: index.php');
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/analytics.log');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();

$container = new ItemController();
$statusUser = $_SESSION["Status"];

// Получение данных для фильтров
$inventoryItems = $container->getInventoryItems($_SESSION["Status"], $_SESSION["IDUser"]);
$brands = [];
$models = [];
$locations = [];
$names = [];

if ($inventoryItems) {
    foreach ($inventoryItems as $item) {
        // Собираем уникальные наименования
        if (!in_array($item->NameTMC, $names)) {
            $names[] = $item->NameTMC;
        }
        
        // Собираем уникальные бренды
        if ($item->BrandTMC && !in_array($item->BrandTMC->NameBrand, $brands)) {
            $brands[] = $item->BrandTMC->NameBrand;
        }
        
        // Собираем уникальные модели
        if ($item->ModelTMC && !in_array($item->ModelTMC->NameModel, $models)) {
            $models[] = $item->ModelTMC->NameModel;
        }
        
        // Собираем уникальные локации
        if ($item->Location && !in_array($item->Location->NameLocation, $locations)) {
            $locations[] = $item->Location->NameLocation;
        }
    }
}

// Сортировка данных для фильтров
sort($names);
sort($brands);
sort($models);
sort($locations);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика ТМЦ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>

<body>
    <!-- Кнопка выхода -->
    <a href="home.php" class="btn btn-primary exit-btn">
        <i class="bi bi-arrow-left"></i> На главную
    </a>

    <div class="container-fluid py-4">
        <!-- Заголовок -->
        <!--div class="header-section">
            <h1 class="mb-0">Аналитика ТМЦ</h1>
        </div-->
        
        <!-- Фильтры -->
        <div class="filter-section">
            <h4 class="mb-2">Фильтры</h4>
            <div class="row">
                <div class="col-md-3">
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
                
                <div class="col-md-3">
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
                
                <div class="col-md-3">
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
                
                <div class="col-md-3">
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
            </div>
        </div>

        <!-- Диаграммы -->
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

    <script>
        // Глобальные переменные для хранения данных и диаграмм
        let brandChart, modelChart, locationChart, writtenOffChart;
        let brandColors = {}, modelColors = {}, locationColors = {}, writtenOffColors = {};
        let allData = <?= json_encode($inventoryItems ? $inventoryItems->toArray() : []) ?>;

        // Функция применения фильтров
        function applyFilters() {
            const filters = {
                name: Array.from(document.querySelectorAll('input[data-filter="name"]:checked')).map(cb => cb.value),
                brand: Array.from(document.querySelectorAll('input[data-filter="brand"]:checked')).map(cb => cb.value),
                model: Array.from(document.querySelectorAll('input[data-filter="model"]:checked')).map(cb => cb.value),
                location: Array.from(document.querySelectorAll('input[data-filter="location"]:checked')).map(cb => cb.value)
            };

            // Фильтрация данных
            let filteredData = allData.filter(item => {
                if (filters.name.length > 0 && !filters.name.includes(item.NameTMC)) return false;
                if (filters.brand.length > 0 && (!item.BrandTMC || !filters.brand.includes(item.BrandTMC.NameBrand))) return false;
                if (filters.model.length > 0 && (!item.ModelTMC || !filters.model.includes(item.ModelTMC.NameModel))) return false;
                if (filters.location.length > 0 && (!item.Location || !filters.location.includes(item.Location.NameLocation))) return false;
                return true;
            });

            // Обновление диаграмм
            updateCharts(filteredData);
        }

        // Функция обновления диаграмм
        function updateCharts(data) {
            updateBrandChart(data);
            updateModelChart(data);
            updateLocationChart(data);
            updateWrittenOffChart(data);
        }

        // Функции обновления конкретных диаграмм
        function updateBrandChart(data) {
            const brandCounts = {};
            data.forEach(item => {
                if (item.BrandTMC) {
                    const brandName = item.BrandTMC.NameBrand;
                    brandCounts[brandName] = (brandCounts[brandName] || 0) + 1;
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
            const modelCounts = {};
            data.forEach(item => {
                if (item.ModelTMC) {
                    const modelName = item.ModelTMC.NameModel;
                    modelCounts[modelName] = (modelCounts[modelName] || 0) + 1;
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
            const locationCounts = {};
            data.forEach(item => {
                if (item.Location) {
                    const locationName = item.Location.NameLocation;
                    locationCounts[locationName] = (locationCounts[locationName] || 0) + 1;
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
            const writtenOffCounts = {};
            data.forEach(item => {
                if (item.Location && item.Status === 5) { // Предположим, что статус 5 = "Списано"
                    const locationName = item.Location.NameLocation;
                    writtenOffCounts[locationName] = (writtenOffCounts[locationName] || 0) + 1;
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
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
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
                const hue = (i * 360 / count) % 360;
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
            
            // Добавление обработчиков событий для фильтров
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });
            
            // Настройка поиска в фильтрах
            setupFilterSearch();
        });
    </script>
</body>

</html>