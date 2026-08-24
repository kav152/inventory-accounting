<?php
require_once __DIR__.'/../BusinessLogic/Action.php';
abstract class BaseEntity {

    /**
     * Свойства которые можно изменять
     * @return void
     */
    public abstract function getPersistableProperties(): array;
    
    protected ?int $id = null;
    private string $idFieldName = 'id'; // Значение по умолчанию

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * Получать текущую дату для полей
     * @return array
     */
    public function getAutoDateFields(): array
    {
        return [];
    }
    /**
     * Получить поля, доступные только для чтения
     * @return void
     */
    public function getReadOnlyFields(): array
    {
        return [];
    }

    // метод для получения имени ID-поля
    public function getIdFieldName(): string {
        return $this->idFieldName;
    }

    /**
     * Метод для установки имени ID-поля
     * @param string $name
     * @return void
     */
    public function setIdFieldName(string $name): void {
        $this->idFieldName = $name;
    }
}