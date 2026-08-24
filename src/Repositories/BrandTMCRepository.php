<?php
require_once __DIR__ ."/../Entity/BrandTMC.php";
require_once __DIR__.'/GenericRepository.php';

class BrandTMCRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, BrandTMC::class, 'BrandTMC');
    }
}