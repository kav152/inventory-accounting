<?php if ($confirmRepairCount > 0): ?>
    <?php
    include __DIR__ . '/message_modal.php';
    ?>
    <div class="modal fade" id="confirmRepairModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение ремонта ТМЦ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmRepairItems as $item): ?>
                                <tr class="itemRepair-row" data-id="<?= $item->ID_TMC ?>">
                                    <td><?= $item->ID_TMC ?></td>
                                    <td><?= $item->NameTMC ?></td>
                                    <td><?= $item->SerialNumber ?></td>
                                </tr>
                                <tr class="repair-form" id="repairForm<?= $item->ID_TMC ?>" style="display: none;">
                                    <td colspan="4">
                                        <form class="repair-data-form" data-id="<?= $item->ID_TMC ?>">
                                            <input type="hidden" name="ID_TMC" value="<?= $item->ID_TMC ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Организация</label>
                                                <select name="IDLocation" class="form-select" required>
                                                    <option value="">Выберите организацию</option>
                                                    <?php foreach ($locationRepairs as $loc): ?>
                                                        <option value="<?= $loc->IDLocation ?>"><?= $loc->NameLocation ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col">
                                                    <label class="form-label">Счет</label>
                                                    <input type="text" name="InvoiceNumber" class="form-control">
                                                </div>
                                                <div class="col">
                                                    <label class="form-label">Сумма ремонта/списания</label>
                                                    <input type="number" name="RepairCost" class="form-control" step="0.01">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Описание ремонта</label>
                                                <textarea name="RepairDescription" class="form-control" rows="3" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Загрузить документ (PDF)</label>
                                                <input type="file" name="UPD" accept=".pdf" class="form-control">
                                            </div>
                                            <button type="button" class="btn btn-primary btn-submit-repair"
                                                onclick="sendForRepair(<?= $item->ID_TMC ?>, 'repair')">В
                                                ремонт</button>
                                            <button type="button" class="btn btn-danger btn-submit-write-off"
                                                onclick="sendForRepair(<?= $item->ID_TMC ?>, 'writeOff')">Списать</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>