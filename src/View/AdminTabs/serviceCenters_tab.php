<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/customers_tab.log');

require_once __DIR__ . '/../../BusinessLogic/LocationController.php';

$locationController = new LocationController();
$locations = $locationController->getLocations(true);

print_r($locations, true);

?>

<div class="row">

    <div class="d-flex gap-2 align-items-center mb-3">
        <button type="button" class="btn btn-success w-100" onclick="openEntityModal(Action.CREATE, 'locationServiceModal')">
            <i class="bi bi-plus-circle"></i> Добавить
        </button>
        <button type="button" class="btn btn-warning w-100" onclick="openEntityModal(Action.UPDATE, 'locationServiceModal')">
            <i class="bi bi-pencil-square"></i> Редактировать
        </button>
        <button type="button" class="btn btn-danger w-100">
            <i class="bi bi-trash"></i> Аннулировать
        </button>
    </div>

    <div class>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="serviceCentersTableContainer">
                        <thead class="table-light">
                            <tr>
                                <th>ИД</th>
                                <th>Наименование</th>
                                <th>Адрес</th>
                                <th>Город</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $location): ?>
                                <tr class="row-serviceCenters" data-id="<?= $location->IDLocation ?>">
                                    <td><?= htmlspecialchars($location->IDLocation) ?></td>  
                                    <td><?= htmlspecialchars($location->FormsJointStockCompanies . " " . $location->NameLocation) ?></td>
                                    <td><?= htmlspecialchars($location->Address) ?></td>                                  
                                    <td><?= htmlspecialchars($location->City->NameCity) ?></td>                                    
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Инициализация выделения строк для заказчиков
    document.addEventListener('DOMContentLoaded', function () {
        //console.log('Мы сервисных центрах!');
        if (window.rowSelectionManager) {
            window.rowSelectionManager.initializeTable('serviceCentersTableContainer', 'row-serviceCenters');
        }
    });
</script>