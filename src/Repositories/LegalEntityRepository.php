<?php
require_once __DIR__ . '/../Entity/LegalEntity.php';
require_once __DIR__ . '/GenericRepository.php';

class LegalEntityRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, LegalEntity::class, 'LegalEntity');
    }
}
