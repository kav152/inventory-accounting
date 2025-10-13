<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';
class SendToServiceModalLoader
{
    public function load($params = [])
    {
        ob_start();
        include __DIR__ . '/../Modal/service_modal.php';
        return ob_get_clean();
    }

}