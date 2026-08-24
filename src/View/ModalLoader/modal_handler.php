<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/modal_handler.log');
session_start();

// Конфигурация модальных окон
$modalConfig = require_once __DIR__ . '/../../../data/modal_types.php';

if (isset($_GET['type'])) {
    $type = $_GET['type'];

    if (array_key_exists($type, $modalConfig)) {

        $config = $modalConfig[$type];
        require_once __DIR__ . '/' . basename($config['loader']) . '.php';

        $loader = new $config['loader']();
        echo $loader->load($_GET);
    } else {
        error_log("Неизвестный тип модалки - $type");
        echo '<div class="alert alert-danger">Неизвестный тип модального окна</div>';
    }
}