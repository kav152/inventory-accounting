<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/routes.php';
require __DIR__ . '/src/BusinessLogic/AccessLogger.php';

//print_r(PDO::getAvailableDrivers());

// Логируем доступ к главной странице
$logAccess = new AccessLogger();
$logAccess->logPageAccess('index.php');

$router = new Routes();
$router->dispatch("/login");
