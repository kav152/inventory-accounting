<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/LegalEntityModalLoader.log');

require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/LegalEntityController.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';

class LegalEntityModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $controller = new LegalEntityController();
        $currentID = isset($params['id']) ? (int) $params['id'] : 0;
        $legal = $controller->getLegalEntity($currentID);

        ob_start();
        include __DIR__ . '/../Modal/legalEntity_modal.php';
        return ob_get_clean();
    }
}
