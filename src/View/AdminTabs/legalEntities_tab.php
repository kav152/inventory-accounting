<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/legalEntities_tab.log');

require_once __DIR__ . '/../../BusinessLogic/LegalEntityController.php';

$legalController = new LegalEntityController();
$legalEntities = $legalController->getLegalEntities(false) ?? [];
$legalCount = is_countable($legalEntities) ? count($legalEntities) : 0;
?>

<div class="admin-locations">
    <div class="locations-toolbar">
        <div class="toolbar-left">
            <h5 class="toolbar-title"><i class="bi bi-briefcase"></i> Юр. лица</h5>
            <span class="toolbar-count"><?= $legalCount ?> записей</span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn loc-btn loc-btn-add"
                onclick="openEntityModal(Action.CREATE, 'legalEntityModal')">
                <i class="bi bi-plus-lg"></i> Добавить
            </button>
            <button type="button" class="btn loc-btn loc-btn-edit"
                onclick="openEntityModal(Action.UPDATE, 'legalEntityModal')">
                <i class="bi bi-pencil"></i> Редактировать
            </button>
        </div>
    </div>

    <div class="locations-card">
        <div class="table-responsive">
            <table class="table locations-table align-middle" id="legalEntityTableContainer">
                <thead>
                    <tr>
                        <th>ИД</th>
                        <th>Наименование</th>
                        <th>Активно</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legalCount > 0): ?>
                        <?php foreach ($legalEntities as $legal):
                            $name = trim((string) ($legal->NameLegalEntity ?? ''));
                            $active = (bool) ($legal->isActive ?? true);
                        ?>
                            <tr class="row-legal-entity" data-id="<?= (int) $legal->IDLegalEntity ?>">
                                <td><span class="id-chip"><?= (int) $legal->IDLegalEntity ?></span></td>
                                <td>
                                    <span class="meta-chip meta-chip-legal" title="<?= htmlspecialchars($name) ?>">
                                        <i class="bi bi-building"></i><?= htmlspecialchars($name) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($active): ?>
                                        <span class="badge bg-success">Да</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Нет</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Нет юр. лиц. Нажмите «Добавить».
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.rowSelectionManager) {
            window.rowSelectionManager.initializeTable('legalEntityTableContainer', 'row-legal-entity');
        }
    });
</script>
