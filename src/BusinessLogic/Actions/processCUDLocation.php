<?php
/**
 * CUD локаций — любой сбой должен вернуть JSON, не HTML-страницу ошибки.
 */
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');

$logDir = __DIR__ . '/../../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . '/processCUDLocation.log');

function processCUDLocation_jsonError(string $message): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
    }
    echo json_encode([
        'success' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
}

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'] ?? 0, $fatalTypes, true)) {
        return;
    }
    // Если уже успели отдать JSON — не дублируем
    $sent = headers_list();
    foreach ($sent as $h) {
        if (stripos($h, 'application/json') !== false) {
            return;
        }
    }
    processCUDLocation_jsonError(
        'Критическая ошибка PHP при сохранении локации: ' . ($err['message'] ?? 'unknown')
    );
});

try {
    require_once __DIR__ . '/CUDHandler.php';
    require_once __DIR__ . '/../../Entity/Location.php';
    require_once __DIR__ . '/../LocationController.php';

    class processCUDLocation extends CUDHandler
    {
        private LocationController $locationController;

        public function __construct()
        {
            DatabaseFactory::setConfig();
            $this->locationController = new LocationController();
            parent::__construct($this->locationController, Location::class);
        }

        protected function prepareData($postData)
        {
            return [
                'IDLocation' => (int) ($postData['id'] ?? 0),
                'NameLocation' => trim((string) ($postData['NameLocation'] ?? '')),
                'Address' => trim((string) ($postData['Address'] ?? '')),
                'Location2' => trim((string) ($postData['Location2'] ?? '')),
                'Phone' => trim((string) ($postData['Phone'] ?? '')),
                'Contacts' => trim((string) ($postData['Contacts'] ?? '')),
                'Email' => trim((string) ($postData['Email'] ?? '')),
                'isMainWarehouse' => isset($postData['isMainWarehouse']) ? (int) $postData['isMainWarehouse'] : 0,
                'FormsJointStockCompanies' => trim((string) ($postData['FormsJointStockCompanies'] ?? '')),
                'IsRepair' => isset($postData['IsRepair']) ? (int) $postData['IsRepair'] : 0,
                'IDCity' => isset($postData['IDCity']) && $postData['IDCity'] !== ''
                    ? (int) $postData['IDCity']
                    : null,
            ];
        }

        protected function create($data, ?int $patofID = null)
        {
            if (($data['NameLocation'] ?? '') === '') {
                throw new Exception('Укажите наименование локации');
            }
            if (empty($data['IDCity'])) {
                throw new Exception('Выберите город');
            }
            if (($data['Address'] ?? '') === '') {
                throw new Exception('Укажите адрес локации');
            }

            // Не делаем повторный getLocation — достаточно объекта после insert
            $location = parent::create($data, $patofID);
            if (!$location || !$location->getId()) {
                throw new Exception('Не удалось создать локацию в базе данных');
            }
            return $location;
        }

        protected function update($id, $data, int|null $patofID = null)
        {
            parent::update($id, $data);
            $location = $this->locationController->getLocation((int) ($data['IDLocation'] ?? $id));
            if (!$location || !$location->getId()) {
                throw new Exception('Не удалось загрузить локацию после сохранения');
            }
            return $location;
        }

        protected function delete($data)
        {
            $entity = new Location($data);
            $id = (int) $entity->getId();
            if ($id <= 0) {
                throw new Exception('Не указан ID локации для удаления');
            }

            $ok = $this->locationController->delete($entity);
            if (!$ok) {
                throw new Exception('Не удалось удалить локацию');
            }

            return new Location(['IDLocation' => $id]);
        }

        protected function prepareResultEntity($location)
        {
            if (!$location) {
                throw new Exception('Локация не найдена');
            }

            return [
                'id' => $location->getId(),
                'NameLocation' => $location->NameLocation ?? '',
                'Address' => $location->Address ?? '',
                'Location2' => $location->Location2 ?? '',
                'Phone' => $location->Phone ?? '',
                'Contacts' => $location->Contacts ?? '',
                'Email' => $location->Email ?? '',
                'FormsJointStockCompanies' => $location->FormsJointStockCompanies ?? '',
                'isMainWarehouse' => (int) ($location->isMainWarehouse ?? 0),
                'City' => [
                    'NameCity' => $location->City?->NameCity ?? '',
                    'Address' => $location->City?->Address ?? '',
                ],
            ];
        }
    }

    $handler = new processCUDLocation();
    $handler->handleRequest();
} catch (Throwable $e) {
    processCUDLocation_jsonError('Ошибка сохранения локации: ' . $e->getMessage());
}
