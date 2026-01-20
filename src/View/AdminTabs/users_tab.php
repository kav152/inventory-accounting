<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/users_tab.log');


?>

<div class="tab-pane fade show active h-100 d-flex flex-column" id="users" role="tabpanel">
    <!-- Кнопки управления -->
    <div class="d-flex gap-2 align-items-center mb-3 flex-shrink-0">
        <button type="button" class="btn btn-success w-100" onclick="openEntityModal(Action.CREATE, 'userModal')">
            <i class="bi bi-plus-circle"></i> Добавить
        </button>
        <button type="button" class="btn btn-warning w-100" onclick="openEntityModal(Action.UPDATE, 'userModal')">
            <i class="bi bi-pencil-square"></i> Редактировать
        </button>
        <button type="button" class="btn btn-success w-100" onclick="saveUsersStatuses()">
            <i class="bi bi-pencil-square"></i> Сохранить статусы пользователей
        </button>
    </div>

    <!-- Карточка с таблицей - занимает всё оставшееся пространство -->
    <div class="card flex-grow-1 d-flex flex-column">
        <div class="card-body d-flex flex-column p-0 flex-grow-1">
            <form method="post" id="users-form" class="h-100 d-flex flex-column">
                <input type="hidden" name="update_users" value="1">

                <!-- Контейнер таблицы -->
                <div class="scrollable-table-container flex-grow-1">
                    <table class="table table-hover align-middle mb-0 w-100" id="usersTableContainer">
                        <thead class="table-light fixed-header">
                            <tr>
                                <th>Инд.</th>
                                <th>Фамилия</th>
                                <th>Имя</th>
                                <th>Отчество</th>
                                <th>Роль</th>
                                <th class="text-center">Активен</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="row-user" data-id="<?= $user->IDUser?>"
                                    data-surname="<?= htmlspecialchars($user->Surname) ?>"
                                    data-name="<?= htmlspecialchars($user->Name) ?>"
                                    data-patronymic="<?= htmlspecialchars($user->Patronymic) ?>">                                    
                                    <td><?= htmlspecialchars($user->IDUser) ?></td>
                                    <td>
                                        <?= htmlspecialchars($user->Surname) ?>
                                        <?php if ($user->Status == 0): ?>
                                            <span class="badge bg-danger ms-2">Админ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user->Name) ?></td>
                                    <td><?= htmlspecialchars($user->Patronymic) ?></td>
                                    <td class="status-cell">
                                        <select name="Status"
                                            class="form-select form-select-sm">
                                            <option value="0" <?= $user->Status == 0 ? 'selected' : '' ?>>
                                                Администратор</option>
                                            <option value="1" <?= $user->Status == 1 ? 'selected' : '' ?>>Кладовщик
                                            </option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <label class="active-toggle">
                                            <input type="checkbox" name="active[]" value="<?= $user->IDUser ?>"
                                                <?= $user->isActive ? 'checked' : '' ?>
                                                <?= $_SESSION["IDUser"] == $user->IDUser ? 'disabled' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center flex-shrink-0">
            <span class="badge bg-primary">Всего: <?= count($users) ?></span>
        </div>
    </div>
</div>

<script>

    // Инициализация выделения строк для пользователей
    document.addEventListener('DOMContentLoaded', function() {
        if (window.rowSelectionManager) {
            window.rowSelectionManager.initializeTable('usersTableContainer', 'row-user');
        }
    });
</script>