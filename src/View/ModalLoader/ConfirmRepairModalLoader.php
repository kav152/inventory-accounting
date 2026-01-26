<?php
// src/views/home/Modal/confirmRepair_modal_loader.php
require_once __DIR__ . '/ModalLoader.php';

class ConfirmRepairModalLoader extends ModalLoader
{
    public function load($params = [])
    {

        DatabaseFactory::setConfig();
        $container = new ItemController();
        $confirmRepairItems = $container->getConfirmRepairItems($_SESSION["Status"], $_SESSION["IDUser"]);
        $confirmRepairCount = count($confirmRepairItems);
        $locationRepairs = $container->getLocations(true);

        ob_start();
        include __DIR__ . '/../Modal/confirmRepair_modal.php';
        return ob_get_clean();
    }
}