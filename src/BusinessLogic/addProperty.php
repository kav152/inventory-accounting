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

    $dataJson = json_decode(file_get_contents('php://input'), true);
    if (!$dataJson) {
        error_log('Необнаружены данные для выполнения дейтсвия для сущости: ');
        throw new Exception('Необнаружены данные для выполнения дейтсвия для сущости: ');
    }

    $type = $dataJson['typeProperty'] ?? '';
    $valueProp = $dataJson['valueProp'] ?? '';
    $propertyId = (int) ($dataJson['property_id'] ?? 0);



 /*   $logger->log(
        'DATA',
        'Получение данных из _POST',
        [
            'typeProperty' => $type,
            'valueProp' => $valueProp,
            'propertyId' => $propertyId
        ]
    );*/

    $controller = new PropertyController();
    $property = [];


    switch ($type) {
        case 'type_tmc':

            $result = [
                'id' => 11,
                'value' => "type_tmc_00"
            ];
            break;
        case 'brand':
            // $property = $controller->addBrand($valueProp, $propertyId);
            $result = [
                'id' => 11,
                'value' => "brand_111"
            ];
            break;
        case 'model':
            // $property = $controller->addModel($valueProp, $propertyId);
            $result = [
                'id' => 11,
                'value' => "model_222"
            ];
            break;
        default:
            throw new InvalidArgumentException('Неизвестный тип свойства /addProperty.php');
    }


    /* $logger->log('addProperty', 'Возвращение данных',
     ['data' => $property]);*/

    /*   $result[] = [ 
               'ID' => $property->getId(),
               'Name' => $property->getName()
           ];  */

    $response = [
        'success' => true,
        'message' => 'Данные успешно созданы',
        'resultEntity' => $result,
        'fields' => '',
        'statusCUD' => 'create'
    ];

    /* $logger->log('addProperty', 'Возвращение данных',
     ['data' => $result]);*/

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}