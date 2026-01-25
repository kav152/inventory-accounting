<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDDistribute.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDDistribute extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(null, null);
    }

    private $currentData = [];
    protected function prepareData($postData)
    {
        $this->currentData = $postData;
        //error_log("Данные распределения: " . print_r($this->currentData, true));

        return [
            'selectedTMCIds' => json_decode($postData['selectedTMCIds'] ?? '[]', true),
            'location' => (int) ($postData['location'] ?? 0),
            'user' => (int) ($postData['user'] ?? 0)
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        // Валидация данных
        if (empty($data['tmc_ids'])) {
            throw new Exception('Не выбраны ТМЦ для передачи');
        }

        if (empty($data['idLocation'])) {
            throw new Exception('Не выбран объект назначения');
        }

        if (empty($data['idUser'])) {
            throw new Exception('Не выбран ответственный');
        }
        throw new Exception('Ошибка - при распределении ТМЦ - функция create неиспользуеться');
    }

    protected function update($id, $data, int|null $patofID = null)
    {
        $tmcIds = json_decode($this->currentData['tmc_ids'], true);
        // Преобразуем в целые числа
        $tmcIds = array_map('intval', $tmcIds);
        $itemController = new ItemController();
        $itemController->distributeItems($tmcIds, $this->currentData['location'], $this->currentData['user']);
    }

    protected function prepareResultEntity($result)
    {
        return [
        ];
    }
}

// Использование
$handler = new processCUDDistribute();
$handler->handleRequest();