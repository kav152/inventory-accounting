<?php
abstract class BaseEntity {

    public abstract function getPersistableProperties();
    /**
     * метод для получения имени ID-поля
     * @return void
     */
    public abstract function getIdFieldName(): string;
    
    abstract function getId(): int;

    public abstract function setId(int $id): void;

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
    
    /**
     * Получить название сущности
     * @return string - возращает название сущности
     */
    abstract function getTypeEntity(): string;
}