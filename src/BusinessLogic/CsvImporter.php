<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/CsvImporter.log');
require_once __DIR__ . '/../Logging/Logger.php';

class CsvImporter
{
    private $db;
    private $logger;
    private $orderedTables = [
        'City',
        'User',
        'TypesTMC',
        'BrandTMC',
        'ModelTMC',
        'LinkTypeToBrand',
        'LinkBrandToModel',
        'Location',
        'Brigades',
        'InventoryItem',
        'RegistrationInventoryItem',
        'CommentsHistory',
        'HistoryOperations',
        'LinkBrigadesToItem',
        'RepairItem'
    ];

    public function __construct(Database $database)
    {
        $this->db = $database;
        $container = new Container();
        $container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/CsvImporter.log');
        });
        $this->logger = $container->get(Logger::class);
    }

    public function importData(array $files): array
    {
        $results = [];
        $pdo = $this->db->getConnection();

        // Отключаем проверку внешних ключей
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->logger->log('importData', 'Выполнено', [
            'Действие' => 'Отключаем проверку внешних ключей'
        ]);

        // Очистка таблиц в обратном порядке
        $tablesForClean = array_reverse($this->orderedTables);
        $errorsOnClean = [];
        $pdo->beginTransaction();

        foreach ($tablesForClean as $table) {
            try {
                $pdo->exec("DELETE FROM `$table`");
            } catch (PDOException $e) {
                $errorsOnClean[$table] = $e->getMessage();
            }
        }

        if (!empty($errorsOnClean)) {
            $pdo->rollBack();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            foreach ($this->orderedTables as $table) {
                $results[$table] = [
                    'status' => 'error',
                    'message' => isset($errorsOnClean[$table])
                        ? 'Ошибка очистки: ' . $errorsOnClean[$table]
                        : 'Очистка отменена из-за ошибки в другой таблице'
                ];
            }
            return $results;
        }

        $pdo->commit();

        $this->logger->log('importData', 'Выполнено', [
            'Действие' => 'Очистка таблиц в обратном порядке'
        ]);

        // Импорт данных в правильном порядке
        foreach ($this->orderedTables as $table) {
            if (!isset($files[$table]) || $files[$table]['error'] !== UPLOAD_ERR_OK) {
                $results[$table] = [
                    'status' => 'error',
                    'message' => 'Файл не загружен или ошибка загрузки'
                ];
                continue;
            }

            $arr = $this->importTable($pdo, $table, $files[$table]['tmp_name']);
            $this->logger->log('importTable', 'Результат работы importTable', $arr);
            $results[$table] = $arr;
        }

        // Включаем проверку внешних ключей
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        return $results;
    }

    private function importTable(PDO $pdo, string $table, string $filePath): array
    {
        if (!file_exists($filePath)) {
            $this->logger->log('ERROR', "Файл не найден", ['table' => $table, 'path' => $filePath]);
            return ['status' => 'error', 'message' => 'Файл не найден'];
        }

        $fileSizeMB = round(filesize($filePath) / (1024 * 1024), 2);
        $this->logger->log('DEBUG', "Начало импорта таблицы", [
            'table' => $table,
            'file_size' => "$fileSizeMB MB"
        ]);

        if (($handle = fopen($filePath, 'r')) === false) {
            $this->logger->log('ERROR', "Ошибка открытия файла", ['table' => $table]);
            return ['status' => 'error', 'message' => 'Не удалось открыть файл'];
        }

        try {
            $headers = fgetcsv($handle, 0, '|', '"', '\\');
            if ($headers === false || count($headers) === 0) {
                $this->logger->log('ERROR', "Файл не содержит заголовков", ['table' => $table]);
                return ['status' => 'error', 'message' => 'Файл не содержит заголовков'];
            }

            // НАЧАЛО ТРАНЗАКЦИИ
            $pdo->beginTransaction();
            $this->logger->log('DEBUG', "Транзакция начата", ['table' => $table]);

            $columns = implode(', ', array_map(fn($col) => "`$col`", $headers));
            $placeholders = implode(', ', array_fill(0, count($headers), '?'));
            $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $this->logger->log('DEBUG', "Подготовка запроса", ['table' => $table, 'sql' => $sql]);

            $rowCount = 0;
            $batchSize = 500;
            $batchCount = 0;
            $batchStartTime = microtime(true);

            while (($row = fgetcsv($handle, 0, '|', '"', '\\')) !== false) {
                if ($table === 'RepairItem') {
                    $updIndex = array_search('UPD', $headers);
                    if ($updIndex !== false && isset($row[$updIndex]) && !empty($row[$updIndex])) {
                        $row[$updIndex] = hex2bin(str_replace('0x', '', $row[$updIndex]));
                    }
                }

                $stmt->execute($row);
                $rowCount++;
                $batchCount++;

                if ($batchCount >= $batchSize) {
                    $batchTime = round(microtime(true) - $batchStartTime, 3);
                    $this->logger->log('DEBUG', "Пакетная вставка", [
                        'table' => $table,
                        'rows' => $rowCount,
                        'batch_size' => $batchSize,
                        'batch_time' => "$batchTime sec"
                    ]);
                    $batchCount = 0;
                    $batchStartTime = microtime(true);
                }
            }

            // ФИКСАЦИЯ ТРАНЗАКЦИИ ПЕРЕД ОПЕРАЦИЯМИ С ИНДЕКСАМИ
            $pdo->commit();
            $this->logger->log('DEBUG', "Транзакция зафиксирована", ['table' => $table]);

            // ОПЕРАЦИИ С ИНДЕКСАМИ ВНЕ ТРАНЗАКЦИИ
            $pdo->exec("ALTER TABLE `$table` ENABLE KEYS");
            $this->logger->log('DEBUG', "Индексы включены", ['table' => $table]);

            $this->logger->log('INFO', "Успешный импорт таблицы", [
                'table' => $table,
                'rows' => $rowCount
            ]);

            return [
                'status' => 'success',
                'message' => "Успешно импортировано $rowCount строк"
            ];

        } catch (PDOException $e) {
            // ОТКАТ ТРАНЗАКЦИИ ТОЛЬКО ЕСЛИ ОНА АКТИВНА
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                $this->logger->log('DEBUG', "Транзакция откачена", ['table' => $table]);
            }

            $this->logger->log('ERROR', "Ошибка импорта", [
                'table' => $table,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'status' => 'error',
                'message' => 'Ошибка импорта: ' . $e->getMessage()
            ];
        } finally {
            fclose($handle);
            $this->logger->log('DEBUG', "Файл закрыт", ['table' => $table]);
        }
    }

    public function getRequiredTables(): array
    {
        return $this->orderedTables;
    }

    public function checkCommentsHistory(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['status' => 'error', 'message' => 'Файл не найден - '.$filePath];
        }

        if (($handle = fopen($filePath, 'r')) === false) {
            $this->logger->log('ERROR', "Ошибка открытия файла", ['файл' => $filePath]);
            return ['status' => 'error', 'message' => 'Не удалось открыть файл'];
        }

        $headers = fgetcsv($handle, 0, '|', '"', '\\');
        $columnCount = count($headers);

        $error = [];
        // $columns = implode(', ', array_map(fn($col) => "`$col`", $headers));
        while (($row = fgetcsv($handle, 0, '|', '"', '\\')) !== false)
        {
            if(count($row) != $columnCount)
            {
                $arrayRow = implode(', ', array_map(fn($cell) => "`$cell`", $row));
                $error += ['IDComment' => $arrayRow];
            }
        }

        return $error;
    }
}