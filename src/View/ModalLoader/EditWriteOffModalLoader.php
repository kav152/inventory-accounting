<?php
require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';

class EditWriteOffModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        $idTmc = $params['id'] ?? null;
        
        if (!$idTmc) {

            return '<div class="alert alert-danger">Не указан ID ТМЦ</div>';
        }
        DatabaseFactory::setConfig();
        $repairController = new ItemRepairController();
        $itemData = $repairController->getItemWithRepairs($idTmc);
        //print_r($itemData);
        
        
        ob_start();
        include __DIR__ . '/../Modal/edit_write_off_modal.php';

        //error_log("ГОТОВО edit_write_off_modal");
        return ob_get_clean();
    }
}