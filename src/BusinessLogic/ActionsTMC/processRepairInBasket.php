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

$ID_TMC = $data['ID_TMC'] ?? null;
$NameTMC = $data['NameTMC'] ?? '';
$ID_Repair = isset($data['ID_Repair']) ? (int) $data['ID_Repair'] : 0;

DatabaseFactory::setConfig();
$controller = new ItemRepairController();
$success = false;

$response = [
    'success' => $success,
    'message' => '',
];

try {
    if ($ID_Repair > 0) {
        $success = $controller->RepairRecordInBasket($ID_Repair);
        $message = $success
            ? "Запись ремонта №{$ID_Repair} перемещена в корзину"
            : "Не удалось переместить запись ремонта в корзину";
    } else {
        if (!$ID_TMC) {
            throw new Exception('Не указан ID ТМЦ');
        }
        $success = $controller->RepairInBasket($ID_TMC);
        $message = $success
            ? "ТМЦ {$NameTMC} с идентификатором {$ID_TMC} перемещено в корзину"
            : "Не удалось переместить ТМЦ в корзину";
    }

    $basketItems = $controller->getBasketItems();
    $totalRepairCost_Basket = 0;
    $totalCount = 0;
    if ($basketItems) {
        foreach ($basketItems as $item) {
            $totalRepairCost_Basket += $item->RepairCost;
            $totalCount++;
        }
    }

    $response = [
        'success' => $success,
        'message' => $message,
        'totalCount' => $totalCount,
        'totalCost' => $totalRepairCost_Basket,
        'formattedTotalCost' => number_format($totalRepairCost_Basket, 2, ',', ' '),
        'ID_Repair' => $ID_Repair ?: null,
        'ID_TMC' => $ID_TMC,
    ];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
