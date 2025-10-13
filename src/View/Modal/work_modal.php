<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/work_modal.log');
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Repositories/GenericRepository.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';
//require_once __DIR__ . '/../../Repositories/UserRepository.php';

DatabaseFactory::setConfig();
$controller = new ItemController();
$brigades = $controller->getBrigades($_SESSION["IDUser"]);

?>

<div class="modal fade" id="workModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Передача ТМЦ в работу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Левая часть (основная) -->
                    <div class="col-md-8" id="mainSection">
                        <form id="workForm">
                            <div class="mb-3">
                                <label class="form-label">Бригадир</label>
                                <div class="input-group">
                                    <select name="brigade" id="brigadeSelect" class="form-select" required>
                                        <option value="">Выберите бригаду</option>
                                        <?php foreach ($brigades as $brigade): ?>
                                            <option value="<?= $brigade->IDBrigade ?>">
                                                <?= $brigade->NameBrigade ?> (<?= $brigade->NameBrigadir ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btnExpand">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                                            <path
                                                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Наименование</th>
                                            <th>Серийный номер</th>
                                            <th>Текущее местоположение</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedWorkItemsTable">
                                        <!-- Сюда будут добавлены выбранные ТМЦ -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2"
                                    data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" class="btn btn-primary">Передать</button>
                            </div>
                        </form>
                    </div>

                    <!-- Правая часть (для создания бригады, изначально скрыта) -->
                    <div class="col-md-4 border-start" id="createBrigadeSection" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Создать бригаду</h5>
                            <button type="button" class="btn-close" id="btnCollapse"></button>
                        </div>

                        <form id="createBrigadeForm">
                            <div class="mb-3">
                                <label class="form-label">Бригадир</label>
                                <input type="text" name="brigadir" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Название бригады</label>
                                <input type="text" name="brigade_name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ответственный</label>
                                <input type="text" class="form-control"
                                    value="<?= htmlspecialchars($_SESSION["FIO"]) ?>" readonly>

                                <input type="hidden" name="responsible"
                                    value="<?= htmlspecialchars($_SESSION["IDUser"]) ?>">
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2"
                                    id="btnCancelCreate">Отмена</button>
                                <button type="submit" class="btn btn-primary">Создать</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/message_modal.php';
?>