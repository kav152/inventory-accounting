<?php
// Проверка авторизации
if (!isset($_SESSION['IDUser'])) {
    die('Доступ запрещен');
}

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/GenericRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__.'/../BusinessLogic/CsvImporter.php';
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

// Обработка POST-запросов панели управления
if ($_SERVER["REQUEST_METHOD"] === "POST" && $isAdmin) {
    // Обработка добавления пользователя
    if (isset($_POST['add_user'])) {
        $surname = trim($_POST['surname']);
        $name = trim($_POST['name']);
        $patronymic = trim($_POST['patronymic']);
        $password = trim($_POST['password']);
        $status = (int)$_POST['status'];
        
        if (!empty($surname) && !empty($name) && !empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $newUser = new User([
                'Surname' => $surname,
                'Name' => $name,
                'Patronymic' => $patronymic,
                'Password' => $hashedPassword,
                'Status' => $status,
                'isActive' => 1
            ]);
            
            if ($userRepository->add($newUser)) {
                $successMessage = "Пользователь успешно добавлен!";
                $users = $userRepository->getAll();
            } else {
                $errorMessage = "Ошибка при добавлении пользователя";
            }
        } else {
            $errorMessage = "Заполните обязательные поля";
        }
    }
    
    // Обработка изменения активности
    if (isset($_POST['update_users'])) {
        $activeUsers = $_POST['active'] ?? [];
        
        foreach ($users as $user) {
            $isActive = in_array($user->IDUser, $activeUsers) ? 1 : 0;
            if ($isActive != $user->isActive) {
                $user->isActive = $isActive;
                $userRepository->update($user);
            }
        }
        
        $successMessage = "Статусы пользователей обновлены!";
        $users = $userRepository->getAll();
    }
    
    // Обработка импорта CSV
    if (isset($_POST['import_csv'])) {
        $database = $container->get(Database::class);
        $importer = new CsvImporter($database);
        $requiredTables = $importer->getRequiredTables();
        
        $uploadedFiles = [];
        foreach ($_FILES['csv_files']['name'] as $index => $name) {
            if ($_FILES['csv_files']['error'][$index] === UPLOAD_ERR_OK) {
                $tableName = pathinfo($name, PATHINFO_FILENAME);
                $uploadedFiles[$tableName] = [
                    'tmp_name' => $_FILES['csv_files']['tmp_name'][$index],
                    'error' => $_FILES['csv_files']['error'][$index]
                ];
            }
        }
        
        $missingTables = array_diff($requiredTables, array_keys($uploadedFiles));
        
        if (!empty($missingTables)) {
            $errorMessage = 'Отсутствуют файлы: ' . implode(', ', $missingTables);
        } else {
            $importResults = $importer->importData($uploadedFiles);
            $successMessage = "Импорт данных выполнен!";
        }
    }
}
?>

<style>
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
    
    input:checked + .toggle-slider {
        background-color: #27ae60;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(30px);
    }
    
    /* Контейнер для таблицы с вертикальной прокруткой */
    .scrollable-table-container {
        max-height: 400px;
        overflow-y: auto;
    }
    
    /* Фиксированный заголовок таблицы */
    .fixed-header {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
    }
</style>

<!-- Сообщения -->
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= $errorMessage ?>
    </div>
<?php endif; ?>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= $successMessage ?>
    </div>
<?php endif; ?>

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
            <div class="col-lg-4 mb-4">
                <div class="card user-card h-100">
                    <div class="card-body text-center">
                        <div class="user-avatar bg-success">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h4>Добавить пользователя</h4>
                        <p class="text-muted">Только для администраторов</p>
                        
                        <?php if ($isAdmin): ?>
                            <form method="post" class="mt-3">
                                <input type="hidden" name="add_user" value="1">
                                
                                <div class="mb-3">
                                    <input type="text" name="surname" class="form-control" placeholder="Фамилия" required>
                                </div>
                                
                                <div class="mb-3">
                                    <input type="text" name="name" class="form-control" placeholder="Имя" required>
                                </div>
                                
                                <div class="mb-3">
                                    <input type="text" name="patronymic" class="form-control" placeholder="Отчество">
                                </div>
                                
                                <div class="mb-3">
                                    <input type="password" name="password" class="form-control" placeholder="Пароль" required>
                                </div>
                                
                                <div class="mb-3">
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
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-lines-fill"></i> Управление пользователями</span>
                        <span class="badge bg-primary">Всего: <?= count($users) ?></span>
                    </div>
                    <div class="card-body">
                        <form method="post" id="users-form">
                            <input type="hidden" name="update_users" value="1">
                            
                            <div class="scrollable-table-container"> <!-- Контейнер с прокруткой -->
                                <table class="table table-hover align-middle">
                                    <thead class="table-light fixed-header"> <!-- Фиксированный заголовок -->
                                        <tr>
                                            <th>ФИО</th>
                                            <th class="text-center">Статус</th>
                                            <th class="text-center">Активен</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($user->FIO) ?>
                                                    <?php if ($user->Status == 0): ?>
                                                        <span class="badge bg-danger ms-2">Админ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= $user->Status == 0 ? 'Администратор' : 'Пользователь' ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($isAdmin): ?>
                                                        <label class="active-toggle">
                                                            <input type="checkbox" 
                                                                   name="active[]" 
                                                                   value="<?= $user->IDUser ?>"
                                                                <?= $user->isActive ? 'checked' : '' ?>
                                                                <?= $_SESSION['IDUser'] == $user->IDUser ? 'disabled' : '' ?>>
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                    <?php else: ?>
                                                        <?= $user->isActive ? 
                                                            '<i class="bi bi-check-circle-fill text-success"></i>' : 
                                                            '<i class="bi bi-x-circle-fill text-danger"></i>' ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">
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
                                    <input class="form-control" type="file" name="csv_files[]" multiple accept=".csv" required>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Загружайте файлы в формате CSV с разделителями-запятыми
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
                                                    <span class="badge bg-<?= $result['status'] === 'success' ? 'success' : 'danger' ?>">
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

<script>
    // Активация вкладок Bootstrap
    const triggerTabList = document.querySelectorAll('#adminTabs button');
    triggerTabList.forEach(triggerEl => {
        new bootstrap.Tab(triggerEl);
    });

    // Подтверждение перед деактивацией текущего пользователя
    document.getElementById('users-form')?.addEventListener('submit', function(e) {
        const selfCheckbox = this.querySelector('input[value="<?= $_SESSION['IDUser'] ?? '' ?>"]');
        if (selfCheckbox && !selfCheckbox.checked) {
            if (!confirm('Вы деактивируете свой аккаунт! Продолжить?')) {
                e.preventDefault();
                selfCheckbox.checked = true;
            }
        }
    });
</script>