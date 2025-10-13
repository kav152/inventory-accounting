<?php
define('APP_PATH', __DIR__);
$setting = require 'setting.php';
require_once __DIR__ . '/src/Database/DatabaseFactory.php';
$setting = require APP_PATH . '\setting.php';

DatabaseFactory::setConfig($setting);

try {
    $database = DatabaseFactory::create();

    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SHOW TABLES FROM a1138175_ConstructionAccounting");
    $stmt->execute();

    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    //print_r($entities);

    foreach ($tables as $tableName) {
        echo "<p>Таблица: $tableName</p>";
    }

    // 1. Информация о подключении (host_info)
    $hostInfo = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    echo "<p>Информация о подключении: $hostInfo</p>";

    // 2. Информация о сервере (server_info)
    $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    echo "<p>Версия сервера: $serverVersion</p>";
    echo "<p>Статус сервера: $serverInfo</p>";

    // 3. Дополнительная информация
    $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $clientVersion = $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);

    echo "<p>Драйвер: $driverName</p>";
    echo "<p>Версия клиента:  $clientVersion </p>";


} catch (PDOException $e) {
    die("Не удалось установить соединение: " . $e->getMessage());
}
