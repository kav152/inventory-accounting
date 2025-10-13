<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processUpdateUser.log');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../SettingController.php';
require_once __DIR__ . '/../../Logging/Logger.php';

header('Content-Type: application/json');
$logger = new Logger(__DIR__ . '/../../storage/logs/processUpdateUser.log');

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['users'])) {
    $logger->log('Ошибка: Данные не получены или некорректны', "",$data);
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

DatabaseFactory::setConfig();
$settingControl = new SettingController();

$response = [
    'success' => false,
    'message' => 'Ошибка обновления данных пользователей',
    'file_path' => ''
];

$changed = false;

try {

    foreach($data['users'] as $userId => $userData)
    {        
        if($settingControl->updateUser($userData) != null)
        {
            $changed = true;
        }
    }

    $response = [
    'success' => $changed,
    'message' => $changed ? 'Обновление выполнено' : 'Пользователи не изменялись',
    'file_path' => ''
];



} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);