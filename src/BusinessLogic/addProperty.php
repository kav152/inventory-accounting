<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/addProperty.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../Logging/Logger.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    DatabaseFactory::setConfig();
    $logger = new Logger(__DIR__ . '/../storage/logs/addProperty.log');

    $type = $_POST['typeProperty'] ?? '';
    $valueProp = $_POST['valueProp'] ?? '';
    $propertyId = (int)($_POST['property_id'] ?? 0);

    $logger->log('DATA', 'Получение данных из _POST',
    ['typeProperty' => $type,
              'valueProp' => $valueProp,
              'propertyId' => $propertyId]);


    if (!$valueProp) throw new InvalidArgumentException('Не указано название свойства');
    if (!$propertyId) throw new InvalidArgumentException('Не указан родительский ID');

    $controller = new PropertyController();
    $property = [];

    switch ($type) {
        case 'type_tmc':
            break;
        case 'brand':
            $property = $controller->addBrand($valueProp, $propertyId);
            break;
        case 'model':
            $property = $controller->addModel($valueProp, $propertyId);
            break;
        default:
            throw new InvalidArgumentException('Неизвестный тип свойства /addProperty.php');
    }


    $logger->log('addProperty', 'Возвращение данных',
    ['data' => $property]);

    $result[] = [ 
            'ID' => $property->getId(),
            'Name' => $property->getName()
        ];   

    $logger->log('addProperty', 'Возвращение данных',
    ['data' => $result]);



    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}