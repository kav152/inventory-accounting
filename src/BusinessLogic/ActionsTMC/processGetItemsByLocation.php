<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processGetItemsByLocation.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemController.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа', 'items' => []]);
    exit;
}

$locationId = (int) ($_GET['locationId'] ?? $_POST['locationId'] ?? 0);
if ($locationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Не указана локация', 'items' => []]);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemController();
    $items = $controller->getItemsByLocation($locationId);
    echo json_encode([
        'success' => true,
        'message' => '',
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
