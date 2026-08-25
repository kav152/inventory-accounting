<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/routes.php';
require __DIR__ . '/src/BusinessLogic/AccessLogger.php';

// Логируем доступ к главной странице
$logAccess = new AccessLogger();
$logAccess->logPageAccess('index.php');

$router = new Routes();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath)) ?: '/';
}
if ($uri === '/' || $uri === '') {
    $uri = '/login';
}

$router->dispatch($uri);
