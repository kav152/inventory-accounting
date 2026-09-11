<?php
ob_start();
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../storage/logs/processSaveLocationLegal.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Entity/Location.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../../Repositories/LocationRepository.php';
require_once __DIR__ . '/../../Repositories/InventoryItemRepository.php';
require_once __DIR__ . '/../ItemController.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';

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

    $tmcId = (int) ($input['tmcId'] ?? 0);
    $locationId = (int) ($input['locationId'] ?? 0);
    $legalEntity = trim((string) ($input['legalEntity'] ?? ''));

    if ($tmcId <= 0) {
        throw new Exception('Не указан ТМЦ');
    }
    if ($locationId <= 0) {
        throw new Exception('Выберите локацию');
    }

    DatabaseFactory::setConfig();
    $db = DatabaseFactory::create();
    $itemController = new ItemController();
    $item = $itemController->getInventoryItem($tmcId);
    if (!$item || (int) ($item->ID_TMC ?? 0) <= 0) {
        throw new Exception('ТМЦ не найден');
    }

    $locationRepo = new LocationRepository($db);
    $location = $locationRepo->findById($locationId, 'IDLocation');
    if (!$location) {
        throw new Exception('Локация не найдена');
    }

    // если в карточке сменили локацию — привязываем ТМЦ к ней
    if ((int) ($item->IDLocation ?? 0) !== $locationId) {
        $itemRepo = new InventoryItemRepository($db);
        $itemRepo->updateScalarField($tmcId, 'IDLocation', $locationId, 'ID_TMC');
    }

    $currentLegal = trim((string) ($location->FormsJointStockCompanies ?? ''));
    if ($legalEntity !== $currentLegal) {
        $locationRepo->updateLegalEntity($locationId, $legalEntity);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Юр. лицо сохранено',
        'legalEntity' => $legalEntity,
        'locationId' => $locationId,
        'locationName' => (string) ($location->NameLocation ?? ''),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('processSaveLocationLegal: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
