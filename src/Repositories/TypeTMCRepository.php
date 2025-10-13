<?php

require_once __DIR__ ."/../Entity/TypesTMC.php";
require_once __DIR__.'/GenericRepository.php';

class TypeTMCRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, TypesTMC::class, 'TypesTMC');
    }
}