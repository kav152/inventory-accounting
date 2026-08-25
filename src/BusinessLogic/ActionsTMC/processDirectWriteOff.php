<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processDirectWriteOff.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';

header('Content-Type: application/json');

if ((int) ($_SESSION['Status'] ?? 1) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Списание инструмента доступно только администратору']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$ids = $data['tmc_ids'] ?? $data['ids'] ?? [];
$reason = (string) ($data['reason'] ?? '');

if (!is_array($ids) || count($ids) === 0) {
    echo json_encode(['success' => false, 'message' => 'Выберите ТМЦ для списания']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();
    $result = $controller->directWriteOffByIds($ids, $reason);
    $written = $result['written'] ?? [];
    $errors = $result['errors'] ?? [];

    $ok = count($written) > 0;
    $message = $ok
        ? ('Списано: ' . count($written) . ' ТМЦ')
        : 'Не удалось списать выбранные ТМЦ';
    if ($errors) {
        $message .= '. ' . implode('; ', $errors);
    }

    echo json_encode([
        'success' => $ok,
        'message' => $message,
        'written' => $written,
        'errors' => $errors,
        'atWorkCount' => (int) ($result['atWorkCount'] ?? 0),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
