<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDRepairItem.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/RepairItem.php';
require_once __DIR__ . '/../ItemRepairController.php';

class processCUDRepairItem extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemRepairController(), RepairItem::class);
    }

    protected function prepareData($postData)
    {
        return [
            'ID_Repair' => $postData['ID_Repair'] ?? '',
            'ID_TMC' => $postData['ID_TMC'],
            'IDLocation' => $postData['IDLocation'],
            'RepairCost' => $postData['RepairCost'],
            'InvoiceNumber' => $postData['InvoiceNumber'] ?? null,
            'UPD' => $postData['UPD'] ?? null,
            'RepairDescription' => $postData['RepairDescription'] ?? null,
            'DateToService' => $postData['DateToService'] ?? null,
            'DateReturnService' => $postData['DateReturnService'] ?? null,
            'inBasket' => $postData['inBasket'] ?? null,
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $repairItem = parent::create($data);
        return $repairItem;
    }

    protected function prepareResultEntity($repairItem)
    {
        return [
            'id' => $repairItem->getId(),
            'name' => $repairItem->name,
            'RelatedEntity' => [
                'value' => $repairItem->RelatedEntity->value ?? '',
            ],
            // Добавить другие поля для ответа
        ];
    }
}

// Использование
$handler = new processCUDRepairItem();
$handler->handleRequest();
