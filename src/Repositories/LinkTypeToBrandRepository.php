<?php
require_once __DIR__ ."/../Entity/LinkTypeToBrand.php";
require_once __DIR__.'/GenericRepository.php';

class LinkTypeToBrandRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, LinkTypeToBrand::class, 'LinkTypeToBrand');
    }
}