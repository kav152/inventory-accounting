<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDSendToService.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDSendToService extends CUDHandler
{
    private $currentData = [];
    private $success = true;
    private $messages = [];

    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemController(), InventoryItem::class);
    }

    protected function prepareData($postData)
    {
        $this->currentData = $postData;
        //error_log("Данные для возврата/отправки в сервис: " . print_r($this->currentData, true));

        return [
            'items' => $postData['items'] ?? [],
            'statusService' => $postData['statusService']
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        throw new Exception('Ошибка - при отправке/возврате ТМЦ в сервис - функция create неиспользуеться');
    }

    protected function update($id, $data, int|null $patofID = null)
    {
        $statusService = $data['statusService'] ?? 0;
        $items = $data['items'] ?? [];

        $itemController = new ItemController();
        foreach ($items as $item) {
            $result = $itemController->sendToService($item['id'], (int)$statusService, $item['reason']);
            if (!$result) {
                $this->success = false;
                $this->messages[] = "Ошибка при отправке ТМЦ в сервис: {$item['id']}";
            }
        }
    }

    protected function prepareResultEntity($result)
    {
        return [
            'success' => $this->success,
            'messages' => $this->messages
        ];
    }
}

$handler = new processCUDSendToService();
$handler->handleRequest();