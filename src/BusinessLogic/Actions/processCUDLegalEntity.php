<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDLegalEntity.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/LegalEntity.php';
require_once __DIR__ . '/../LegalEntityController.php';

class processCUDLegalEntity extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new LegalEntityController(), LegalEntity::class);
    }

    protected function prepareData($postData)
    {
        return [
            'IDLegalEntity' => (int) ($postData['id'] ?? $postData['IDLegalEntity'] ?? 0),
            'NameLegalEntity' => trim((string) ($postData['NameLegalEntity'] ?? '')),
            'isActive' => isset($postData['isActive']) ? (int) $postData['isActive'] : 1,
        ];
    }

    protected function prepareResultEntity($entity)
    {
        if (!$entity) {
            throw new Exception('Юр. лицо не найдено');
        }
        return [
            'id' => $entity->getId(),
            'IDLegalEntity' => $entity->getId(),
            'NameLegalEntity' => $entity->NameLegalEntity ?? '',
            'isActive' => (int) ($entity->isActive ?? 1),
        ];
    }
}

try {
    $handler = new processCUDLegalEntity();
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
        'message' => 'Ошибка сохранения юр. лица: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
