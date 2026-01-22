<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/CardItemLoader.log');
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../../Logging/Logger.php';

class CardItemLoader extends ModalLoader
{    
    public function load($params = [])
    {
        error_log('Мы в ModalLoader');        
        DatabaseFactory::setConfig();
        $controller = new ItemController();
        $propertyController = new PropertyController();
       

        // Получаем параметры из запроса
        //$statusItem = $params['statusEntity'] == null ? '' : (string)$params['statusEntity'];
        $currentID = $params['id'] == null ? 0 : (int)$params['id'];        

        $inventoryItem = $controller->getInventoryItem($currentID);
        $typeTMCs = $propertyController->getTypeTMC();

        ob_start();
        include __DIR__ . '/../Modal/cardItem_modal.php';
        return ob_get_clean();
    }
}