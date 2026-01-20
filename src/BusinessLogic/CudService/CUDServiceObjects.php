<?php

require_once __DIR__ . '/../Exceptions/ValidationException.php';
require_once __DIR__ . '/../../Repositories/BaseEntity.php';
require_once __DIR__ . '/../../Logging/Logger.php';

/**
 * Абстрактный базовый класс для операций Create, Update, Delete
 * Содержит общую логику для всех типов сущностей
 * @template T
 */
abstract class CUDServiceObjects
{
    protected $db;
    protected Logger $logger;

    /**
     * @param mixed $db Объект базы данных
     * @param mixed $logger Объект логгера
     */
    public function __construct($db,Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Создать новую сущность
     * @param T $entityObject Объект сущности для создания
     * @param int $PARTOF Идентификатор родительской сущности (опционально)
     * @return T|null Созданная сущность или null при ошибке
     * @throws ValidationException
     */
    abstract public function create($entityObject, int $PARTOF = 0);

    /**
     * Обновить существующую сущность
     * @param T $entityObject Объект сущности для обновления
     * @return T|null Обновленная сущность или null при ошибке
     * @throws ValidationException
     */
    abstract function update($entityObject);

    /**
     * Удалить сущность
     * @param T $entityObject Объект сущности для удаления
     * @return bool Результат операции удаления
     * @throws ValidationException
     */
    abstract function delete($entityObject): bool;

    /**
     * Вернуть предыдущему пользователю (специфичная операция для некоторых сущностей)
     * @param T $entityObject Объект сущности
     * @return void
     * @throws ValidationException
     */
    public function returnPreviousUser($entityObject): void
    {
        throw new ValidationException(
            "Функция возврата предыдущему пользователю для " . get_class($entityObject) . " не реализована",
            0,
            null,
            ['operation' => 'returnPreviousUser', 'entity' => get_class($entityObject)]
        );
    }

    /**
     * Обновить свойства сущности на основе другого объекта
     * @param mixed $newEntity Новый объект с обновленными значениями
     * @param mixed $oldEntity Существующий объект для обновления
     * @return void
     */
    protected function updateProperties($newEntity, $oldEntity): void
    {
        if ($newEntity === null || $oldEntity === null) {
            return;
        }

        if (!$oldEntity instanceof BaseEntity || !$newEntity instanceof BaseEntity) {
            throw new InvalidArgumentException("Обе сущности должны наследоваться от BaseEntity");
        }

        $reflectionClass = new ReflectionClass($oldEntity);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            // Пропускаем свойства, которые не должны обновляться
            /*if (in_array($propertyName, ['id', 'ID', 'created_at', 'createdAt'])) {
                continue;
            }*/
            if (in_array($propertyName, $newEntity->getReadOnlyFields())) {
                continue;
            }

            if (property_exists($newEntity, $propertyName)) {
                $newValue = $property->getValue($newEntity);
                $oldValue = $property->getValue($oldEntity);

                if ($newValue !== $oldValue) {
                    $property->setValue($oldEntity, $newValue);
                }
            }
        }
    }
}