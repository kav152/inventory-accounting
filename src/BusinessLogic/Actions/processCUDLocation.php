<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDLocation.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/Location.php';
require_once __DIR__ . '/../LocationController.php';

class processCUDLocation extends CUDHandler
{
    private LocationController $locationController;
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new LocationController(), Location::class);
        $this->locationController = new LocationController();
    }

    protected function prepareData($postData)
    {
        error_log("Данные Location: " . print_r($postData, true));

        return [
            // НАСТРОИТЬ ПОЛЯ ПОД КОНКРЕТНУЮ СУЩНОСТЬ
            'IDLocation' => (int) $postData['id'],
            'NameLocation' => $postData['NameLocation'] ?? null,
            'Address' => $postData['Address'] ?? null,
            'idRelatedEntity' => $postData['idRelatedEntity'] ?? null,
            'isMainWarehouse' => $postData['isMainWarehouse'],
            'FormsJointStockCompanies' => $postData['FormsJointStockCompanies'] ?? null,
            'IsRepair' => $postData['IsRepair'],
            'IDCity' => $postData['IDCity'],
            // Добавить другие поля
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $location = parent::create($data);
        return $location;
    }
    protected function update($id, $data, int|null $patofID = null)
    {
        $location = parent::update($data['IDLocation'], $data);
        $result = $this->locationController->getLocation($data['IDLocation']);
        //$result = $this->locationController->getLocations(0);
        error_log(print_r($result, true));
        return $location;
    }

    protected function prepareResultEntity($location)
    {
        return [
            'id' => $location->getId(),
            'NameLocation' => $location->NameLocation,
            'Address' => $location->Address,
            'City' => [
                'NameCity' => $location->City->NameCity,
            ],
            /*'isMainWarehouse' => $location->isMainWarehouse,
            'FormsJointStockCompanies' => $location->FormsJointStockCompanies,
            'IsRepair' => $location->IsRepair,*/
        ];
    }
}

// Использование
$handler = new processCUDLocation();
$handler->handleRequest();