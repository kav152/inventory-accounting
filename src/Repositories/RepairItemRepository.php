<?php
declare(strict_types=1);
require_once __DIR__ . "/../Entity/RepairItem.php";
require_once __DIR__ . '/GenericRepository.php';

class RepairItemRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, RepairItem::class, 'RepairItem');

        $registrationRepository = new RegistrationInventoryItemRepository($database);
        $this->addRelationship(
            'RegistrationInventoryItem',
            $registrationRepository,
            'ID_TMC', // Поле в RepairItem
            'IDRegItem' // Поле в RegistrationInventoryItem
        );
    }
}