<?php
require __DIR__ . '/vendor/autoload.php';

$envPath = __DIR__ . '/.env';
if (!is_file($envPath)) {
    throw new RuntimeException(
        'Файл .env не найден по пути ' . $envPath
        . '. Создайте его на сервере: cp .env.example .env && заполните DB_* / DB_*_SQL. '
        . 'Файл .env не хранится в git (секреты).'
    );
}
if (!is_readable($envPath)) {
    throw new RuntimeException(
        'Файл .env есть, но не читается (права доступа). Выполните: chmod 640 .env && chown www-data:www-data .env'
    );
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


return [
    'connections' => [
        'databaseMySQl' => [
            'driver'   => 'mysql',
            'host'     => $_ENV['DB_HOST'],
            'dbname'   => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset'  => 'utf8mb4'
        ],
        'databaseSRV' => [
            'driver'   => 'sqlsrv',
            'host'     => $_ENV['DB_HOST_SQL'],
            'dbname'   => $_ENV['DB_NAME_SQL'],
            'username' => $_ENV['DB_USER_SQL'],
            'password' => $_ENV['DB_PASSWORD_SQL'],
            'charset'  => 'UTF-8'
        ],
    ]
];
