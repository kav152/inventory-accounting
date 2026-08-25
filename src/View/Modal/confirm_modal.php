<<<<<<< HEAD
<?php if ($confirmCount > 0): ?>

    <?php
    include __DIR__ . '/message_modal.php';
    ?>
    <!-- Модальное окно подтверждения ТМЦ (ConfirmItem) aria-hidden="true" -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Принять ТМЦ на склад</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
=======
<?php
$confirmCount = (int) ($confirmCount ?? 0);
$confirmItems = $confirmItems ?? [];
include __DIR__ . '/message_modal.php';
?>
<style>
    #confirmModal .modal-dialog {
        max-width: min(1100px, 96vw);
        margin: 1.25rem auto;
    }
    #confirmModal .modal-content {
        padding: 0;
        overflow: hidden;
        border: none;
        border-radius: 12px;
        width: 100%;
        max-width: 100%;
    }
    #confirmModal .modal-header {
        flex-shrink: 0;
        width: 100%;
        box-sizing: border-box;
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    #confirmModal .modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        padding-right: 0.5rem;
    }
    #confirmModal .modal-body {
        padding: 0;
        max-height: min(70vh, 720px);
        overflow: auto;
        width: 100%;
        box-sizing: border-box;
    }
    #confirmModal .confirm-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
        border-collapse: collapse;
    }
    #confirmModal .confirm-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f1f5f9;
        font-size: 0.82rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.7rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    #confirmModal .confirm-table td {
        padding: 0.7rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.92rem;
        color: #0f172a;
        word-break: break-word;
    }
    #confirmModal .confirm-table col.col-id { width: 64px; }
    #confirmModal .confirm-table col.col-name { width: auto; }
    #confirmModal .confirm-table col.col-serial { width: 18%; }
    #confirmModal .confirm-table col.col-loc { width: 16%; }
    #confirmModal .confirm-table col.col-actions { width: 200px; }
    #confirmModal .confirm-actions {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.4rem;
    }
    #confirmModal .confirm-actions .btn {
        flex: 1 1 auto;
        min-width: 0;
        padding: 0.35rem 0.55rem;
        font-size: 0.82rem;
        font-weight: 650;
        white-space: nowrap;
    }
    #confirmModal .confirm-empty {
        text-align: center;
        color: #64748b;
        padding: 2rem 1rem;
        margin: 0;
    }
</style>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Проверка УПД / принять ТМЦ на объект</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($confirmCount > 0): ?>
                    <table class="table confirm-table mb-0">
                        <colgroup>
                            <col class="col-id">
                            <col class="col-name">
                            <col class="col-serial">
                            <col class="col-loc">
                            <col class="col-actions">
                        </colgroup>
>>>>>>> feature/local-updates-2026-08
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
<<<<<<< HEAD
=======
                                <th>Объект</th>
>>>>>>> feature/local-updates-2026-08
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmItems as $item): ?>
                                <tr id="itemRow<?= $item->ID_TMC ?>">
<<<<<<< HEAD
                                    <td><?= $item->ID_TMC ?></td>
                                    <td><?= $item->NameTMC ?></td>
                                    <td><?= $item->SerialNumber ?></td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <button class="btn btn-success"
                                                onclick="processItem(<?= $item->ID_TMC ?>, 'accept')">
                                                Принять
                                            </button>
                                            <button class="btn btn-danger"
                                                onclick="processItem(<?= $item->ID_TMC ?>, 'reject')">
=======
                                    <td><?= (int) $item->ID_TMC ?></td>
                                    <td><?= htmlspecialchars($item->NameTMC ?? '') ?></td>
                                    <td><?= htmlspecialchars($item->SerialNumber ?? '') ?></td>
                                    <td><?= htmlspecialchars($item->Location?->NameLocation ?? $item->NameLocation ?? '') ?></td>
                                    <td>
                                        <div class="confirm-actions">
                                            <button type="button" class="btn btn-success"
                                                onclick="processItem(<?= (int) $item->ID_TMC ?>, 'accept')">
                                                Принять
                                            </button>
                                            <button type="button" class="btn btn-danger"
                                                onclick="processItem(<?= (int) $item->ID_TMC ?>, 'reject')">
>>>>>>> feature/local-updates-2026-08
                                                Отказать
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
<<<<<<< HEAD
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
=======
                <?php else: ?>
                    <p class="confirm-empty">Нет ТМЦ, ожидающих проверки УПД и приёмки на объект.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
>>>>>>> feature/local-updates-2026-08
