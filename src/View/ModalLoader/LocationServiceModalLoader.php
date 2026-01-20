<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/LocationModalLoader.log');
require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/LocationController.php';
include_once __DIR__ . '/../../Database/DatabaseFactory.php';

class LocationServiceModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $locationController = new LocationController();


        $currentID = $params['id'] == null ? 0 : (int)$params['id'];
        $location = $locationController->getLocation($currentID);

        // Устанавливаем IsRepair = 1 для сервисных центров при создании
        if ($currentID == 0) {
            $location->IsRepair = 1;
        }

        $cities = $locationController->getCities();

        ob_start();
        include __DIR__ . '/../Modal/location_modal.php';
        return ob_get_clean();
    }
}
