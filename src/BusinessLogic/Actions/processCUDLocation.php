<?php
ob_start();
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
        //error_log("Данные Location: " . print_r($postData, true));

        return [
            'IDLocation' => (int) ($postData['id'] ?? 0),
            'NameLocation' => $postData['NameLocation'] ?? null,
            'Address' => $postData['Address'] ?? null,
            'Location2' => $postData['Location2'] ?? null,
            'Phone' => $postData['Phone'] ?? null,
            'Contacts' => $postData['Contacts'] ?? null,
            'Email' => $postData['Email'] ?? null,
            'idRelatedEntity' => $postData['idRelatedEntity'] ?? null,
            'isMainWarehouse' => isset($postData['isMainWarehouse']) ? (int) $postData['isMainWarehouse'] : 0,
            'FormsJointStockCompanies' => trim((string) ($postData['FormsJointStockCompanies'] ?? '')),
            'IsRepair' => isset($postData['IsRepair']) ? (int) $postData['IsRepair'] : 0,
            'IDCity' => isset($postData['IDCity']) ? (int) $postData['IDCity'] : null,
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $location = parent::create($data);
        if (!$location || !$location->getId()) {
            return $location;
        }
        return $this->locationController->getLocation((int) $location->getId());
    }

    protected function update($id, $data, int|null $patofID = null)
    {
        parent::update($id, $data);
        $location = $this->locationController->getLocation((int) $data['IDLocation']);
        if (!$location || !$location->getId()) {
            throw new Exception('Не удалось загрузить локацию после сохранения');
        }

        return $location;
    }

    protected function prepareResultEntity($location)
    {
        if (!$location) {
            throw new Exception('Локация не найдена');
        }

        return [
            'id' => $location->getId(),
            'NameLocation' => $location->NameLocation ?? '',
            'Address' => $location->Address ?? '',
            'Location2' => $location->Location2 ?? '',
            'Phone' => $location->Phone ?? '',
            'Contacts' => $location->Contacts ?? '',
            'Email' => $location->Email ?? '',
            'FormsJointStockCompanies' => $location->FormsJointStockCompanies ?? '',
            'isMainWarehouse' => (int) ($location->isMainWarehouse ?? 0),
            'City' => [
                'NameCity' => $location->City?->NameCity ?? '',
                'Address' => $location->City?->Address ?? '',
            ],
        ];
    }
}

// Использование
$handler = new processCUDLocation();
$handler->handleRequest();