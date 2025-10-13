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

<div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Передача ТМЦ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="distributeForm">
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
                        <div class="col-md-6">
                            <label class="form-label">Ответственный</label>
                            <select name="user" class="form-select" required>
                                <option value="">Выберите ответственного</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user->IDUser ?>"><?= $user->FIO ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
                                <th>Текущее местоположение</th>
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
</div>