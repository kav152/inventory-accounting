<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDReturnFromWork.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDReturnFromWork extends CUDHandler
{
    private $currentData = [];
    private $result_tmcIds = [];
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemController(), InventoryItem::class);
    }

    protected function prepareData($postData)
    {
        $this->currentData = $postData;
        //error_log("Данные распределения: " . print_r($this->currentData, true));

        return [
            'selectedTMCIds' => json_decode($postData['selectedTMCIds'] ?? '[]', true),
            'brigade_id' => $postData['brigade_id']
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        throw new Exception('Ошибка - при передачи в работу ТМЦ - функция create неиспользуеться');
    }

    protected function update($id, $data, int|null $patofID = null)
    {
        $tmcIds = json_decode($this->currentData['tmc_ids'], true);
        $itemController = new ItemController();

        $brigadeId = trim($this->currentData['brigade_id'], '"'); // Убираем кавычки
        $brigadeId = (int)$brigadeId; // Преобразуем в целое число

        foreach ($tmcIds as $tmcId) {
            $result = $itemController->returnTMCtoWork($tmcId, $brigadeId);

            $result_tmcIds['id'] = $tmcId;
        }
    }
    
    protected function prepareResultEntity($result)
    {        
        return [];
    }
}

// Использование
$handler = new processCUDReturnFromWork();
$handler->handleRequest();
