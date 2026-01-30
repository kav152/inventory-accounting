<?php
require_once __DIR__ . '/CUDGenericService.php';
require_once __DIR__ . '/../Exceptions/ValidationException.php';
require_once __DIR__ . '/../../Logging/Logger.php';


class CUDFactory
{
    private $db;
    private $logger;
    private $container;
    private array $handlers;

    public function __construct($db, $logger, Container $container)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->container = $container;
        $this->handlers = $this->registerHandlers();
    }

    /**
     * Автоматическая регистрация обработчиков
     */
    private function registerHandlers(): array
    {
        return [
            'TypesTMC' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(TypesTMCRepository::class),
                TypesTMC::class,
                'TypesTMC'
            ),
            'BrandTMC' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(BrandTMCRepository::class),
                BrandTMC::class,
                'BrandTMC'

            ),
            'ModelTMC' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(ModelTMCRepository::class),
                ModelTMC::class,
                'ModelTMC'
            ),
            'LinkTypeToBrand' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(LinkTypeToBrandRepository::class),
                LinkTypeToBrand::class,
                'LinkTypeToBrand'
            ),
            'LinkBrandToModel' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(LinkBrandToModelRepository::class),
                LinkBrandToModel::class,
                'LinkBrandToModel'
            ),
            'InventoryItem' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(InventoryItemRepository::class),
                InventoryItem::class,
                'InventoryItem'
            ),
            'RegistrationInventoryItem' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(RegistrationInventoryItemRepository::class),
                RegistrationInventoryItem::class,
                'RegistrationInventoryItem'
            ),
            'User' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(UserRepository::class),
                User::class,
                'User'
            ),
            'Location' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(LocationRepository::class),
                Location::class,
                'Location'
            ),
            'City' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(CityRepository::class),
                City::class,
                'City'
            ),
            'Brigades' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(BrigadesRepository::class),
                Brigades::class,
                'Brigades'
            ),
            'RepairItem' => fn() => new CUDGenericService(
                $this->db,
                $this->logger,
                $this->container->get(RepairItemRepository::class),
                RepairItem::class,
                'RepairItem'
            ),
        ];
    }
    //RepairItem
    /**
     * Упрощенный метод получения обработчика
     */
    private function getCUDHandler($entityObject): CUDGenericService
    {
        //error_log('entityObject');
        //error_log(print_r($entityObject, true));
        $entityType = $entityObject->getTypeEntity();

        if (!isset($this->handlers[$entityType])) {
            throw new ValidationException(
                "CUD обработчик для типа '{$entityType}' не найден",
                500,
                null,
                [
                    'type' => $entityType,
                    'available_handlers' => array_keys($this->handlers)
                ]
            );
        }

        return $this->handlers[$entityType]();
    }

    /**
     * Динамическая регистрация новых обработчиков
     */
    public function registerHandler(string $type, Closure $factory): void
    {
        $this->handlers[$type] = $factory;
    }

    /**
     * Создать сущность
     * @param mixed $entityObject Объект сущности для создания
     * @param int $patofID Идентификатор родительской сущности (опционально)
     * @return mixed Созданная сущность или null при ошибке
     * @throws ValidationException
     */
    public function create($entityObject, int $patofID = 0)
    {
        try {
            $handler = $this->getCUDHandler($entityObject);
            return $handler->create($entityObject, $patofID);
        } catch (ValidationException $e) {
            throw $e; // Пробрасываем ValidationException без изменений
        } catch (Exception $e) {
            $this->logger->error("Ошибка при создании сущности: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось создать сущность",
                $e->getCode(),
                $e,
                ['entity_type' => $entityObject->getType()]
            );
        }
    }

    /**
     * Обновить сущность
     * @param mixed $entityObject Объект сущности для обновления
     * @return mixed Обновленная сущность или null при ошибке
     * @throws ValidationException
     */
    public function update($entityObject)
    {
        try {
            $handler = $this->getCUDHandler($entityObject);
            return $handler->update($entityObject);
        } catch (ValidationException $e) {
            throw $e; // Пробрасываем ValidationException без изменений
        } catch (Exception $e) {
            $this->logger->error("Ошибка при обновлении сущности: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось обновить сущность",
                $e->getCode(),
                $e,
                ['entity_type' => $entityObject->getType(), 'id' => $entityObject->getID()]
            );
        }
    }

    /**
     * Удалить сущность
     * @param mixed $entityObject Объект сущности для удаления
     * @return bool Результат операции
     * @throws ValidationException
     */
    public function delete($entityObject): bool
    {
        try {
            $handler = $this->getCUDHandler($entityObject);
            return $handler->delete($entityObject);
        } catch (ValidationException $e) {
            throw $e; // Пробрасываем ValidationException без изменений
        } catch (Exception $e) {
            $this->logger->error("Ошибка при удалении сущности: " . $e->getMessage());
            throw new ValidationException(
                "Не удалось удалить сущность",
                $e->getCode(),
                $e,
                ['entity_type' => $entityObject->getType(), 'id' => $entityObject->getID()]
            );
        }
    }
}
