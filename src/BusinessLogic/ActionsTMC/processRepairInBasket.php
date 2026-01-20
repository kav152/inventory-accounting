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

$ID_TMC = $data['ID_TMC'];
$NameTMC = $data['NameTMC'];
//$ID_TMC = 52;

//error_log($ID_TMC);
//error_log($NameTMC);
DatabaseFactory::setConfig();
$controller = new ItemRepairController();
$success = false;

$response = [
    'success' => $success,
    'message' => '',
];

try {
    $success = $controller->RepairInBasket($ID_TMC);
    //error_log("RepairInBasket выполнено");
    //error_log($success);
    // Пересчитываем общую сумму
    $basketItems = $controller->getBasketItems();
    $totalRepairCost_Basket = 0;
    $totalCount = 0;
    foreach ($basketItems as $item) {
        $totalRepairCost_Basket += $item->RepairCost;
        $totalCount++;
    }


    $response = [
        'success' => $success,
        'message' => `ТМЦ {$NameTMC} c идификатором {$ID_TMC} пермещено в корзину`,
        'totalCount' => $totalCount,
        'totalCost' => $totalRepairCost_Basket,
        'formattedTotalCost' => number_format($totalRepairCost_Basket, 2, ',', ' ')
    ];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
