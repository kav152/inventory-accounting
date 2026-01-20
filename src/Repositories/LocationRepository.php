<?php
require_once __DIR__ . "/../Entity/Location.php";
require_once __DIR__ . '/GenericRepository.php';
require_once __DIR__ . '/CityRepository.php';

class LocationRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, Location::class, 'Location');

        // Добавляем отношение для загрузки Location
        $cytiRepository = new CityRepository($database);
        $this->addRelationship(
            'City',
            $cytiRepository,
            'IDCity',
            'IDCity'
        );
    }
}
