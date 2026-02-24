<?php
date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/access.log');

class AccessLogger
{
    private $logDir;
    private $logFile;
    private $logFiles = [
        'access.log',
        'GenericRepository.log',
        'ItemController.log',
        'CommentsHistory.log',
        'CsvImporter.log',
        'processCUDTypesTMC.log',
        'processCUDBrandTMC.log',
        'processCUDModelTMC.log',
        'processCUDLocation.log',
        'GenericRepository.log',
        'processCUDRepairItem.log',
        'ItemRepairController.log',
        'LocationController.log',
        'UserController.log',
        'SettingController.log',
        'PropertyController.log',
        'HistoryOperationsController.log'
    ];

    public function __construct()
    {
        $this->logDir = $logDir ?? __DIR__ . '/../../src/storage/logs';
        $this->logDir = rtrim($this->logDir, '/');

        // Проверяем/создаем директорию с проверкой прав
        $this->ensureLogDirectory();

        // Создаем все файлы логов из списка
        $this->createLogFiles();

        $this->logFile = $logFile ?? $this->logDir . '/access.log';
    }

    /**
     * Проверяет и создает директорию для логов, устанавливает права
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            if (!@mkdir($this->logDir, 0777, true) && !is_dir($this->logDir)) {
                throw new RuntimeException("Не удалось создать директорию логов: {$this->logDir}");
            }

            // Устанавливаем права на директорию
            @chmod($this->logDir, 0777);
            // ДАем права 
            @chown($this->logDir, '0777! www-data');


        }

        // Проверяем доступность директории для записи
        if (!is_writable($this->logDir)) {
            // Пытаемся исправить права
            @chmod($this->logDir, 0777);

            if (!is_writable($this->logDir)) {
                throw new RuntimeException("Директория логов недоступна для записи: {$this->logDir}");
            }
        }
    }

    /**
     * Создает все файлы логов из списка и устанавливает права
     */
    private function createLogFiles(): void
    {
        foreach ($this->logFiles as $logFile) {
            $filePath = $this->logDir . '/' . $logFile;
            
            // Создаем файл если не существует
            if (!file_exists($filePath)) {
                @touch($filePath);
                
                // Устанавливаем права на файл
                @chmod($filePath, 0644);
                @chown($filePath, '0777! www-data');
                
            }
        }
    }



    /**
     * Получает реальный IP адрес клиента
     * Учитывает прокси и заголовки X-Forwarded-For, X-Real-IP
     */
    public function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Проверяем заголовки, которые устанавливает Nginx
        $headers = [
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // Берем первый IP из списка (могут быть через запятую)
                $ipList = explode(',', $_SERVER[$header]);
                $ip = trim($ipList[0]);
                break;
            }
        }

        // Фильтруем IP адрес
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'INVALID_IP';
    }

    /**
     * Получает информацию о пользовательском агенте
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    }

    /**
     * Получает текущий URL
     */
    public function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Записывает лог в файл
     */
    public function log(string $action, array $additionalData = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getClientIp();
        $userAgent = $this->getUserAgent();
        $url = $this->getCurrentUrl();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';

        // Базовые данные
        $logData = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'method' => $method,
            'url' => $url,
            'user_agent' => $userAgent,
            'action' => $action
        ];

        // Добавляем дополнительные данные
        $logData = array_merge($logData, $additionalData);

        // Добавляем сессию если есть
        if (session_status() === PHP_SESSION_ACTIVE) {
            $logData['session_id'] = session_id();
            if (isset($_SESSION['IDUser'])) {
                $logData['user_id'] = $_SESSION['IDUser'];
            }
            if (isset($_SESSION['FIO'])) {
                $logData['user_name'] = $_SESSION['FIO'];
            }
        }

        // Преобразуем в строку
        $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Записываем в файл
        file_put_contents(
            $this->logFile,
            $logLine . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        error_log("Access Log: {$action} from IP: {$ip}");
    }

    /**
     * Логирование попытки входа
     */
    public function logLoginAttempt(string $username, bool $success, string $message = ''): void
    {
        $this->log('LOGIN_ATTEMPT', [
            'username' => $username,
            'success' => $success,
            'message' => $message,
            'post_data' => $this->sanitizePostData()
        ]);
    }

    /**
     * Логирование успешного входа
     */
    public function logLoginSuccess(string $username): void
    {
        $this->log('LOGIN_SUCCESS', [
            'username' => $username
        ]);
    }

    /**
     * Логирование доступа к странице
     */
    public function logPageAccess(string $pageName): void
    {
        $this->log('PAGE_ACCESS', [
            'page' => $pageName,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null
        ]);
    }

    /**
     * Логирование выхода из системы
     */
    public function logLogout(): void
    {
        $this->log('LOGOUT');
    }

    /**
     * Очищает POST данные от паролей для безопасности
     */
    private function sanitizePostData(): array
    {
        $postData = $_POST ?? [];

        // Маскируем чувствительные поля
        $sensitiveFields = ['password', 'pass', 'pwd', 'secret', 'token'];

        foreach ($sensitiveFields as $field) {
            if (isset($postData[$field])) {
                $postData[$field] = '***HIDDEN***';
            }
        }

        return $postData;
    }

    /**
     * Чтение логов с фильтрацией
     */
    public function readLogs(int $limit = 100, array $filters = []): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        $count = 0;

        // Читаем с конца (новые логи внизу)
        for ($i = count($lines) - 1; $i >= 0 && $count < $limit; $i--) {
            $logData = json_decode($lines[$i], true);

            if ($logData && $this->filterLog($logData, $filters)) {
                $logs[] = $logData;
                $count++;
            }
        }

        return $logs;
    }

    /**
     * Фильтрация логов
     */
    private function filterLog(array $logData, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $key => $value) {
            if ($value === '') {
                continue; // Пропускаем пустые значения фильтров
            }

            switch ($key) {
                case 'ip':
                    if (!isset($logData['ip']) || stripos($logData['ip'], $value) === false) {
                        return false;
                    }
                    break;

                case 'user_id':
                    if (!isset($logData['user_id']) || (int) $logData['user_id'] !== (int) $value) {
                        return false;
                    }
                    break;

                case 'action':
                    if (!isset($logData['action'])) {
                        return false;
                    }

                    // Специальная обработка для LOGIN_SUCCESS
                    if ($value === 'LOGIN_SUCCESS') {
                        if (
                            $logData['action'] !== 'LOGIN_ATTEMPT' ||
                            !isset($logData['success']) ||
                            $logData['success'] !== true
                        ) {
                            return false;
                        }
                    } else if ($logData['action'] !== $value) {
                        return false;
                    }
                    break;

                default:
                    // Для других фильтров проверяем наличие и равенство
                    if (!isset($logData[$key]) || $logData[$key] != $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}
?>