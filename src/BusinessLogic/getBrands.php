<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../Logging/Logger.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $typeId = (int) ($_GET['type_id'] ?? 0);

    if (!$typeId) {
        throw new InvalidArgumentException('Не указан тип ТМЦ');
    }

    DatabaseFactory::setConfig();
    $controller = new PropertyController();
    $logger = new Logger(__DIR__ . '/../storage/logs/getBrands.log');
    $brands = $controller->getBrandsByTypeTMC($typeId);

    $result = [];

   // error_log(print_r($brands, true));

    foreach ($brands as $value) {
        $result[] = [
            'ID' => $value->BrandTMC->getId(),
            'Name' => $value->BrandTMC->NameBrand
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