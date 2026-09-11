<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processRepairApproval.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../ItemRepairController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Некорректный запрос');
    }

    $action = (string) ($input['action'] ?? '');
    $tmcId = (int) ($input['tmcId'] ?? 0);
    $repairId = (int) ($input['repairId'] ?? 0);

    if ($tmcId <= 0) {
        throw new Exception('Не указан ТМЦ');
    }
    if (!in_array($action, ['approve', 'reject'], true)) {
        throw new Exception('Неизвестное действие');
    }

    DatabaseFactory::setConfig();
    $controller = new ItemRepairController();

    if ($action === 'approve') {
        $controller->approveRepair($repairId, $tmcId, [
            'InvoiceNumber' => $input['invoiceNumber'] ?? '',
            'UPD' => $input['updNumber'] ?? $input['UPD'] ?? '',
            'RepairCost' => $input['repairCost'] ?? 0,
            'RepairDescription' => $input['repairDescription'] ?? '',
            'IDLocation' => (int) ($input['locationId'] ?? 0),
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Ремонт согласован, счёт сохранён',
            'tmcId' => $tmcId,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $reason = trim((string) ($input['reason'] ?? 'Отказ в согласовании ремонта'));
    $controller->rejectRepair($tmcId, $reason);
    echo json_encode([
        'success' => true,
        'message' => 'В согласовании ремонта отказано, ТМЦ возвращено',
        'tmcId' => $tmcId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('processRepairApproval: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
