<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDRepairItem.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/RepairItem.php';
require_once __DIR__ . '/../ItemRepairController.php';
require_once __DIR__ . '/../ItemController.php';
require_once __DIR__ . '/../OperationType.php';

class processCUDRepairItem extends CUDHandler
{
    private $action = "";
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemRepairController(), RepairItem::class);
    }

    protected function prepareData($postData)
    {
        $this->action = $postData['action'];
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
        $itemRepairController = new ItemRepairController();
        if ($this->action === 'repair') {
            $repairItem = $itemRepairController->sendForRepair($data, null);
        } elseif ($this->action === 'writeOff') {
            $repairItem = $itemRepairController->writeOffItem($data, null);
        } else {
            throw new Exception('Неизвестное действие');
        }

        return $repairItem;
    }

    protected function prepareResultEntity($repairItem)
    {
        return [
            'id' => $repairItem->getId(),
            'ID_TMC' => $repairItem->ID_TMC,
            'IDLocation' => $repairItem->IDLocation,
            'RepairCost' => $repairItem->RepairCost,
            'InvoiceNumber' => $repairItem->InvoiceNumber,
            'RepairDescription' => $repairItem->RepairDescription
        ];
    }
}

// Использование
$handler = new processCUDRepairItem();
$handler->handleRequest();
