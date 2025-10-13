<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/createBrigade.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Logging/Logger.php';

session_start();

if (!isset($_SESSION['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$NameBrigade = $_POST['brigade_name'] ?? '';
$NameBrigadir = $_POST['brigadir'] ?? '';
$IDResponsibleIssuing = $_POST['responsible'] ?? 0;

// После получения переменных:
error_log("Creating brigade: 
    NameBrigade=$NameBrigade, 
    NameBrigadir=$NameBrigadir, 
    IDResponsibleIssuing=$IDResponsibleIssuing");


if (empty($NameBrigade) || empty($NameBrigadir)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
    exit;
}

try {
    DatabaseFactory::setConfig();
    $controllerItem = new ItemController();
    $brigades = new Brigades([
        'NameBrigade' => $NameBrigade,
        'NameBrigadir' => $NameBrigadir,
        'IDResponsibleIssuing' => $_SESSION["IDUser"]
    ]);
    $resultBrigades = $controllerItem->createBrigade($brigades);
    
    echo json_encode([
        'success' => true,
        'id' => $resultBrigades->IDBrigade,
        'message' => 'Бригада успешно создана'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при создании бригады: ' . $e->getMessage()
    ]);
}