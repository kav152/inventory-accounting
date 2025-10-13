<?php
require_once __DIR__ ."/../Entity/Brigades.php";
require_once __DIR__.'/GenericRepository.php';

class BrigadesRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, Brigades::class, 'Brigades');
    }
}