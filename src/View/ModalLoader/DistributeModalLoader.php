<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';
require_once __DIR__ . '/../../BusinessLogic/SettingController.php';
require_once __DIR__ . '/../../BusinessLogic/LegalEntityController.php';

class DistributeModalLoader extends ModalLoader
{
    public function load($params = [])
    {
        DatabaseFactory::setConfig();
        $controller = new ItemController();
        $locations = $controller->getLocations() ?? [];

        $settingController = new SettingController();
        $users = $settingController->getUsers();

        $legalEntities = [];
        try {
            $legalController = new LegalEntityController();
            foreach ($legalController->getLegalEntityNames(true) as $name) {
                $legalEntities[$name] = $name;
            }
        } catch (Throwable $e) {
            error_log('DistributeModalLoader legal: ' . $e->getMessage());
        }
        foreach ($locations as $loc) {
            $locLegal = trim((string) ($loc->FormsJointStockCompanies ?? ''));
            if ($locLegal !== '') {
                $legalEntities[$locLegal] = $locLegal;
            }
        }
        ksort($legalEntities, SORT_NATURAL | SORT_FLAG_CASE);

        ob_start();
        include __DIR__ . '/../Modal/distribute_modal.php';

        return ob_get_clean();
    }
}