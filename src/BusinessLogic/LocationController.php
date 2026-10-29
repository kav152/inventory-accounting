<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/LocationController.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../Database/SchemaEnsure.php';
require_once __DIR__ . '/../Container.php';
require_once __DIR__ . '/../Repositories/LocationRepository.php';
require_once __DIR__ . '/../Repositories/CityRepository.php';

require_once __DIR__ . '/CudService/CUDFactory.php';


class LocationController
{
    private Container $container;
    private Logger $logger;
    private CUDFactory $cudFactory;
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/LocationController.log');
        });
        $this->logger = $this->container->get(Logger::class);

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
        $this->ensureSchema();
    }

    /**
     * Колонки, которые код уже использует, но миграции могли не накатить на прод.
     */
    private function ensureSchema(): void
    {
        try {
            $pdo = $this->container->get(Database::class)->getConnection();
            SchemaEnsure::ensureLocationColumns($pdo);
        } catch (Throwable $e) {
            error_log('Location ensureSchema: ' . $e->getMessage());
        }
    }

    public function getLocations(bool $IsRepair)
    {
        $locationRepository = $this->container->get(LocationRepository::class);
        $sql = ' where IsRepair = ' . ($IsRepair ? 1 : 0);
        return $locationRepository->findBy($sql);
    }

    public function getCities()
    {
        $cityRepository = $this->container->get(CityRepository::class);
        return $cityRepository->getAll();
    }

    public function getLocation(?int $IDLocation): ?object
    {
        if (!$IDLocation) {
            $result = new Location();
            //$result->mountEmptyDocument();
            return $result;
        }

        $customersRepository = $this->container->get(LocationRepository::class);
        $cytiRepository = $this->container->get(CityRepository::class);
        // Добавляем отношение для загрузки Location
        $customersRepository->addRelationship(
            'City',
            $cytiRepository,
            'IDCity',
            'IDCity'
        );
        $result = $customersRepository->findById((int) $IDLocation, "IDLocation");

        return $result;
    }

    /**
     * Создать сущность объекта
     * @param mixed $object
     * @return Location|City|null - список сущностей
     */
    public function create($object): ?object
    {
        $result = $this->cudFactory->create($object);
        if (!$result) {
            return null;
        }

        // City создаётся через тот же controller — не трогаем City->City
        if ($result instanceof Location) {
            $cityRepository = $this->container->get(CityRepository::class);
            $cityId = (int) ($result->IDCity ?? 0);
            $result->City = $cityId > 0 ? $cityRepository->findById($cityId, "IDCity") : null;
        }
        return $result;
    }
    /**
     * Обновить сущность объекта
     * @param mixed $object
     * @return Location|City|null
     */
    public function update($object): ?object
    {
        $result = $this->cudFactory->update($object);
        if (!$result) {
            throw new Exception('Не удалось сохранить локацию в базе данных');
        }

        if ($result instanceof Location) {
            $cityRepository = $this->container->get(CityRepository::class);
            $cityId = (int) ($result->IDCity ?? 0);
            $result->City = $cityId > 0 ? $cityRepository->findById($cityId, "IDCity") : null;
        }

        return $result;
    }

    /**
     * Удалить локацию / сервисный центр
     * @param mixed $object
     * @return bool
     */
    public function delete($object): bool
    {
        return $this->cudFactory->delete($object);
    }

    /**
     * Получить текущую основную локацию (склад)
     */
    public function getMainWarehouse(): ?Location
    {
        try {
            $locationRepository = $this->container->get(LocationRepository::class);
            return $locationRepository->first(" WHERE isMainWarehouse = 1");
        } catch (PDOException $e) {
            error_log("Ошибка при получении основного склада: " . $e->getMessage());
        }

        return null;
    }
}
