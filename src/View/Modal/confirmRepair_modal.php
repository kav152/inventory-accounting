<?php
$confirmRepairCount = (int) ($confirmRepairCount ?? 0);
$confirmRepairItems = $confirmRepairItems ?? [];
$locationRepairs = $locationRepairs ?? [];
include __DIR__ . '/message_modal.php';
?>
<style>
    #confirmRepairModal .repair-form-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        margin: 4px 0 8px;
    }
    #confirmRepairModal .repair-form-card .form-label {
        font-weight: 650;
        font-size: 0.86rem;
        color: #334155;
    }
    #confirmRepairModal .btn-confirm-repair {
        background: #0f766e;
        border-color: #0f766e;
        font-weight: 700;
    }
    #confirmRepairModal .btn-confirm-repair:hover {
        background: #0d9488;
        border-color: #0d9488;
    }
    #confirmRepairModal .itemRepair-row {
        cursor: pointer;
    }
    #confirmRepairModal .itemRepair-row:hover td {
        background: #f0fdfa;
    }
    #confirmRepairModal .empty-repair-hint {
        text-align: center;
        color: #64748b;
        padding: 28px 12px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px dashed #cbd5e1;
    }
</style>
<div class="modal fade" id="confirmRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение ремонта ТМЦ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <?php if ($confirmRepairCount > 0): ?>
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
                                    <td><?= htmlspecialchars($item->NameTMC) ?></td>
                                    <td><?= htmlspecialchars($item->SerialNumber) ?></td>
                                </tr>
                                <tr class="repair-form" id="repairForm<?= $item->ID_TMC ?>" style="display: none;">
                                    <td colspan="3">
                                        <form class="repair-data-form repair-form-card" data-id="<?= $item->ID_TMC ?>">
                                            <input type="hidden" name="ID_TMC" value="<?= $item->ID_TMC ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Организация</label>
                                                <select name="IDLocation" class="form-select" required>
                                                    <option value="">Выберите организацию</option>
                                                    <?php foreach ($locationRepairs as $loc): ?>
                                                        <option value="<?= $loc->IDLocation ?>">
                                                            <?php
                                                            $legal = trim((string) ($loc->FormsJointStockCompanies ?? ''));
                                                            echo htmlspecialchars($legal !== '' ? $legal . ' — ' . $loc->NameLocation : $loc->NameLocation);
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">№ счета</label>
                                                    <input type="text" name="InvoiceNumber" class="form-control" placeholder="Счет или «Без счета»">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">№ УПД</label>
                                                    <input type="text" name="UPD" class="form-control" placeholder="Номер УПД">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Сумма ремонта/диагностика</label>
                                                    <input type="number" name="RepairCost" class="form-control" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Описание ремонта</label>
                                                <textarea name="RepairDescription" class="form-control" rows="3" required
                                                    placeholder="Описание работ / диагностики"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Документ (PDF), по желанию</label>
                                                <input type="file" name="UPDFile" accept=".pdf" class="form-control">
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-confirm-repair text-white btn-submit-repair"
                                                    onclick="sendForRepair(<?= $item->ID_TMC ?>, 'repair')">
                                                    Подтвердить ремонт
                                                </button>
                                                <button type="button" class="btn btn-danger btn-submit-write-off"
                                                    onclick="sendForRepair(<?= $item->ID_TMC ?>, 'writeOff')">
                                                    Списать
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-repair-hint">
                        Сейчас нет ТМЦ, ожидающих подтверждения ремонта.<br>
                        Появятся после отправки в сервис из «Выдано в работу».
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
