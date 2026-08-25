<?php
require_once __DIR__ . '/ModalLoader.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';

class EditWriteOffModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        $idTmc = $params['id'] ?? null;
        $idRepair = isset($params['repairId']) ? (int) $params['repairId'] : null;

        if (!$idTmc) {
            return '<div class="alert alert-danger">Не указан ID ТМЦ</div>';
        }
        DatabaseFactory::setConfig();
        $repairController = new ItemRepairController();
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
