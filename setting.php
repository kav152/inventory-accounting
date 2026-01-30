<?php
require __DIR__ . '/vendor/autoload.php';
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