<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Проверка прав администратора
/*if (empty($_SESSION['Status']) || $_SESSION['Status'] != 0) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}*/

// Путь к папке с логами
$logDirectory = __DIR__ . '/../../storage/logs';

// Функция для получения списка log файлов
function getLogFiles($directory) {
    $logFiles = [];
    
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            $filePath = $directory . '/' . $file;
            if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'log') {
                $logFiles[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }
    }
    
    // Сортируем по дате изменения (новые сверху)
    usort($logFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $logFiles;
}

// Получаем список файлов
$logFiles = getLogFiles($logDirectory);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Логи ошибок</h2>
        <button class="btn btn-primary" onclick="refreshLogs()">
            <i class="bi bi-arrow-clockwise"></i> Обновить
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($logFiles)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Лог-файлы не найдены
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Имя файла</th>
                                <th>Размер</th>
                                <th>Дата изменения</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logFiles as $logFile): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                        <?= htmlspecialchars($logFile['name']) ?>
                                    </td>
                                    <td><?= formatBytes($logFile['size']) ?></td>
                                    <td><?= date('d.m.Y H:i:s', $logFile['modified']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="viewLog('<?= htmlspecialchars($logFile['name']) ?>')"
                                                title="Просмотреть">
                                            <i class="bi bi-eye"></i> Просмотреть
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteLog('<?= htmlspecialchars($logFile['name']) ?>')"
                                                title="Удалить">
                                            <i class="bi bi-trash"></i> Удалить
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра лога -->
<div class="modal fade" id="logViewerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logFileName">Просмотр лога</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        Размер: <span id="logFileSize">0</span> | 
                        Строк: <span id="logLineCount">0</span>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyLogContent()">
                            <i class="bi bi-clipboard"></i> Копировать
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="downloadLog()">
                            <i class="bi bi-download"></i> Скачать
                        </button>
                    </div>
                </div>
                <div class="log-content-container">
                    <pre id="logContent" class="bg-dark text-light p-3 rounded" 
                         style="max-height: 60vh; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php
// Функция для форматирования размера файла
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<script>
// Текущий открытый файл
let currentLogFile = null;

// Функция просмотра лога
function viewLog(filename) {
    currentLogFile = filename;
    
    // Показать индикатор загрузки
    document.getElementById('logContent').innerHTML = '<div class="text-center"><div class="spinner-border text-light"></div></div>';
    document.getElementById('logFileName').textContent = 'Загрузка...';
    
    // Показать модальное окно
    const modal = new bootstrap.Modal(document.getElementById('logViewerModal'));
    modal.show();
    
    // Загрузить содержимое файла через отдельный PHP файл
    fetch('/src/View/AdminTabs/error_logs_ajax.php?action=read_log&filename=' + encodeURIComponent(filename))
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('logFileName').textContent = filename;
                document.getElementById('logFileSize').textContent = formatBytes(data.size);
                
                // Подсчет строк
                const lines = data.content.split('\n').length;
                document.getElementById('logLineCount').textContent = lines;
                
                // Отображаем содержимое
                document.getElementById('logContent').textContent = data.content;
            } else {
                document.getElementById('logContent').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Ошибка') + '</div>';
            }
        })
        .catch(error => {
            console.error('Ошибка загрузки:', error);
            document.getElementById('logContent').innerHTML = '<div class="alert alert-danger">Ошибка загрузки: ' + error.message + '</div>';
        });
}

// Функция удаления лога
function deleteLog(filename) {
    if (!confirm('Вы уверены, что хотите удалить файл "' + filename + '"?')) {
        return;
    }
    
    // Отправляем запрос на удаление
    const formData = new FormData();
    formData.append('action', 'delete_log');
    formData.append('filename', filename);
    
    fetch('/src/View/AdminTabs/error_logs_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Показываем уведомление
            if (typeof showNotification !== 'undefined') {
                showNotification(TypeMessage.success, data.message || 'Файл успешно удален');
            } else {
                alert(data.message || 'Файл успешно удален');
            }
            // Перезагружаем страницу через 1 секунду
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof showNotification !== 'undefined') {
                showNotification(TypeMessage.error, data.message || 'Ошибка при удалении файла');
            } else {
                alert(data.message || 'Ошибка при удалении файла');
            }
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        if (typeof showNotification !== 'undefined') {
            showNotification(TypeMessage.error, 'Ошибка сети: ' + error.message);
        } else {
            alert('Ошибка сети: ' + error.message);
        }
    });
}

// Функция обновления списка логов
function refreshLogs() {
    location.reload();
}

// Функция копирования содержимого лога в буфер обмена
function copyLogContent() {
    const content = document.getElementById('logContent').textContent;
    navigator.clipboard.writeText(content)
        .then(() => {
            if (typeof showNotification !== 'undefined') {
                showNotification(TypeMessage.success, 'Содержимое скопировано в буфер обмена');
            } else {
                alert('Содержимое скопировано в буфер обмена');
            }
        })
        .catch(err => {
            if (typeof showNotification !== 'undefined') {
                showNotification(TypeMessage.error, 'Ошибка копирования: ' + err);
            } else {
                alert('Ошибка копирования: ' + err);
            }
        });
}

// Функция скачивания лога
function downloadLog() {
    if (!currentLogFile) return;
    
    const content = document.getElementById('logContent').textContent;
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = currentLogFile;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Вспомогательная функция для форматирования размера
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Обработчик закрытия модального окна
document.getElementById('logViewerModal').addEventListener('hidden.bs.modal', function () {
    currentLogFile = null;
    document.getElementById('logContent').textContent = '';
    document.getElementById('logFileName').textContent = '';
    document.getElementById('logFileSize').textContent = '0';
    document.getElementById('logLineCount').textContent = '0';
});
</script>