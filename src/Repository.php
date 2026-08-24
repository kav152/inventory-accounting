<?php

require __DIR__ ."/Entity/Users.php";

class Repository
{
    public function __construct(private Database $database) {}

    private function conn() {}
/*
    public function getAllItems(): array
    {
        $pdo = $this->database->getConnection();
        $sql = $pdo->query("SELECT * FROM InventoryItem");
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }
*/
/*
    public function getAllUsers(): Collection
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->query("SELECT * FROM [User]");

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Преобразуем строку БД в объект Users
            $users[] = new Users($row);
        }
        // Возвращаем коллекцию с типом Users
        return new Collection(Users::class, $users);
    }*/
}
