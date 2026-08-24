<?php
declare(strict_types=1);
require_once __DIR__ ."/../Entity/RegistrationInventoryItem.php";
require_once __DIR__.'/GenericRepository.php';

class RegistrationInventoryItemRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, RegistrationInventoryItem::class, 'RegistrationInventoryItem');

        $userRepository =new UserRepository($database);
        $this->addRelationship(
            'User',                     // Свойство в InventoryItem
            $userRepository,            // Репозиторий User
            'CurrentUser',              // ID пользователя в InventoryItem
            'IDUser'                    // Первичный ключ в User
        );
    }
}