<?php
// мини-список списанных с главной, отдаём JSON
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processGetWrittenOffItems.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../ItemRepairController.php';

// ItemController уже стартует сессию при подключении — не дублируем
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// сбрасываем случайный вывод до json (бывало из-за warning session_start)
ob_end_clean();header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа', 'items' => []], JSON_UNESCAPED_UNICODE);
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
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('processGetWrittenOffItems: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'items' => [],
    ], JSON_UNESCAPED_UNICODE);
}
