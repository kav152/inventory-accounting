<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDBrandTMC.log');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/CUDHandler.php';
    require_once __DIR__ . '/../../Entity/BrandTMC.php';
    require_once __DIR__ . '/../PropertyController.php';

    class processCUDBrandTMC extends CUDHandler
    {
        public function __construct()
        {
            DatabaseFactory::setConfig();
            parent::__construct(new PropertyController(), BrandTMC::class);
        }

        protected function prepareData($postData)
        {
            return [
                'NameBrand' => $postData['NameBrand'] ?? '',
            ];
        }

        protected function create($data, ?int $patofID = null)
        {
            $brandTMC = parent::create($data);
            if ($patofID && $brandTMC && $brandTMC->getId()) {
                $this->createLinkTypeToBrand((int) $patofID, $brandTMC->getId());
            }
            return $brandTMC;
        }

        private function createLinkTypeToBrand(int $typeTMCId, int $brandTMCId): void
        {
            try {
                $link = new LinkTypeToBrand([
                    'IDTypesTMC' => $typeTMCId,
                    'IDBrandTMC' => $brandTMCId,
                ]);
                $controller = new PropertyController();
                $controller->create($link);
            } catch (Exception $e) {
                error_log('Ошибка при создании связи TypeToBrand: ' . $e->getMessage());
                throw new Exception('Не удалось создать связь типа ТМЦ с брендом');
            }
        }

        protected function prepareResultEntity($brandTMC)
        {
            return [
                'id' => $brandTMC->getId(),
                'NameBrand' => $brandTMC->NameBrand,
            ];
        }
    }

    $handler = new processCUDBrandTMC();
    $handler->handleRequest();
} catch (Throwable $e) {
    error_log('ERROR in processCUDBrandTMC.php: ' . $e->getMessage());
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сохранения бренда: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
