<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/admin_panel.log');
// Проверка авторизации
if (!isset($_SESSION['IDUser'])) {
    die('Доступ запрещен');
}

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/GenericRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../BusinessLogic/CsvImporter.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';

//DatabaseFactory::setConfig();
$container = new Container();

$container->set(Database::class, function () {
    return DatabaseFactory::create();
});

$userRepository = $container->get(UserRepository::class);
$users = $userRepository->getAll();

$isAdmin = ($_SESSION['Status'] == 0);
$errorMessage = '';
$successMessage = '';
$importResults = null;


?>


<style>
    /* Увеличиваем ширину всей панели управления */
    #users {
        max-width: 98%;
        margin: 0 auto;
    }

    /* Расширяем карточку таблицы */
    .table-card {
        width: 100%;
    }

    /* Уменьшаем отступы в ячейках таблицы */
    .table-hover td,
    .table-hover th {
        padding: 8px 10px;
    }

    /* Делаем поля ввода и выбора адаптивными */
    .table-hover input,
    .table-hover select {
        width: 100%;
        min-width: 120px;
    }

    /* Улучшаем отображение на мобильных устройствах */
    @media (max-width: 992px) {
        .scrollable-table-container {
            overflow-x: auto;
        }

        .table-hover {
            min-width: 1000px;
        }

        .row {
            flex-direction: column;
        }

        .col-lg-3,
        .col-lg-9 {
            max-width: 100%;
            flex: 0 0 100%;
        }
    }

    /* Стили для ползунка активности */
    .active-toggle {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
    }

    .active-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.toggle-slider {
        background-color: #27ae60;
    }

    input:checked+.toggle-slider:before {
        transform: translateX(30px);
    }

    /* Контейнер для таблицы с вертикальной прокруткой */
    .scrollable-table-container {
        max-height: 500px;
        /*width: 900px;*/
        overflow-y: auto;
    }

    /* Фиксированный заголовок таблицы */
    .fixed-header {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
    }

    /* Улучшение стилей таблицы */
    .table-hover td {
        padding: 0.3rem;
        vertical-align: middle;
    }

    .table-hover input.form-control-sm,
    .table-hover select.form-select-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        height: auto;
        line-height: 1.3;
    }

    .table-hover input[type="password"] {
        min-width: 120px;
    }

    .compact-cell {
        max-width: 150px;
        min-width: 120px;
    }

    .status-cell {
        min-width: 140px;
    }

    .active-cell {
        min-width: 100px;
    }
</style>

<?php
//<!-- Модальное окно сообщений -->    
include __DIR__ . '/Modal/message_modal.php';
?>



<!-- Панель управления -->
<ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
            <i class="bi bi-people"></i> Пользователи
        </button>
    </li>

    <?php if ($isAdmin): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button">
                <i class="bi bi-file-earmark-spreadsheet"></i> Импорт CSV
            </button>
        </li>
    <?php endif; ?>
</ul>

