<?php
require_once __DIR__ . '/Database.php';

class DatabaseFactory
{
    private static array $config;

    public static function setConfig(?array $config = null)
    {
        //$set = require_once __DIR__ . '/../../setting.php';
        if ($config == null) 
        {

            $settingsPath = realpath(__DIR__ . '/../../setting.php');
            if ($settingsPath === false) 
            {
                die("Файл настроек не найден!");
            }

            $set = require $settingsPath;

            if (is_array($set)) {
                self::$config = $set;
            } else {
                $settingsPath = realpath(__DIR__ . '/../../setting.php');
                throw new RuntimeException("Переменная set '$set' не являеться массивом. адрес вызова - '$settingsPath'");
            }
        } else {
            self::$config = $config;
        }
    }

    public static function create(string $connectionName = 'databaseSRV'): Database
    {
        if (!isset(self::$config['connections'][$connectionName])) {
            throw new RuntimeException("Connection '$connectionName' не найден в конфигурации.");
        }

        return new Database(self::$config['connections'][$connectionName]);
    }

    public static function create_test(string $connectionName): Database
    {
        if (!isset(self::$config['connections'][$connectionName])) {
            throw new RuntimeException("Connection '$connectionName' не найден в конфигурации.");
        }

        return new Database(self::$config['connections'][$connectionName]);
    }
}