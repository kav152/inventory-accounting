<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/LocationController.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
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
            return new Logger(__DIR__ . '/../storage/logs/CustomersController.log');
        });
        $this->logger = $this->container->get(Logger::class);

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
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
        $cityRepository = $this->container->get(CityRepository::class);
        $city = $cityRepository->findById($result->IDCity, "IDCity");

        $result->City = $city;
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
        $cityRepository = $this->container->get(CityRepository::class);
        $city = $cityRepository->findById($result->IDCity, "IDCity");

        $result->City = $city;

        return $result;
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
