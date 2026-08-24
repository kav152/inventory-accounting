<?php
declare(strict_types=1);
require_once __DIR__ . "/../Entity/InventoryItem.php";
require_once __DIR__ . '/GenericRepository.php';

class InventoryItemRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, InventoryItem::class, 'InventoryItem');

        // Добавляем отношение для загрузки Location
        $locationRepository = new LocationRepository($database);
        $this->addRelationship(
            'Location',
            $locationRepository,
            'IDLocation',
            'IDLocation'
        );

        $brandTMCRepository = new BrandTMCRepository($database);
        $this->addRelationship(
            'BrandTMC',
            $brandTMCRepository,
            'IDBrandTMC',
            'IDBrandTMC'
        );

        $modelTMCRepository = new ModelTMCRepository($database);
        $this->addRelationship(
            'ModelTMC',
            $modelTMCRepository,
            'IDModel',
            'IDModel'
        );

      /*  $registrationInventoryItemRepository = new RegistrationInventoryItemRepository($database);
        $this->addRelationship(
            'RegistrationInventoryItem',
            $registrationInventoryItemRepository,
            'ID_TMC',
            'IDRegItem'
        );*/
        $userRepository =new UserRepository($database);
        $this->addRelationship(
            'User',                     // Свойство в InventoryItem
            $userRepository,            // Репозиторий User
            'CurrentUser',              // ID пользователя в InventoryItem
            'IDUser'                    // Первичный ключ в User
        );

    }
}