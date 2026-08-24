<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/error_logs_ajax.log');

// Начинаем сессию для проверки прав
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка прав администратора
/*if (empty($_SESSION['Status']) || $_SESSION['Status'] != 0) {
    header('HTTP/1.0 403 Forbidden');
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}*/

// Путь к папке с логами
$logDirectory = __DIR__ . '/../../storage/logs';

// Обработка удаления файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_log') {
    header('Content-Type: application/json; charset=utf-8');
    
    $filename = $_POST['filename'] ?? '';
    
    if ($filename) {
        $filePath = $logDirectory . '/' . basename($filename);
        
        // Проверяем, что файл существует и является .log файлом в нужной директории
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'log' && 
            strpos(realpath($filePath), realpath($logDirectory)) === 0) {
            
            if (unlink($filePath)) {
                echo json_encode(['success' => true, 'message' => 'Файл удален']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при удалении файла']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Недопустимый файл']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Не указано имя файла']);
    }
    exit;
}

// Обработка GET запроса для чтения файла
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'read_log') {
    header('Content-Type: application/json; charset=utf-8');
    
    $filename = $_GET['filename'] ?? '';
    
    if ($filename) {
        $filePath = $logDirectory . '/' . basename($filename);
        
        // Проверяем, что файл существует и является .log файлом в нужной директории
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'log' && 
            strpos(realpath($filePath), realpath($logDirectory)) === 0) {
            
            $content = @file_get_contents($filePath);
            if ($content === false) {
                echo json_encode(['success' => false, 'message' => 'Ошибка чтения файла']);
            } else {
                echo json_encode([
                    'success' => true, 
                    'content' => $content,
                    'filename' => $filename,
                    'size' => filesize($filePath)
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Недопустимый файл']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Не указано имя файла']);
    }
    exit;
}

// Если запрос не распознан
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
exit;