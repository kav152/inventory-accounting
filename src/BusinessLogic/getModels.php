<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $typeId = (int) ($_GET['type_id'] ?? 0);

    if (!$typeId) {
        throw new InvalidArgumentException('Не указан тип ТМЦ');
    }

    DatabaseFactory::setConfig();
    $controller = new PropertyController();
    $models = $controller->getModelsByBrand($typeId);

    $result = [];

    foreach ($models as $value) { 
                $result[] = [ 
            'ID' => $value->getId(),
            'Name' => $value->getName()
        ];
    }
    echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}