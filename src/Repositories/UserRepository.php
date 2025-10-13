<?php
require_once __DIR__ ."/../Entity/User.php";
require_once __DIR__.'/GenericRepository.php';

class UserRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, User::class, '[User]');
    }
}