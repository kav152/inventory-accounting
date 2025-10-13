<?php
require_once __DIR__ ."/../Entity/Location.php";
require_once __DIR__.'/GenericRepository.php';

class LocationRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, Location::class, 'Location');
    }
}