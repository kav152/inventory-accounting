<?php
require __DIR__ . '/vendor/autoload.php';

$envPath = __DIR__ . '/.env';
if (!is_file($envPath)) {
    throw new RuntimeException(
        'Файл .env не найден по пути ' . $envPath
        . '. Создайте его на сервере: cp .env.example .env && заполните DB_*_SQL. '
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

$host = trim((string) ($_ENV['DB_HOST_SQL'] ?? ''));
$dbname = trim((string) ($_ENV['DB_NAME_SQL'] ?? ''));
$user = trim((string) ($_ENV['DB_USER_SQL'] ?? ''));
$password = (string) ($_ENV['DB_PASSWORD_SQL'] ?? '');

if ($host === '' || $dbname === '' || $user === '') {
    throw new RuntimeException(
        'В .env заполните DB_HOST_SQL, DB_NAME_SQL, DB_USER_SQL, DB_PASSWORD_SQL (MySQL DB_HOST/DB_NAME не используются).'
    );
}

return [
    'connections' => [
        'databaseSRV' => [
            'driver'   => 'sqlsrv',
            'host'     => $host,
            'dbname'   => $dbname,
            'username' => $user,
            'password' => $password,
            'charset'  => 'UTF-8'
        ],
    ]
];
