<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDBrigades.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/Brigades.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDBrigades extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemController(), Brigades::class);
    }

    protected function prepareData($postData)
    {
        return [
            'IDBrigade' => $postData['id'] ?? 0,
            'NameBrigade' => $postData['brigade_name'] ?? null,
            'IDResponsibleIssuing' => $postData['responsible'] ?? 0,
            'NameBrigadir' => $postData['brigadir'] ?? null,
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        return parent::create($data);
    }

    protected function delete($data): bool
    {
        return parent::delete($data);
    }

    protected function prepareResultEntity($brigades)
    {
        if ($brigades === true || $brigades === false) {
            return [
                'id' => 0,
                'NameBrigade' => '',
                'IDResponsibleIssuing' => 0,
                'NameBrigadir' => '',
            ];
        }
        return [
            'id' => $brigades->IDBrigade ?? 0,
            'NameBrigade' => $brigades->NameBrigade ?? '',
            'IDResponsibleIssuing' => $brigades->IDResponsibleIssuing ?? 0,
            'NameBrigadir' => $brigades->NameBrigadir ?? '',
        ];
    }
}

try {
    $handler = new processCUDBrigades();
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
        'message' => 'Ошибка сохранения бригады: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
