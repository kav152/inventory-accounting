<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processGetWrittenOffItems.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа', 'items' => []]);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $items = $controller->getWrittenOffSummary(80);
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items),
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'items' => [],
    ]);
}
