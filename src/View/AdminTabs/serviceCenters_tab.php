<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
<<<<<<< HEAD
ini_set('error_log', __DIR__ . '/../../storage/logs/customers_tab.log');
=======
ini_set('error_log', __DIR__ . '/../../storage/logs/serviceCenters_tab.log');
>>>>>>> source/feature/local-updates-2026-08

require_once __DIR__ . '/../../BusinessLogic/LocationController.php';

$locationController = new LocationController();
$locations = $locationController->getLocations(true);
<<<<<<< HEAD

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
=======
$count = $locations ? count($locations) : 0;
?>

<div class="admin-locations">
    <div class="locations-toolbar">
        <div class="toolbar-left">
            <h5 class="toolbar-title"><i class="bi bi-tools"></i> Сервисные центры</h5>
            <span class="toolbar-count"><?= $count ?> записей</span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn loc-btn loc-btn-add" onclick="openEntityModal(Action.CREATE, 'locationServiceModal')">
                <i class="bi bi-plus-lg"></i> Добавить
            </button>
            <button type="button" class="btn loc-btn loc-btn-edit" onclick="openEntityModal(Action.UPDATE, 'locationServiceModal')">
                <i class="bi bi-pencil"></i> Редактировать
            </button>
            <button type="button" class="btn loc-btn loc-btn-danger" disabled title="Скоро">
                <i class="bi bi-trash"></i> Аннулировать
            </button>
        </div>
    </div>

    <div class="locations-card">
        <div class="table-responsive">
            <table class="table locations-table align-middle" id="serviceCentersTableContainer">
                <thead>
                    <tr>
                        <th>ИД</th>
                        <th>Наименование</th>
                        <th>Юр. лицо</th>
                        <th>Адрес</th>
                        <th>Локация 2</th>
                        <th>Город</th>
                        <th>Телефоны</th>
                        <th>Контакты</th>
                        <th>Почта</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($locations): ?>
                        <?php foreach ($locations as $location):
                            $legalEntity = trim((string) ($location->FormsJointStockCompanies ?? ''));
                            $phone = trim((string) ($location->Phone ?? ''));
                            $contacts = trim((string) ($location->Contacts ?? ''));
                            $email = trim((string) ($location->Email ?? ''));
                            $address = trim((string) ($location->Address ?? ''));
                            $location2 = trim((string) ($location->Location2 ?? ''));
                            $cityName = trim((string) ($location->City?->NameCity ?? ''));
                            $cityAddress = trim((string) ($location->City?->Address ?? ''));
                        ?>
                            <tr class="row-serviceCenters" data-id="<?= $location->IDLocation ?>">
                                <td><span class="id-chip"><?= htmlspecialchars($location->IDLocation) ?></span></td>
                                <td>
                                    <div class="name-cell">
                                        <span><?= htmlspecialchars($location->NameLocation ?? '') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($legalEntity !== ''): ?>
                                        <span class="meta-chip meta-chip-legal" title="<?= htmlspecialchars($legalEntity) ?>">
                                            <i class="bi bi-building"></i><?= htmlspecialchars($legalEntity) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="address-cell"><?= $address !== '' ? htmlspecialchars($address) : '<span class="empty-cell">—</span>' ?></td>
                                <td><?= $location2 !== '' ? htmlspecialchars($location2) : '<span class="empty-cell">—</span>' ?></td>
                                <td>
                                    <div class="city-cell">
                                        <span class="city-name"><?= $cityName !== '' ? htmlspecialchars($cityName) : '<span class="empty-cell">—</span>' ?></span>
                                        <?php if ($cityAddress !== ''): ?>
                                            <span class="city-address"><?= htmlspecialchars($cityAddress) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($phone !== ''): ?>
                                        <span class="meta-chip meta-chip-phone"><i class="bi bi-telephone"></i><?= htmlspecialchars($phone) ?></span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($contacts !== ''): ?>
                                        <span class="meta-chip meta-chip-contact"><i class="bi bi-person"></i><?= htmlspecialchars($contacts) ?></span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($email !== ''): ?>
                                        <span class="meta-chip meta-chip-email"><i class="bi bi-envelope"></i><?= htmlspecialchars($email) ?></span>
                                    <?php else: ?>
                                        <span class="empty-cell">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
>>>>>>> source/feature/local-updates-2026-08
        </div>
    </div>
</div>

<<<<<<< HEAD

<script>
    // Инициализация выделения строк для заказчиков
    document.addEventListener('DOMContentLoaded', function () {
        //console.log('Мы сервисных центрах!');
=======
<script>
    document.addEventListener('DOMContentLoaded', function () {
>>>>>>> source/feature/local-updates-2026-08
        if (window.rowSelectionManager) {
            window.rowSelectionManager.initializeTable('serviceCentersTableContainer', 'row-serviceCenters');
        }
    });
<<<<<<< HEAD
</script>
=======
</script>
>>>>>>> source/feature/local-updates-2026-08
