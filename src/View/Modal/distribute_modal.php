<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/distribute_modal.log');
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Repositories/GenericRepository.php';
require_once __DIR__ . '/../../Repositories/UserRepository.php';

?>

<?php include __DIR__ . '/message_modal.php'; ?>

<<<<<<< HEAD
<div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
=======
<style>
    #distributeModal .legal-transfer-panel {
        display: none;
        gap: 12px;
        margin-bottom: 16px;
    }
    #distributeModal .legal-transfer-panel.is-visible {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: stretch;
    }
    #distributeModal .legal-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 14px;
    }
    #distributeModal .legal-card-to {
        background: #f0fdfa;
        border-color: #99f6e4;
    }
    #distributeModal .legal-card-cross {
        box-shadow: inset 0 0 0 1px #f59e0b;
        background: #fffbeb;
    }
    #distributeModal .legal-card-label {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }
    #distributeModal .legal-card-value {
        font-weight: 750;
        color: #0f172a;
        font-size: 0.95rem;
        word-break: break-word;
    }
    #distributeModal .legal-card-value.is-empty {
        color: #94a3b8;
        font-weight: 600;
    }
    #distributeModal .legal-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0f766e;
        font-size: 1.25rem;
    }
    #distributeModal .legal-cross-hint {
        display: none;
        margin: -6px 0 14px;
        padding: 8px 12px;
        border-radius: 10px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-size: 0.84rem;
        font-weight: 650;
    }
    #distributeModal .legal-cross-hint.is-visible {
        display: block;
    }
    #distributeModal .col-legal-to {
        display: table-cell;
    }
    #distributeModal .legal-chip {
        display: inline-block;
        max-width: 160px;
        padding: 3px 8px;
        border-radius: 8px;
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        color: #0f766e;
        font-size: 0.78rem;
        font-weight: 650;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #distributeModal .legal-chip.empty {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #94a3b8;
        font-weight: 600;
    }
    @media (max-width: 700px) {
        #distributeModal .legal-transfer-panel.is-visible {
            grid-template-columns: 1fr;
        }
        #distributeModal .legal-arrow { transform: rotate(90deg); }
    }
</style>

<div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
>>>>>>> source/feature/local-updates-2026-08
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Передача ТМЦ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="distributeForm">
<<<<<<< HEAD
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Объект назначения</label>
                            <select name="location" class="form-select" required>
                                <option value="">Выберите объект</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location->IDLocation ?>"><?= $location->NameLocation ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
=======
                    <div class="legal-transfer-panel" id="legalTransferPanel">
                        <div class="legal-card legal-card-from" id="legalCardFrom">
                            <div class="legal-card-label">Юр. лицо откуда</div>
                            <div class="legal-card-value is-empty" id="legalFromValue">—</div>
                        </div>
                        <div class="legal-arrow"><i class="bi bi-arrow-right"></i></div>
                        <div class="legal-card legal-card-to" id="legalCardTo">
                            <div class="legal-card-label">Юр. лицо куда</div>
                            <div class="legal-card-value is-empty" id="legalToValue">—</div>
                        </div>
                    </div>
                    <div class="legal-cross-hint" id="legalCrossHint">
                        Передача между разными юр. лицами — проверьте оба столбца в таблице ниже.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Объект назначения</label>
                            <select name="location" id="distributeLocationSelect" class="form-select" required>
                                <option value="">Выберите объект</option>
                                <?php foreach ($locations as $location): ?>
                                    <?php $legal = trim((string) ($location->FormsJointStockCompanies ?? '')); ?>
                                    <option value="<?= $location->IDLocation ?>"
                                        data-legal="<?= htmlspecialchars($legal) ?>"
                                        data-name="<?= htmlspecialchars($location->NameLocation ?? '') ?>">
                                        <?= htmlspecialchars($location->NameLocation) ?>
                                        <?= $legal !== '' ? ' (' . htmlspecialchars($legal) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <div class="row mb-3">
>>>>>>> source/feature/local-updates-2026-08
                        <div class="col-md-6">
                            <label class="form-label">Ответственный</label>
                            <select name="user" class="form-select" required>
                                <option value="">Выберите ответственного</option>
                                <?php foreach ($users as $user): ?>
<<<<<<< HEAD
                                    <option value="<?= $user->IDUser ?>"><?= $user->FIO ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <table class="table" >
=======
                                    <option value="<?= $user->IDUser ?>"><?= htmlspecialchars($user->FIO) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">№ УПД (проверка)</label>
                            <input type="text" name="upd" id="distributeUpdInput" class="form-control"
                                placeholder="Номер УПД при отправке на объект">
                            <div class="form-text">Для ТМЦ из сервиса укажите УПД — админ получит уведомление на приёмку.</div>
                        </div>
                    </div>

                    <table class="table align-middle" id="distributeItemsTable">
>>>>>>> source/feature/local-updates-2026-08
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
                                <th>Текущее местоположение</th>
<<<<<<< HEAD
=======
                                <th>Юр. лицо откуда</th>
                                <th class="col-legal-to">Юр. лицо куда</th>
>>>>>>> source/feature/local-updates-2026-08
                            </tr>
                        </thead>
                        <tbody id="selectedItemsTable">
                            <!-- Сюда будут добавлены выбранные ТМЦ -->
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Подтвердить передачу</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<<<<<<< HEAD
</div>
=======
</div>
>>>>>>> source/feature/local-updates-2026-08
