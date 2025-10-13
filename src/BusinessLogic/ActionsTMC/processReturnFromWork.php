<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processWorkTMC.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__.'/../ItemController.php';

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$tmcIds = $data['tmc_ids'] ?? [];
$brigadeId = $data['brigade_id'] ?? 0;

if (empty($tmcIds)) {
    echo json_encode(['success' => false, 'message' => 'Не выбраны ТМЦ для возвращения на склад']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $itemController = new ItemController();
    $success = false;
    
    foreach ($tmcIds as $tmcId) {        
        $result = $itemController->returnTMCtoWork($tmcId, $brigadeId);
        if (!$result) {
            $success = false;
            $messages[] = "Ошибка при возврате ТМЦ на склад: $tmcId";
        }
        else{
            $success = true;
        }
    }
        
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => $success, 'message' => $e->getMessage()]);
}