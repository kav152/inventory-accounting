<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDRepairInBasket.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/RepairItem.php';
require_once __DIR__ . '/../ItemRepairController.php';

class processCUDRepairInBasket extends CUDHandler
{
    private $totalCount = 0;
    private $totalRepairCost_Basket = 0;

    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemRepairController(), RepairItem::class);
    }

    protected function prepareData($postData)
    {
        return [
            'id' => (int) ($postData['ID_TMC'] ?? $postData['id'] ?? 0),
            'ID_Repair' => (int) ($postData['ID_Repair'] ?? 0),
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $repairItem = parent::create($data);
        return $repairItem;
    }

    /** Вернуть из корзины (раньше ошибочно вызывался toggle RepairInBasket) */
    protected function update($id, $data, ?int $patofID = null)
    {
        $itemRepairController = new ItemRepairController();
        $repairId = (int) ($data['ID_Repair'] ?? 0);
        $tmcId = (int) ($data['id'] ?? $id ?? 0);

        if ($repairId > 0) {
            $isResult = $itemRepairController->returnRepairRecordFromBasket($repairId);
        } elseif ($tmcId > 0) {
            $isResult = $itemRepairController->returnFromBasket($tmcId);
        } else {
            throw new Exception('Не указан ID записи или ТМЦ для возврата из корзины');
        }

        if (!$isResult) {
            throw new Exception('Не удалось вернуть запись из корзины');
        }

        $basketItems = $itemRepairController->getBasketItems();
        $this->totalRepairCost_Basket = 0;
        $this->totalCount = 0;
        if ($basketItems) {
            foreach ($basketItems as $item) {
                $this->totalRepairCost_Basket += (float) ($item->RepairCost ?? 0);
                $this->totalCount++;
            }
        }

        return [
            'id' => $tmcId,
            'ID_Repair' => $repairId,
            'totalCount' => $this->totalCount,
            'totalCost' => $this->totalRepairCost_Basket,
            'formattedTotalCost' => number_format($this->totalRepairCost_Basket, 2, ',', ' '),
        ];
    }

    /** Очистить всю корзину (statusEntity = delete) */
    protected function delete($data): bool
    {
        $itemRepairController = new ItemRepairController();
        if (!$itemRepairController->clearBasket()) {
            throw new Exception('Не удалось очистить корзину');
        }
        $this->totalCount = 0;
        $this->totalRepairCost_Basket = 0;
        return true;
    }

    protected function prepareResultEntity($repairItem)
    {
        return [
            'totalCount' => $this->totalCount,
            'totalCost' => $this->totalRepairCost_Basket,
            'formattedTotalCost' => number_format($this->totalRepairCost_Basket, 2, ',', ' ')
        ];
    }
}

// Использование
$handler = new processCUDRepairInBasket();
$handler->handleRequest();