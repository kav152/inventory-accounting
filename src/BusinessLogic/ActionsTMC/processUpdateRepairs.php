<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processRepairInBasket.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';
header('Content-Type: application/json');

$success = false;
$response = [
    'success' => $success,
    'message' => '',
];


try {
    //$input = json_decode(file_get_contents('php://input'), true);
    //$repairsData = $input['repairs'] ?? [];
    $repairsData = $_POST['repairs'] ?? [];

    if (empty($repairsData)) {
        $response = [
            'success' => $success,
            'message' => 'Нет данных для обновления',
        ];
        echo json_encode($response);
        exit;
    }
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $updatedCount = 0;
    foreach ($repairsData as $repairData) {
        // Проверяем, есть ли изменения
        if (!empty($repairData['ID_Repair'])) {
            $result = $controller->updateRepair($repairData);
            if ($result) {
                $success = true;
                $updatedCount++;
            }
        }
    }
    $response = [
        'success' => $success,
        'message' => "Успешно обновлено записей: $updatedCount из " . count($repairsData),
    ];
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error updating repairs: " . $e->getMessage());
    $response = [
        'success' => $success,
        'message' => 'Ошибка при обновлении данных: ' . $e->getMessage(),
    ];
    echo json_encode($response);
}