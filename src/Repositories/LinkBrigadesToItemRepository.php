<?php
require_once __DIR__ ."/../Entity/LinkBrigadesToItem.php";
require_once __DIR__.'/GenericRepository.php';

class LinkBrigadesToItemRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, LinkBrigadesToItem::class, 'LinkBrigadesToItem');
    }
}