<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/SettingController.php';

class DistributeModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $controller = new ItemController(); // Создаем новый экземпляр
        $locations = $controller->getLocations();

        $settingController = new SettingController();
        $users = $settingController->getUsers();

        ob_start();
        include __DIR__ . '/../Modal/distribute_modal.php';

        return ob_get_clean();
    }
}