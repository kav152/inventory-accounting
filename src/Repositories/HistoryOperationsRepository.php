<?php

require_once __DIR__ ."/../Entity/HistoryOperations.php";
require_once __DIR__.'/GenericRepository.php';

class HistoryOperationsRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, HistoryOperations::class, 'HistoryOperations');
    }
}