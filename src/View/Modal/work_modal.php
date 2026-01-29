<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/work_modal.log');
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Repositories/GenericRepository.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';

DatabaseFactory::setConfig();
$controller = new ItemController();
$brigades = $controller->getBrigades($_SESSION["IDUser"]);
/*
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
*/
?>

<style>
    .dropdown-select-container {
        position: relative;
    }

    .dropdown-select-trigger {
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .dropdown-select-trigger:hover {
        border-color: #86b7fe;
    }

    .dropdown-select-trigger:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        outline: 0;
    }

    .brigade-item {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .brigade-item:hover {
        background-color: #f8f9fa;
    }

    .brigade-item .delete-brigade-btn {
        opacity: 0.6;
        transition: opacity 0.15s ease;
        padding: 0.125rem 0.375rem;
    }

    .brigade-item:hover .delete-brigade-btn {
        opacity: 1;
    }

    .brigade-item .delete-brigade-btn:hover {
        background-color: #dc3545;
        color: white;
    }
</style>
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
                                    <div class="dropdown-select-container" style="position: relative; width: 90%;">
                                        <!-- Скрытый input для хранения выбранного значения -->
                                        <input type="hidden" name="brigade" id="selectedBrigadeId" value="">

                                        <!-- Кнопка-триггер для открытия списка -->
                                        <button type="button"
                                            class="form-select dropdown-select-trigger d-flex justify-content-between align-items-center w-100 text-start"
                                            id="brigadeDropdownTrigger" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span id="selectedBrigadeText">Выберите бригаду</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
                                                <path fill-rule="evenodd"
                                                    d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                            </svg>
                                        </button>

                                        <!-- Выпадающий список с элементами -->
                                        <ul class="dropdown-menu w-100 p-2" id="brigadeDropdownList"
                                            aria-labelledby="brigadeDropdownTrigger">
                                            <?php foreach ($brigades as $index => $brigade): ?>
                                                <li class="dropdown-item brigade-item d-flex justify-content-between align-items-center"
                                                    data-id="<?= $brigade->IDBrigade ?>"
                                                    data-name="<?= htmlspecialchars($brigade->NameBrigade) ?>"
                                                    data-brigadir="<?= htmlspecialchars($brigade->NameBrigadir) ?>">
                                                    <span>
                                                        <?= $brigade->NameBrigade ?> (
                                                        <?= $brigade->NameBrigadir ?>)
                                                    </span>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-brigade-btn"
                                                        data-id="<?= $brigade->IDBrigade ?>" data-index="<?= $index ?>"
                                                        title="Удалить бригаду">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                            fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                            <path
                                                                d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                                            <path fill-rule="evenodd"
                                                                d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                                        </svg>
                                                    </button>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <!-- Кнопка добавления новой бригады -->
                                    <button type="button" class="btn btn-outline-secondary" id="btnExpand"
                                        style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
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