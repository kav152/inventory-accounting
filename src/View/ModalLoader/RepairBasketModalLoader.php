<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/RepairBasketModalLoader.log');

require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';
include_once __DIR__ . '/../../Database/DatabaseFactory.php';

class RepairBasketModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $itemRepairController = new ItemRepairController();

        $basketItems = $itemRepairController->getBasketItems();
        //error_log(print_r($basketItems, true));

        $groupedItems = [];
        foreach ($basketItems as $item) {
            $id = $item->ID_TMC;
            if (!isset($groupedItems[$id])) {
                $groupedItems[$id] = [
                    'main' => $item,          // первый (или любой) ремонт для отображения основной информации
                    'repairs' => []            // все ремонты для этого ТМЦ (если нужны детали)
                ];
            }
            $groupedItems[$id]['repairs'][] = $item;
        }
        
        

        ob_start();
        include __DIR__ . '/../Modal/basket_modal.php';
        return ob_get_clean();
    }
}
