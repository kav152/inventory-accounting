<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCreateItem.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemController.php';

header('Content-Type: application/json');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

try {
    // Получаем данные из формы
    $data = [
        'id' => $_POST['id'] ?? '',
        'IDTypesTMC' => $_POST['idTypeTMC'] ?? 0,
        'IDBrandTMC' => $_POST['idBrand'] ?? 0,
        'IDModel' => $_POST['idModel'] ?? 0,
        'NameTMC' => $_POST['nameTMC'] ?? '',
        'SerialNumber' => $_POST['serialNumber'] ?? null
    ];

    // Валидация обязательных полей
    if (empty($data['IDTypesTMC']) || $data['IDTypesTMC'] == 0) {
        throw new Exception('Не выбран тип ТМЦ');
    }

    if (empty($data['IDBrandTMC']) || $data['IDBrandTMC'] == 0) {
        throw new Exception('Не выбран бренд');
    }

    if (empty($data['IDModel']) || $data['IDModel'] == 0) {
        throw new Exception('Не выбрана модель');
    }

    if (empty(trim($data['NameTMC']))) {
        throw new Exception('Не заполнено наименование');
    }

    // Если отмечено "Серийный номер отсутствует", очищаем поле
    if (isset($_POST['checkSerialNum']) && $_POST['checkSerialNum'] === 'on') {
        $data['SerialNumber'] = null;
    }

    // Получаем статус действия из формы
    $statusItem = $_POST['statusItem'] ?? '';

    DatabaseFactory::setConfig();
    // Создаем контроллер и выполняем действие
    $itemController = new ItemController();
    $message = "Ошибка при создании ТМЦ";
    $resultItem = [];

    if ($statusItem == 'create' || $statusItem == 'create_analog') {
        $result = $itemController->createItemInventory($data);
        $message = "ТМЦ успешно создан";

    }
    if ($statusItem == 'edit') {

        $currentID = $_POST['id'] ?? 0;
        if ($currentID > 0) {
            $result = $itemController->updateItemInventory($data);
            $message = "ТМЦ успешно обновлен";
        } else
            $message = "Не верно указан ID редактируемого ТМЦ. Отмена обновления ТМЦ";
    }

    if ($result) {        
        $result = $itemController->getInventoryItem($result->ID_TMC);
        $resultItem = [
            'id' => $result->ID_TMC,
            'name' => $result->NameTMC,
            'serialNumber' => $result->SerialNumber,
            'brand' => $result->BrandTMC->NameBrand,
            'responsible' => "ТЕСТ"/*$result->User->FIO*/ ,
            'status' => "ТЕСТ" /*$result->Status*/,
            'location' => "ТЕСТ"            
        ];
    }





    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'id' => $result->ID_TMC,
            'resultItem' => $resultItem,
            'statusItem' => $statusItem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);

        //throw new Exception('Ошибка при создании ТМЦ');
    }
} catch (Exception $e) {
    error_log("Ошибка создания ТМЦ: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