<div class="tab-content" id="adminTabsContent">
    <!-- Вкладка пользователей -->
    <div class="tab-pane fade show active" id="users" role="tabpanel">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="card user-card h-100">
                    <div class="card-body text-center">
                        <div class="user-avatar bg-success">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h4>Добавить пользователя</h4>
                        <p class="text-muted">Только для администраторов</p>

                        <?php if ($isAdmin): ?>
                            <form method="post" class="mt-1">
                                <input type="hidden" name="add_user" value="1">

                                <div class="mb-2">
                                    <input type="text" name="surname" class="form-control" placeholder="Фамилия" required>
                                </div>

                                <div class="mb-2">
                                    <input type="text" name="name" class="form-control" placeholder="Имя" required>
                                </div>

                                <div class="mb-2">
                                    <input type="text" name="patronymic" class="form-control" placeholder="Отчество">
                                </div>

                                <div class="mb-2">
                                    <input type="password" name="password" class="form-control" placeholder="Пароль"
                                        required>
                                </div>

                                <div class="mb-2">
                                    <select class="form-select" name="status">
                                        <option value="1">Обычный пользователь</option>
                                        <option value="0">Администратор</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle"></i> Добавить
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-shield-lock"></i> Доступно только администраторам
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-lines-fill"></i> Управление пользователями</span>
                        <span class="badge bg-primary">Всего: <?= count($users) ?></span>
                    </div>
                    <div class="card-body p-0" id="cardUser">
                        <form method="post" id="users-form">
                            <!--input type="hidden" name="update_users" value="1"-->

                            <div class="scrollable-table-container">
                                <table class="table table-hover align-middle m-0">
                                    <thead class="table-light fixed-header">
                                        <tr>
                                            <th class="compact-cell">Фамилия</th>
                                            <th class="compact-cell">Имя</th>
                                            <th class="compact-cell">Отчество</th>
                                            <th>Пароль</th>
                                            <th class="status-cell">Статус</th>
                                            <th class="text-center active-cell">Активен</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="compact-cell">
                                                    <input type="text" name="users[<?= $user->IDUser ?>][Surname]"
                                                        value="<?= htmlspecialchars($user->Surname) ?>"
                                                        class="form-control form-control-sm" required>
                                                </td>
                                                <td class="compact-cell">
                                                    <input type="text" name="users[<?= $user->IDUser ?>][Name]"
                                                        value="<?= htmlspecialchars($user->Name) ?>"
                                                        class="form-control form-control-sm" required>
                                                </td>
                                                <td class="compact-cell">
                                                    <input type="text" name="users[<?= $user->IDUser ?>][Patronymic]"
                                                        value="<?= htmlspecialchars($user->Patronymic) ?>"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input type="password" name="users[<?= $user->IDUser ?>][Password]"
                                                        placeholder="Новый пароль" class="form-control form-control-sm">
                                                </td>
                                                <td class="status-cell">
                                                    <select name="users[<?= $user->IDUser ?>][Status]"
                                                        class="form-select form-select-sm">
                                                        <option value="0" <?= $user->Status == 0 ? 'selected' : '' ?>>
                                                            Администратор</option>
                                                        <option value="1" <?= $user->Status == 1 ? 'selected' : '' ?>>Кладовщик
                                                        </option>
                                                    </select>
                                                </td>
                                                <td class="text-center active-cell">
                                                    <input type="hidden" name="users[<?= $user->IDUser ?>][isActive]"
                                                        value="0">
                                                    <label class="active-toggle">
                                                        <input type="checkbox" name="users[<?= $user->IDUser ?>][isActive]"
                                                            value="1" <?= $user->isActive ? 'checked' : '' ?>
                                                            onchange="this.previousSibling.value = this.checked ? '1' : '0'">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($isAdmin): ?>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" class="btn btn-primary" id="save-users-btn">
                                        <i class="bi bi-save"></i> Сохранить изменения
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Вкладка импорта (только для админов) -->
    <?php if ($isAdmin): ?>
        <div class="tab-pane fade" id="import" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-database-add"></i> Импорт данных из CSV
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="import-preview">
                                <i class="bi bi-file-earmark-spreadsheet feature-icon"></i>
                                <h4>Загрузите CSV файлы</h4>
                                <p class="text-muted">Выберите файлы для импорта данных в систему</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="import_csv" value="1">

                                <div class="mb-3">
                                    <label class="form-label">Выберите CSV файлы:</label>
                                    <input class="form-control" type="file" name="csv_files[]" multiple accept=".csv"
                                        required>
                                </div>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Загружайте файлы в формате CSV с
                                    разделителями-запятыми
                                </div>

                                <button type="submit" class="btn btn-success w-100 mt-3">
                                    <i class="bi bi-upload"></i> Импортировать данные
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($importResults): ?>
                        <div class="mt-4">
                            <h5><i class="bi bi-list-check"></i> Результаты импорта:</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Таблица</th>
                                            <th>Статус</th>
                                            <th>Сообщение</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($importResults as $table => $result): ?>
                                            <tr>
                                                <td><?= $table ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $result['status'] === 'success' ? 'success' : 'danger' ?>">
                                                        <?= $result['status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= $result['message'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

    // Активация вкладок Bootstrap
    const triggerTabList = document.querySelectorAll('#adminTabs button');
    triggerTabList.forEach(triggerEl => {
        new bootstrap.Tab(triggerEl);
    });

    document.getElementById('save-users-btn').addEventListener('click', saveUsers);


    // Функция для обновления таблицы пользователей
    function refreshUserTable() {
        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.scrollable-table-container');
                if (newTable) {
                    document.querySelector('.scrollable-table-container').innerHTML = newTable.innerHTML;
                }
            });
    }

</script>