<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processSendToService.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemController.php';

//session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? []; // Массив с id и reason
$statusService = $data['statusService'];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Не выбраны ТМЦ для отправки в сервис']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $itemController = new ItemController();
    $success = true;
    $messages = [];

    foreach ($items as $item) {
        $result = $itemController->sendToService($item['id'], $statusService,$item['reason']);
        if (!$result) {
            $success = false;
            $messages[] = "Ошибка при отправке ТМЦ в сервис: {$item['id']}";
        }
    }

    echo json_encode(['success' => $success, 'message' => implode(', ', $messages)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
