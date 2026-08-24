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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .filter-column {
            max-height: 200px;
            overflow-y: auto;
        }
        .scrollable-list {
            max-height: 250px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
       
        <!-- Фильтры -->
        <div class="row filter-section">
            <div class="col-md-3">
                <h5>Наименование</h5>
                <div class="filter-column">
                    <?php foreach ($names as $name): ?>
                    <div class="form-check">
                        <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($name) ?>" id="name-<?= md5($name) ?>" data-filter="name">
                        <label class="form-check-label" for="name-<?= md5($name) ?>">
                            <?= htmlspecialchars($name) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <h5>Бренд</h5>
                <div class="filter-column">
                    <?php foreach ($brands as $brand): ?>
                    <div class="form-check">
                        <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($brand) ?>" id="brand-<?= md5($brand) ?>" data-filter="brand">
                        <label class="form-check-label" for="brand-<?= md5($brand) ?>">
                            <?= htmlspecialchars($brand) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <h5>Модель</h5>
                <div class="filter-column">
                    <?php foreach ($models as $model): ?>
                    <div class="form-check">
                        <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($model) ?>" id="model-<?= md5($model) ?>" data-filter="model">
                        <label class="form-check-label" for="model-<?= md5($model) ?>">
                            <?= htmlspecialchars($model) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <h5>Локация</h5>
                <div class="filter-column">
                    <?php foreach ($locations as $location): ?>
                    <div class="form-check">
                        <input class="form-check-input filter-checkbox" type="checkbox" value="<?= htmlspecialchars($location) ?>" id="location-<?= md5($location) ?>" data-filter="location">
                        <label class="form-check-label" for="location-<?= md5($location) ?>">
                            <?= htmlspecialchars($location) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Диаграммы -->
        <div class="row">
            <!-- Первый ряд: Бренды и модели -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Распределение по брендам</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="brandChart"></canvas>
                        </div>
                        <div class="scrollable-list mt-3" id="brandList"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Распределение по моделям</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="modelChart"></canvas>
                        </div>
                        <div class="scrollable-list mt-3" id="modelList"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Второй ряд: Локации и списанные ТМЦ -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Распределение по локациям</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="locationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Списанные ТМЦ по локациям</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="writtenOffChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Глобальные переменные для хранения данных и диаграмм
        let brandChart, modelChart, locationChart, writtenOffChart;
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

            // Обновление круговой диаграммы
            if (brandChart) brandChart.destroy();
            
            const ctx = document.getElementById('brandChart').getContext('2d');
            brandChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: brands,
                    datasets: [{
                        data: counts,
                        backgroundColor: generateColors(brands.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });

            // Обновление списка
            const brandList = document.getElementById('brandList');
            brandList.innerHTML = '';
            brands.forEach((brand, index) => {
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
                brandList.innerHTML += `<div class="d-flex justify-content-between"><span>${brand}</span><span>${counts[index]} (${percent}%)</span></div>`;
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

            // Обновление круговой диаграммы
            if (modelChart) modelChart.destroy();
            
            const ctx = document.getElementById('modelChart').getContext('2d');
            modelChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: models,
                    datasets: [{
                        data: counts,
                        backgroundColor: generateColors(models.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });

            // Обновление списка
            const modelList = document.getElementById('modelList');
            modelList.innerHTML = '';
            models.forEach((model, index) => {
                const percent = ((counts[index] / data.length) * 100).toFixed(1);
                modelList.innerHTML += `<div class="d-flex justify-content-between"><span>${model}</span><span>${counts[index]} (${percent}%)</span></div>`;
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

            // Обновление круговой диаграммы
            if (locationChart) locationChart.destroy();
            
            const ctx = document.getElementById('locationChart').getContext('2d');
            locationChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: locations,
                    datasets: [{
                        data: counts,
                        backgroundColor: generateColors(locations.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function updateWrittenOffChart(data) {
            // Здесь нужна дополнительная логика для получения списанных ТМЦ
            // В данном примере просто используем те же данные, но в реальности нужно фильтровать по статусу "Списано"
            const writtenOffCounts = {};
            data.forEach(item => {
                if (item.Location && item.Status === 5) { // Предположим, что статус 5 = "Списано"
                    const locationName = item.Location.NameLocation;
                    writtenOffCounts[locationName] = (writtenOffCounts[locationName] || 0) + 1;
                }
            });

            const locations = Object.keys(writtenOffCounts);
            const counts = Object.values(writtenOffCounts);

            // Обновление круговой диаграммы
            if (writtenOffChart) writtenOffChart.destroy();
            
            const ctx = document.getElementById('writtenOffChart').getContext('2d');
            writtenOffChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: locations,
                    datasets: [{
                        data: counts,
                        backgroundColor: generateColors(locations.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
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

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация диаграмм с полными данными
            updateCharts(allData);
            
            // Добавление обработчиков событий для фильтров
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });
        });
    </script>
</body>

</html>