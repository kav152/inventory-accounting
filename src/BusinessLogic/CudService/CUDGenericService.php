<?php
require_once __DIR__ . '/../../Logging/Logger.php';

/**
 * Универсальный сервис для операций CUD
 * @template T of BaseEntity
 */
class CUDGenericService
{
    private object $repository;
    private string $entityName;
    private string $entityClass;
    protected Logger $logger;
    protected $db;

    /**
     * @param object $repository Репозиторий сущности
     * @param string $entityClass Класс сущности
     * @param string $entityName Название сущности для логов/ошибок
     */
    public function __construct($db, $logger, object $repository, string $entityClass, string $entityName)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->entityClass = $entityClass;
        $this->entityName = $entityName;
    }

    /**
     * Создать новую сущность
     * @param T $entityObject
     * @param int $PARTOF
     * @return T|null
     * @throws ValidationException
     */
    public function create($entityObject, int $PARTOF = 0)
    {
        try {
            // Валидация при необходимости
            // $errors = $this->validateEntity($entityObject);

            $result = $this->repository->save($entityObject);
            // $this->logger->info("{$this->entityName} создан: " . $entityObject->name);
            return $result;
        } catch (Exception $e) {
            $this->logger->log("error", "Ошибка создания {$this->entityName}: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось создать {$this->entityName}",
                $e->getCode(),
                $e,
                [$this->entityName => $entityObject->name ?? 'Unknown']
            );
        }
    }

    /**
     * Обновить сущность
     * @param T $entityObject
     * @return T|null
     * @throws ValidationException
     */
    public function update($entityObject)
    {
        try {
            $existingEntity = $this->repository->findById(
                $entityObject->getID(),
                $entityObject->getIdFieldName()
            );

            if (!$existingEntity) {
                throw new ValidationException(
                    "{$this->entityName} не найден",
                    404,
                    null,
                    ['id' => $entityObject->getID()]
                );
            }

            $this->updateProperties($entityObject, $existingEntity);
            $result = $this->repository->save($existingEntity);

            // $this->logger->info("{$this->entityName} обновлен: " . $existingEntity->name);
            return $result;
        } catch (Exception $e) {
            $this->logger->log("error", "Ошибка обновления {$this->entityName}: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось обновить {$this->entityName}",
                $e->getCode(),
                $e,
                ['id' => $entityObject->getID()]
            );
        }
    }

    /**
     * Удалить сущность
     * @param T $entityObject
     * @return bool
     * @throws ValidationException
     */
    public function delete($entityObject): bool
    {
        try {
            $entityId = $entityObject->getID();

            // Проверка связанных записей при необходимости
            // if ($this->hasRelatedRecords($entityId)) {

            $result = $this->repository->delete($entityObject);

            if ($result) {
                // $this->logger->info("{$this->entityName} удален: ID " . $entityId);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->log("error", "Ошибка удаления {$this->entityName}: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось удалить {$this->entityName}",
                $e->getCode(),
                $e,
                ['id' => $entityObject->getID()]
            );
        }
    }

    /**
     * 
     * Обновить свойства сущности (вынесено из CUDServiceObjects)
     * @param mixed $newEntity
     * @param mixed $oldEntity
     * @throws InvalidArgumentException
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

    /**
     * Специфичные операции можно вынести в отдельные сервисы
     */
    public function returnPreviousUser($entityObject): void
    {
        throw new ValidationException(
            "Функция возврата предыдущему пользователю для {$this->entityName} не реализована",
            0,
            null,
            ['operation' => 'returnPreviousUser', 'entity' => $this->entityName]
        );
    }
}
