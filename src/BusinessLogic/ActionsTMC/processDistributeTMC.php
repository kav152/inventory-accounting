<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processDistributeTMC.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__.'/../ItemController.php';

session_start();

// Проверка авторизации
if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$tmcIds = json_decode($data['tmc_ids'], true);
$locationId = $data['location'];
$userId = $data['user'];

if (empty($tmcIds)) {
    echo json_encode(['success' => false, 'message' => 'Не выбраны ТМЦ для передачи']);
    exit;
}

if (empty($locationId)) {
    echo json_encode(['success' => false, 'message' => 'Не выбран объект назначения']);
    exit;
}

if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'Не выбран ответственный']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $itemController = new ItemController();
    $itemController->distributeItems($tmcIds, $locationId, $userId);
    //$repository = new InventoryItemRepository();
    //$success = $repository->distributeItems($tmcIds, $locationId, $userId);
    

    $success = true;
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'ТМЦ успешно переданы']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при передаче ТМЦ11']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}