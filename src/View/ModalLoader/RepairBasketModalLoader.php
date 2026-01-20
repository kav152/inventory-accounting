<?php
    date_default_timezone_set('Europe/Moscow');
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../storage/logs/RepairBasketModalLoader.log');

    require_once __DIR__ . '/ModalLoader.php';
    require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';
    include_once __DIR__ . '/../../Database/DatabaseFactory.php';

    class RepairBasketModalLoader extends ModalLoader
    {
        public function load($params = [])
        {
            DatabaseFactory::setConfig();        
            $itemRepairController = new ItemRepairController();

            // ЗАГРУЗКА ДОПОЛНИТЕЛЬНЫХ ДАННЫХ (НАСТРОИТЬ)
            // Пример для связанных сущностей
            // $allRelatedEntities = $[yourEntity]Controller->getAllRelatedEntities();

           // $currentID = $params['id'] == null ? 0 : (int)$params['id'];
           // $[yourEntity] = $currentID > 0 ? $[yourEntity]Controller->get[YourEntity]($currentID) : null;

            // Если сущность не существует, создаем пустой объект
           /* if (!$[yourEntity]) {
                $[yourEntity] = new [YourEntity]();
                $[yourEntity]->mountEmptyDocument();
            }*/
            $basketItems = $itemRepairController->getBasketItems();

            ob_start();
            include __DIR__ . '/../Modal/basket_modal.php';
            return ob_get_clean();
        }
    }