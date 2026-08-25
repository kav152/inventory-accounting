<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDCity.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/City.php';
require_once __DIR__ . '/../LocationController.php';

class processCUDCity extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new LocationController(), City::class);
    }

    protected function prepareData($postData)
    {
        //error_log("Данные City: " . print_r($postData, true));

        return [
<<<<<<< HEAD
            // НАСТРОИТЬ ПОЛЯ ПОД КОНКРЕТНУЮ СУЩНОСТЬ
            'IDCity' => (int) $postData['IDCity'] ?? 0,
            'NameCity' => $postData['NameCity'] ?? 'Наименвоание города не указано',
=======
            'IDCity' => (int) ($postData['IDCity'] ?? 0),
            'NameCity' => $postData['NameCity'] ?? 'Наименование города не указано',
            'Address' => $postData['Address'] ?? null,
>>>>>>> source/feature/local-updates-2026-08
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $city = parent::create($data);
        return $city;
    }

    protected function prepareResultEntity($city)
    {
        return [
            'id' => $city->getId(),
            'NameCity' => $city->NameCity,
<<<<<<< HEAD
=======
            'Address' => $city->Address,
>>>>>>> source/feature/local-updates-2026-08
        ];
    }
}

// Использование
$handler = new processCUDCity();
$handler->handleRequest();