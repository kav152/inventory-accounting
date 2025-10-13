<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/createInventoryItem.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';


header('Content-Type: application/json');

try {

    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data)) {
        throw new Exception("Нет данных для сохранения");
    }
    $typeId = 1;

    if (!$typeId) {
        throw new InvalidArgumentException('Не указан тип ТМЦ');
    }
    
    $result = null;
    DatabaseFactory::setConfig();
    $controllerItem = new ItemController();
    $item = $controllerItem->createItemInventory($data);


    $result = $data["IDTypesTMC"];
    
    echo json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    //echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}