<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDModelTMC.log');

header('Content-Type: application/json; charset=utf-8');

try {

    require_once __DIR__ . '/CUDHandler.php';
    require_once __DIR__ . '/../../Entity/ModelTMC.php';
    require_once __DIR__ . '/../PropertyController.php';


    class processCUDModelTMC extends CUDHandler
    {
        public function __construct()
        {
            DatabaseFactory::setConfig();
            parent::__construct(new PropertyController(), ModelTMC::class);
        }

        protected function prepareData($postData)
        {
            return [
                'NameModel' => $postData['NameModel'] ?? ''
            ];
        }

        protected function create($data, int $patofID = null)
        {
            // Создаем модель
            $modelTMC = parent::create($data);

            // Если передан patofID (ID бренда), создаем связь
            if ($patofID && $modelTMC->getId()) {
                error_log('Добавляем взаимосвязь BrandToModel');
                $this->createLinkBrandToModel($patofID, $modelTMC->getId());
            }

            return $modelTMC;
        }

        /**
         * Создает связь между брендом и моделью
         */
        private function createLinkBrandToModel(int $brandTMCId, int $modelTMCId): void
        {
            $controller = new PropertyController();
            $controller->createLinkBrandToModel($brandTMCId, $modelTMCId);

            //createLinkBrandToModel
            /* try {
                 $link = new LinkBrandToModel([
                     'IDBrandTMC' => $brandTMCId,
                     'IDModel' => $modelTMCId
                 ]);

                 $controller = new PropertyController();
                 $resultLink = $controller->create($link);

                 error_log('=========== $resultLink ==========================');
                 error_log(print_r($resultLink, true));

                 if ($resultLink == null)
                     error_log("Cвязь c BrandToModel не создана: Brand=$brandTMCId, Model=$modelTMCId");
                 else
                     error_log("Создана связь BrandToModel: Brand=$brandTMCId, Model=$modelTMCId");
             } catch (Exception $e) {
                 error_log("Ошибка при создании связи BrandToModel: " . $e->getMessage());
                 throw new Exception("Не удалось создать связь бренда с моделью");
             }*/
        }

        protected function prepareResultEntity($modelTMC)
        {
            return [
                'id' => $modelTMC->getId(),
                'NameModel' => $modelTMC->NameModel
            ];
        }
    }
} catch (Throwable $e) {
    error_log("ERROR in processCUDModelTMC.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера при обработке модели ТМЦ'
    ]);
    exit;
}

$handler = new processCUDModelTMC();
$handler->handleRequest();
