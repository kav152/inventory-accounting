<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processRepairInBasket.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';
header('Content-Type: application/json');

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}



DatabaseFactory::setConfig();
$repairContainer = new ItemRepairController();

$ID_TMC = $_POST['ID_TMC'] ?? 0;

if (!$ID_TMC) {
    exit(json_encode(['success' => false, 'message' => 'ID ТМЦ не указан']));
}

$result = $repairContainer->returnFromBasket($ID_TMC);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Элемент успешно возвращен из корзины']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка возврата из корзины']);
}
?>