<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processUpdateRepairs.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';
header('Content-Type: application/json; charset=utf-8');

$success = false;
$response = [
    'success' => $success,
    'message' => '',
];

try {
    $repairsData = $_POST['repairs'] ?? [];

    if (empty($repairsData)) {
        echo json_encode([
            'success' => false,
            'message' => 'Нет данных для обновления',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $updatedCount = 0;
    $force = !empty($_POST['force']);
    $errors = [];
    foreach ($repairsData as $repairData) {
        if (empty($repairData['ID_Repair'])) {
            continue;
        }
        try {
            $result = $force
                ? $controller->saveRepairFormData($repairData)
                : $controller->updateRepair($repairData);
            if ($result) {
                $success = true;
                $updatedCount++;
            }
        } catch (Throwable $e) {
            $errors[] = '№' . ($repairData['ID_Repair'] ?? '?') . ': ' . $e->getMessage();
        }
    }
    if ($errors !== []) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при обновлении данных: ' . implode('; ', $errors),
            'updated' => $updatedCount,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'success' => $success,
        'message' => "Успешно обновлено записей: $updatedCount из " . count($repairsData),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error updating repairs: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при обновлении данных: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
