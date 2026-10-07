<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processProposeWriteOff.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['IDUser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit;
}

if ((int) ($_SESSION['Status'] ?? -1) === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Администратор списывает напрямую («Списать инструмент»)']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$ids = $data['tmc_ids'] ?? $data['ids'] ?? [];
$reason = (string) ($data['reason'] ?? '');

if (!is_array($ids) || count($ids) === 0) {
    echo json_encode(['success' => false, 'message' => 'Выберите ТМЦ для предложения списания']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $result = $controller->proposeWriteOffByIds($ids, $reason);
    $proposed = $result['proposed'] ?? [];
    $errors = $result['errors'] ?? [];

    $ok = count($proposed) > 0;
    $message = $ok
        ? ('Отправлено админу предложений: ' . count($proposed))
        : 'Не удалось отправить предложение';
    if ($errors) {
        $message .= '. ' . implode('; ', $errors);
    }

    echo json_encode([
        'success' => $ok,
        'message' => $message,
        'proposed' => $proposed,
        'errors' => $errors,
        'proposeCount' => $controller->countProposeWriteOff(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
