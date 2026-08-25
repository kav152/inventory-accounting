<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/serviceCenters_tab.log');

require_once __DIR__ . '/../../BusinessLogic/LocationController.php';

$locationController = new LocationController();
$locations = $locationController->getLocations(true);
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
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.rowSelectionManager) {
            window.rowSelectionManager.initializeTable('serviceCentersTableContainer', 'row-serviceCenters');
        }
    });
</script>
