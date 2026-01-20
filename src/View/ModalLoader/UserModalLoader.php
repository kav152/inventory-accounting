<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/UserModalLoader.log');
require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/UserController.php';
include_once __DIR__ . '/../../Database/DatabaseFactory.php';


class UserModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();        
        $userController = new UserController();

        $currentID = $params['id'] == null ? 0 : (int) $params['id'];
        $user = $userController->getUser($currentID);


        ob_start();
        include __DIR__ . '/../Modal/user_modal.php';
        return ob_get_clean();
    }
}