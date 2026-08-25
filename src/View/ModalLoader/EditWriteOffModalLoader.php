<?php
require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';

class EditWriteOffModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        $idTmc = $params['id'] ?? null;
<<<<<<< HEAD
        
        if (!$idTmc) {

=======
        $idRepair = isset($params['repairId']) ? (int) $params['repairId'] : null;

        if (!$idTmc) {
>>>>>>> source/feature/local-updates-2026-08
            return '<div class="alert alert-danger">Не указан ID ТМЦ</div>';
        }
        DatabaseFactory::setConfig();
        $repairController = new ItemRepairController();
<<<<<<< HEAD
        $itemData = $repairController->getItemWithRepairs($idTmc);
        //print_r($itemData);
        
        
        ob_start();
        include __DIR__ . '/../Modal/edit_write_off_modal.php';

        //error_log("ГОТОВО edit_write_off_modal");
        return ob_get_clean();
    }
}
=======
        $itemData = $repairController->getItemWithRepairs((int) $idTmc, $idRepair ?: null);

        if (!$itemData || count($itemData) === 0) {
            return '<div class="alert alert-warning">Запись ремонта не найдена</div>';
        }

        $singleRepairMode = $idRepair > 0;

        ob_start();
        include __DIR__ . '/../Modal/edit_write_off_modal.php';
        return ob_get_clean();
    }
}
>>>>>>> source/feature/local-updates-2026-08
