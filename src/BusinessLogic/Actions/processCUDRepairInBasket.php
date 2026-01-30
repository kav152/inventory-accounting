<?php
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
        error_log("Данные RepairItem: " . print_r($postData, true));

        return [
            'id' => $postData['ID_TMC'] ?? 0,
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $repairItem = parent::create($data);
        return $repairItem;
    }
    protected function update($id, $data, ?int $patofID = null)
    {
        $itemRepairController = new ItemRepairController();
        $isResult = $itemRepairController->RepairInBasket($data['id']);

        if ($isResult) {
            $basketItems = $itemRepairController->getBasketItems();
            foreach ($basketItems as $item) {
                $this->totalRepairCost_Basket += $item->RepairCost;
                $this->totalCount++;
            }
        }


        //$result = parent::update($id, $data, $patofID);


        return null;
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