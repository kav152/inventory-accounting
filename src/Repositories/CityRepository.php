<?php
require_once __DIR__ . "/../Entity/City.php";
require_once __DIR__ . '/GenericRepository.php';

class CityRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, City::class, 'City');
    }
}