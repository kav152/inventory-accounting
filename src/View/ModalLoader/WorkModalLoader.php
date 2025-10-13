<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';

class WorkModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $controller = new ItemController();        
        $brigades = $controller->getBrigades($_SESSION["IDUser"]);

         ob_start();
         include __DIR__ . '/../Modal/work_modal.php';
         return ob_get_clean();
    }
}