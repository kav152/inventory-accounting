<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processWorkTMC.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__.'/../ItemController.php';

session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tmcIds = $data['tmc_ids'] ?? [];
$brigadeId = $data['brigade_id'] ?? 0;

if (empty($tmcIds)) {
    echo json_encode(['success' => false, 'message' => 'Не выбраны ТМЦ для передачи']);
    exit;
}


if (!$brigadeId) {
    echo json_encode(['success' => false, 'message' => 'Не выбрана бригада']);
    exit;
}

try {    
    DatabaseFactory::setConfig();
    $itemController = new ItemController();    
    $success = false;
    $messages = [];
    
    foreach ($tmcIds as $tmcId) {        
        $result = $itemController->assignToBrigade($tmcId, $brigadeId);
        if (!$result) {
            $success = false;
            $messages[] = "Ошибка при передаче ТМЦ ID: $tmcId";
        }
        else{
            $success = true;
        }
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'ТМЦ успешно переданы в работу'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Частичная ошибка: ' . implode('; ', $messages)
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage()
    ]);
}