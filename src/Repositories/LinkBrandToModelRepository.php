<?php
require_once __DIR__ ."/../Entity/LinkBrandToModel.php";
require_once __DIR__.'/GenericRepository.php';

class LinkBrandToModelRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, LinkBrandToModel::class, 'LinkBrandToModel');
    }
}