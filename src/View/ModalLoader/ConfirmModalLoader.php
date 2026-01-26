<?php
// src/views/home/Modal/confirm_modal_loader.php
require_once __DIR__ . '/ModalLoader.php';

class ConfirmModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $container = new ItemController();

        $confirmItems = $container->getConfirmItems($_SESSION["Status"], $_SESSION["IDUser"]);
        $confirmCount = count($confirmItems);

        //  if ($confirmCount > 0) {
        ob_start();
        include __DIR__ . '/../Modal/confirm_modal.php';
        return ob_get_clean();
        //    }
        //    return '';
    }
}