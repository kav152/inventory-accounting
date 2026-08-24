<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDTypesTMC.log');

header('Content-Type: application/json; charset=utf-8');

try {

    require_once __DIR__ . '/CUDHandler.php';
    require_once __DIR__ . '/../../Entity/TypesTMC.php';
    require_once __DIR__ . '/../PropertyController.php';

    class processCUDTypesTMC extends CUDHandler
    {
        public function __construct()
        {
            DatabaseFactory::setConfig();
            parent::__construct(new PropertyController(), TypesTMC::class);
        }

        protected function prepareData($postData)
        {
            return [
                'IDTypesTMC' => $postData['IDTypesTMC'] ?? 0,
                'NameTypesTMC' => $postData['NameTypesTMC'] ?? '',
                'NameImage' => $postData['NameImage'] ?? null
            ];
        }

        protected function create($data, ?int $patofID = null)
        {
            try {
                $typesTMC = parent::create($data);
                return $typesTMC;
            } catch (Exception $e) {
                error_log("Ошибка создания TypesTMC: " . $e->getMessage());
                throw new Exception('Не удалось создать тип ТМЦ: ' . $e->getMessage());
            }
        }


        protected function prepareResultEntity($typesTMC)
        {
            return [
                'id' => $typesTMC->getId(),
                'NameTypesTMC' => $typesTMC->NameTypesTMC,
                'NameImage' => $typesTMC->NameImage
            ];
        }
    }

    // Использование
    $handler = new processCUDTypesTMC();
    $handler->handleRequest();
} catch (Throwable $e) {
    error_log("ERROR in processCUDBrandTMC.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера при обработке бренда ТМЦ'
    ]);
    exit;
}