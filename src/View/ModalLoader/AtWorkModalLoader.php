<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';

class AtWorkModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $controller = new ItemController();

        $brigadesToItems = $controller->getBrigadesToItems($_SESSION["Status"], $_SESSION["IDUser"]);
        if ($brigadesToItems != null) {
            $brigadesToItemsCount = count($brigadesToItems);
            $atWorkGroups = $controller->getAtWorkItemsGrouped($_SESSION["Status"], $_SESSION["IDUser"]);
        }

        //if ($brigadesToItemsCount > 0) {
        ob_start();
        include __DIR__ . '/../Modal/at_work_modal.php';
        return ob_get_clean();
        // }
        //return '';
    }
}
