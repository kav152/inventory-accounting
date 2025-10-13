<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/edit_write_off_modal.log');
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Repositories/GenericRepository.php';
require_once __DIR__ . '/../../Repositories/UserRepository.php';
// Данные передаются через переменную $itemData
//$inventoryItem = $itemData['main']->InventoryItem;
$repairs = $itemData;

$inventoryItem = $repairs->first()->InventoryItem;
print_r($itemData);
//$repairs = null;
?>

<div class="modal fade" id="edit_write_off" tabindex="-1" aria-labelledby="editWriteOffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editWriteOffModal">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWriteOffModalLabel">Редактирование данных о ремонтах</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Данные по InventoryItem -->
                    <div class="inventory-item-details mb-4">
                        <h6>Информация о ТМЦ</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th>Регистр</th>
                                <td><?= $inventoryItem->ID_TMC ?></td>
                            </tr>
                            <tr>
                                <th>Наименование</th>
                                <td><?= htmlspecialchars($inventoryItem->NameTMC) ?></td>
                            </tr>
                            <tr>
                                <th>Бренд</th>
                                <td><?= htmlspecialchars($inventoryItem->BrandTMC->NameBrand ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Серийный номер</th>
                                <td><?= htmlspecialchars($inventoryItem->SerialNumber ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Статус</th>
                                <td><?= (new StatusItem())->getDescription($inventoryItem->Status) ?></td>
                            </tr>
                            <tr>
                                <th>Локация</th>
                                <td><?= htmlspecialchars($inventoryItem->Location->NameLocation ?? '') ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Данные по repairs (редактируемые) -->
                    <div class="repairs-details">
                        <h6>Ремонты</h6>
                        <div id="repairsContainer">                            
                            <?php foreach ($repairs as $index => $repair): ?>
                                <div class="repair-item card mb-2" data-repair-id="<?= $repair->ID_Repair ?>">
                                    <input type="hidden" class="form-control id-tmc" value="<?= $inventoryItem->ID_TMC ?>">
                                    <input type="hidden" class="form-control idLocation" value="<?= $repair->Location->IDLocation ?>">
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label">№ Счета</label>
                                                <input type="text" class="form-control invoice-number"
                                                    value="<?= htmlspecialchars($repair->InvoiceNumber ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Стоимость (руб.)</label>
                                                <input type="number" step="0.01" class="form-control repair-cost"
                                                    value="<?= $repair->RepairCost ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Дата отправки</label>
                                                <input type="date" class="form-control date-to-service"
                                                    value="<?= $repair->DateToService ? date('Y-m-d', strtotime($repair->DateToService)) : '' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Дата возвращения</label>
                                                <input type="date" class="form-control date-return-service"
                                                    value="<?= $repair->DateReturnService ? date('Y-m-d', strtotime($repair->DateReturnService)) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-12">
                                                <label class="form-label">Примечания</label>
                                                <textarea
                                                    class="form-control repair-description"><?= htmlspecialchars($repair->RepairDescription ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="form-label">Сервис</label>
                                                <input type="text" class="form-control service-location"
                                                    value="<?= htmlspecialchars($repair->Location->NameLocation ?? '') ?>"
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Изменить</button>
                </div>
            </form>
        </div>
    </div>
</div>