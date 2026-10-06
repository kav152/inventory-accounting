<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processProposeWriteOffDecision.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';

header('Content-Type: application/json; charset=utf-8');

if ((int) ($_SESSION['Status'] ?? 1) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только администратор']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string) ($input['action'] ?? '');
$tmcId = (int) ($input['tmcId'] ?? 0);
$repairId = (int) ($input['repairId'] ?? 0);
$reason = (string) ($input['reason'] ?? '');

if ($tmcId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Некорректный запрос']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    if ($action === 'approve') {
        $controller->approveProposedWriteOff($tmcId, $repairId);
        echo json_encode(['success' => true, 'message' => 'Списание утверждено', 'tmcId' => $tmcId], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $controller->rejectProposedWriteOff($tmcId, $reason);
    echo json_encode(['success' => true, 'message' => 'Предложение отклонено', 'tmcId' => $tmcId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
