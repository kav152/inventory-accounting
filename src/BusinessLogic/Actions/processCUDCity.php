<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDCity.log');

try {
    require_once __DIR__ . '/CUDHandler.php';
    require_once __DIR__ . '/../../Entity/City.php';
    require_once __DIR__ . '/../LocationController.php';

    class processCUDCity extends CUDHandler
    {
        public function __construct()
        {
            DatabaseFactory::setConfig();
            parent::__construct(new LocationController(), City::class);
        }

        protected function prepareData($postData)
        {
            return [
                'IDCity' => (int) ($postData['IDCity'] ?? $postData['id'] ?? 0),
                'NameCity' => trim((string) ($postData['NameCity'] ?? '')),
                'Address' => trim((string) ($postData['Address'] ?? $postData['CityAddressNew'] ?? '')),
            ];
        }

        protected function create($data, ?int $patofID = null)
        {
            if (($data['NameCity'] ?? '') === '') {
                throw new Exception('Укажите название города');
            }
            return parent::create($data, $patofID);
        }

        protected function prepareResultEntity($city)
        {
            if (!$city) {
                throw new Exception('Город не найден');
            }
            return [
                'id' => $city->getId(),
                'NameCity' => $city->NameCity ?? '',
                'Address' => $city->Address ?? '',
            ];
        }
    }

    $handler = new processCUDCity();
    $handler->handleRequest();
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сохранения города: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
