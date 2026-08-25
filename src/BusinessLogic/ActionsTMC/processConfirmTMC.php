<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processConfirmTMC.log');
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../ItemController.php';

header('Content-Type: application/json');
<<<<<<< HEAD
//session_start();

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
=======

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$allowed = ['accept', 'reject', 'cancelWriteOff'];
if (!$id || !in_array($action, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

>>>>>>> feature/local-updates-2026-08
DatabaseFactory::setConfig();
$controller = new ItemController();
$success = false;

try {
    if ($action === 'accept') {
        $success = $controller->confirmItem($id);
    } elseif ($action === 'reject') {
        $success = $controller->rejectItem($id);
<<<<<<< HEAD
    }
    elseif ($action === 'cancelWriteOff') {
        //$success = $controller->cancelWriteOffTMC($id);
        $success = true;
    }

    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
=======
    } elseif ($action === 'cancelWriteOff') {
        $success = $controller->cancelWriteOffTMC($id);
    }

    echo json_encode([
        'success' => (bool) $success,
        'message' => $success ? 'Ок' : 'Операция не выполнена',
        'id' => $id,
        'status' => $action === 'accept'
            ? StatusItem::Released
            : ($action === 'cancelWriteOff' ? StatusItem::NotDistributed : StatusItem::Released),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
>>>>>>> feature/local-updates-2026-08
