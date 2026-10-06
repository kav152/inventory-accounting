<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processGetRepairHistory.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../ItemRepairController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmcId = (int) ($_GET['tmcId'] ?? $_GET['id'] ?? 0);
if ($tmcId <= 0) {
    $input = json_decode(file_get_contents('php://input'), true);
    $tmcId = (int) ($input['tmcId'] ?? $input['id'] ?? 0);
}

if ($tmcId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Не указан ТМЦ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $data = $controller->getRepairHistoryForTmc($tmcId);
    echo json_encode([
        'success' => true,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('processGetRepairHistory: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
