<?php

require_once __DIR__ ."/../Entity/ModelTMC.php";
require_once __DIR__.'/GenericRepository.php';

class ModelTMCRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, ModelTMC::class, 'ModelTMC');
    }
}