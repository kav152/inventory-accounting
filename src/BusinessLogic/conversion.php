<?php
require_once __DIR__ . '/ConvertController.php';

header('Content-Type: application/json');
session_start();


$controller = new ConvertController();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_GET['action']) && $_GET['action'] === 'test-connection') {
            // Тестирование подключения
            $result = $controller->testDatabaseConnection(
                $input['oldDatabase'] ?? 'ConstructionAccounting',
                $input['newDatabase'] ?? 'ConstructionAccounting_test'
            );
            
            echo json_encode($result);
            
        } elseif (isset($_GET['action']) && $_GET['action'] === 'convert') {
            // Запуск конвертации
            $result = $controller->convertDatabase(
                $input['oldDatabase'] ?? 'ConstructionAccounting',
                $input['newDatabase'] ?? 'ConstructionAccounting_test',
                $input['skipDuplicates'] ?? true,
                $input['includeHistory'] ?? true,
                $input['includeRepairs'] ?? true
            );
            
            echo json_encode($result);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}