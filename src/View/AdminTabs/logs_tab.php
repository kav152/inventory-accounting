<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/logs_tab.log');
require_once __DIR__ . '/../../BusinessLogic/AccessLogger.php';

// Проверка прав администратора
//session_start();
/*if (empty($_SESSION['Status']) || $_SESSION['Status'] != 0) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}*/

$logger = new AccessLogger();
$filters = [];

// Применяем фильтры если есть и они не пустые
if (isset($_GET['ip']) && trim($_GET['ip']) !== '') {
    $filters['ip'] = trim($_GET['ip']);
}
if (isset($_GET['user_id']) && trim($_GET['user_id']) !== '') {
    $filters['user_id'] = (int) $_GET['user_id'];
}
if (isset($_GET['action']) && trim($_GET['action']) !== '') {
    $filters['action'] = $_GET['action'];
}

$logs = $logger->readLogs(100, $filters);
?>


<div class="container mt-4">
    <h2>Логи доступа</h2>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Фильтры</h5>
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="ip" placeholder="IP адрес"
                        value="<?= htmlspecialchars($_GET['ip'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" name="user_id" placeholder="ID пользователя"
                        value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="action">
                        <option value="">Все действия</option>
                        <option value="LOGIN_ATTEMPT" <?= ($_GET['action'] ?? '') == 'LOGIN_ATTEMPT' ? 'selected' : '' ?>>
                            Попытки входа</option>
                        <option value="LOGIN_SUCCESS" <?= ($_GET['action'] ?? '') == 'LOGIN_SUCCESS' ? 'selected' : '' ?>>
                            Успешные входы</option>
                        <option value="LOGOUT" <?= ($_GET['action'] ?? '') == 'LOGOUT' ? 'selected' : '' ?>>Выходы
                        </option>
                        <option value="PAGE_ACCESS" <?= ($_GET['action'] ?? '') == 'PAGE_ACCESS' ? 'selected' : '' ?>>
                            Доступ к страницам</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Фильтровать</button>
                    <a href="?" class="btn btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица логов -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Время</th>
                            <th>IP</th>
                            <th>Действие</th>
                            <th>Пользователь</th>
                            <th>Детали</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Логов не найдено</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($log['timestamp'] ?? '') ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip'] ?? '') ?></code></td>
                                    <td>
                                        <span class="badge bg-<?=
                                            // Определяем цвет баджа в зависимости от действия
                                            match ($log['action'] ?? '') {
                                                'LOGIN_ATTEMPT' => isset($log['success']) && $log['success'] ? 'success' : 'danger',
                                                'LOGIN_SUCCESS' => 'success',
                                                'LOGOUT' => 'warning',
                                                default => 'info'
                                            }
                                            ?>">
                                            <?= htmlspecialchars($log['action'] ?? '') ?>
                                            <?php if (isset($log['success'])): ?>
                                                (<?= $log['success'] ? 'Успех' : 'Ошибка' ?>)
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            // Определяем имя пользователя и ID
                                            $username = $log['user_name'] ?? $log['username'] ?? null;
                                            $user_id = $log['user_id'] ?? null;
                                            
                                            if (!empty($username) || !empty($user_id)):
                                        ?>
                                            <?php if (!empty($username)): ?>
                                                <?= htmlspecialchars($username) ?>
                                                <?php if (!empty($user_id)): ?>
                                                    (ID: <?= htmlspecialchars($user_id) ?>)
                                                <?php endif; ?>
                                            <?php elseif (!empty($user_id)): ?>
                                                Пользователь ID: <?= htmlspecialchars($user_id) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Гость
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            // Список полей, которые не нужно показывать в деталях
                                            $excludedFields = ['timestamp', 'ip', 'action', 'user_id', 'user_name', 'username', 'session_id'];
                                            
                                            foreach ($log as $key => $value) {
                                                if (!in_array($key, $excludedFields) && $value !== null && $value !== '') {
                                                    echo htmlspecialchars("$key: " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value)) . "<br>";
                                                }
                                            }
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>