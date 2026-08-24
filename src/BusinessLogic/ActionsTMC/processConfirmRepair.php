<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processConfirmRepair.log');

// Основные настройки для загрузки файлов
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/repairs/';
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedTypes = ['application/pdf', 'application/x-pdf'];

// Создаем директорию если не существует
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemRepairController.php';

header('Content-Type: application/json');


// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$action = $data['action'];


DatabaseFactory::setConfig();
$controller = new ItemRepairController();
$success = false;

try {


    $response = [
        'success' => false,
        'message' => 'Файл отсутсвует ',
        'file_path' => ''
    ];

    $filename = null; // Инициализируем переменную


    // Проверяем наличие файла
    if (isset($_FILES['UPD']) && $_FILES['UPD']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES['UPD'];
        // Проверка ошибок загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки: ' . $file['error']);
        }
        // Проверка типа файла
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            throw new Exception('Недопустимый тип файла. Разрешены только PDF');
        }
        // Проверка размера файла
        if ($file['size'] > $maxFileSize) {
            throw new Exception('Файл слишком большой. Максимальный размер: 5MB');
        }
        // Генерируем уникальное имя файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('repair_') . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Перемещаем файл в постоянное хранилище
        if (!move_uploaded_file($file['tmp_name'], $destination)) {

            throw new Exception('Не удалось сохранить файл');
        }
    }

    if ($action === 'repair') {
        $success = $controller->sendForRepair($data, $filename);
    } elseif ($action === 'writeOff') {
        $success = $controller->writeOffItem($data, $filename);
    } else {
        throw new Exception('Неизвестное действие');
    }

    $response = [
        'success' => true,
        'message' => 'Файл успешно загружен ',
        'file_path' => $filename ? $destination : ''
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
