<?php
require_once __DIR__ . '/../../BusinessLogic/StatusItem.php';
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
    #confirmRepairModal .archive-hint {
        font-size: 0.88rem;
        color: #64748b;
        margin-bottom: 12px;
    }
</style>
<div class="modal fade" id="confirmRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Архив ремонтов — приложить счёт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <?php if ($confirmRepairCount > 0): ?>
                    <p class="archive-hint mb-2">
                        Инструмент уже может быть в сервисе или возвращён кладовщиком.
                        Счёт и документы можно добавить позже — это не блокирует работу склада.
                        <a href="/src/View/write_off.php">Полный реестр ремонтов</a>
                    </p>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmRepairItems as $entry):
                                $isLegacy = !empty($entry->isLegacyConfirm);
                                $repairId = (int) ($entry->ID_Repair ?? 0);
                                $inv = $isLegacy
                                    ? ($entry->InventoryItem ?? null)
                                    : ($entry->InventoryItem ?? null);
                                $tmcId = (int) ($entry->ID_TMC ?? ($inv->ID_TMC ?? 0));
                                $name = htmlspecialchars((string) ($inv->NameTMC ?? ''));
                                $serial = htmlspecialchars((string) ($inv->SerialNumber ?? ''));
                                $itemStatus = (int) ($inv->Status ?? -1);
                                $statusLabel = (new StatusItem())->getDescription($itemStatus) ?? '—';
                                $repairDesc = htmlspecialchars((string) ($entry->RepairDescription ?? ''));
                                $locId = (int) ($entry->IDLocation ?? ($inv->IDLocation ?? 0));
                                $isArchive = $repairId > 0;
                            ?>
                                <tr class="itemRepair-row" data-id="<?= $tmcId ?>">
                                    <td><?= $tmcId ?></td>
                                    <td><?= $name ?></td>
                                    <td><?= $serial ?></td>
                                    <td><?= htmlspecialchars($statusLabel) ?></td>
                                </tr>
                                <tr class="repair-form" id="repairForm<?= $tmcId ?>" style="display: none;">
                                    <td colspan="4">
                                        <form class="repair-data-form repair-form-card" data-id="<?= $tmcId ?>"
                                            data-repair-id="<?= $repairId ?>">
                                            <input type="hidden" name="ID_TMC" value="<?= $tmcId ?>">
                                            <input type="hidden" name="ID_Repair" value="<?= $repairId ?>">
                                            <?php if ($isArchive && $repairDesc !== ''): ?>
                                                <div class="mb-2 text-muted small">
                                                    <strong>Причина отправки:</strong> <?= $repairDesc ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label">Организация (сервис)</label>
                                                <select name="IDLocation" class="form-select" <?= $isLegacy ? 'required' : '' ?>>
                                                    <option value="">Выберите организацию</option>
                                                    <?php foreach ($locationRepairs as $loc): ?>
                                                        <option value="<?= (int) $loc->IDLocation ?>"
                                                            <?= $locId === (int) $loc->IDLocation ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($loc->NameLocation) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">№ счета</label>
                                                    <input type="text" name="InvoiceNumber" class="form-control"
                                                        placeholder="Счет или «Без счета»">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">№ УПД</label>
                                                    <input type="text" name="UPD" class="form-control" placeholder="Номер УПД">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Сумма ремонта</label>
                                                    <input type="number" name="RepairCost" class="form-control" step="0.01" min="0"
                                                        value="<?= htmlspecialchars((string) ($entry->RepairCost ?? '0')) ?>"
                                                        placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Описание ремонта</label>
                                                <textarea name="RepairDescription" class="form-control" rows="3"
                                                    <?= $isLegacy ? 'required' : '' ?>
                                                    placeholder="Описание работ / диагностики"><?= $repairDesc ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Документ (PDF), по желанию</label>
                                                <input type="file" name="UPDFile" accept=".pdf" class="form-control">
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-confirm-repair text-white btn-submit-repair"
                                                    onclick="sendForRepair(<?= $tmcId ?>, 'repair')">
                                                    <?= $isArchive ? 'Сохранить счёт' : 'Подтвердить ремонт' ?>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-submit-write-off"
                                                    onclick="sendForRepair(<?= $tmcId ?>, 'writeOff')">
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
                        Нет ремонтов без счёта.<br>
                        Появятся после отправки инструмента в сервис кладовщиком.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
