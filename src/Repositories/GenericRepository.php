<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/GenericRepository.log');
require __DIR__ . '/../Repositories/RepositoryInterface.php';
require __DIR__ . '/Collection.php';
require_once __DIR__ . '/../BusinessLogic/Action.php';

class GenericRepository implements RepositoryInterface
{
    private string $entityClass;
    private string $tableName;
    private array $relationships = [];
    private array $cache = []; // Кэш для связанных сущностей

    public function __construct(
        private Database $database,
        string $entityClass,
        string $tableName
    ) {
        $this->entityClass = $entityClass;
        $this->tableName = $tableName;

        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException("Класс $entityClass не существует!");
        }
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function getAll(string $query = null): ?Collection
    {
        $pdo = $this->database->getConnection();
        $finalQuery = $query ?? "SELECT * FROM {$this->tableName}";
        
        $startTime = microtime(true);
        $stmt = $pdo->query($finalQuery);
        $loadTime = microtime(true) - $startTime;
        
        // Логируем только медленные запросы
        if ($loadTime > 1.0) {
            $this->logAction('SLOW_QUERY', "Query took {$loadTime}s: {$finalQuery}");
        }

        $entities = [];
        $batchData = [];
        
        // Собираем все данные для пакетной загрузки связей
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batchData[] = $row;
        }

        if (empty($batchData)) {
            return null;
        }

        // Пакетная загрузка всех связей
        $this->preloadRelationships($batchData);

        // Гидратируем сущности с предзагруженными данными
        foreach ($batchData as $row) {
            $entities[] = $this->hydrate($row);
        }

        return new Collection($this->entityClass, $entities);
    }

    /**
     * Предзагрузка всех связей для пакета данных
     */
    private function preloadRelationships(array &$batchData): void
    {
        if (empty($this->relationships) || empty($batchData)) {
            return;
        }

        foreach ($this->relationships as $property => $config) {
            $foreignKeys = [];
            $keyMap = [];

            // Собираем все внешние ключи
            foreach ($batchData as $index => $row) {
                $foreignKeyValue = $row[$config['foreign_key']] ?? null;
                if ($foreignKeyValue !== null) {
                    $foreignKeys[$foreignKeyValue] = true;
                    $keyMap[$foreignKeyValue][] = $index;
                }
            }

            if (empty($foreignKeys)) {
                continue;
            }

            // Загружаем все связанные сущности одним запросом
            $relatedEntities = $this->batchLoadRelatedEntities(
                $config['repository'],
                array_keys($foreignKeys),
                $config['target_id_field']
            );

            // Распределяем связанные сущности по основным данным
            foreach ($relatedEntities as $relatedId => $relatedEntity) {
                if (isset($keyMap[$relatedId])) {
                    foreach ($keyMap[$relatedId] as $index) {
                        // Сохраняем связанную сущность в кэш для hydrate
                        $cacheKey = $this->getCacheKey($config['repository']->getEntityClass(), $relatedId);
                        $this->cache[$cacheKey] = $relatedEntity;
                    }
                }
            }
        }
    }

    /**
     * Пакетная загрузка связанных сущностей
     */
    private function batchLoadRelatedEntities(RepositoryInterface $repository, array $ids, string $idField): array
    {
        if (empty($ids)) {
            return [];
        }

        $idsString = implode(',', array_map('intval', $ids));
        $sql = "SELECT * FROM {$repository->tableName} WHERE {$idField} IN ({$idsString})";
        
        $result = [];
        $collection = $repository->getAll($sql);
        
        if ($collection) {
            foreach ($collection as $entity) {
                $result[$entity->$idField] = $entity;
            }
        }
        
        return $result;
    }

    private function getCacheKey(string $entityClass, $id): string
    {
        return $entityClass . '_' . $id;
    }

    private function hydrate(array $data): object
    {
        $entity = new $this->entityClass($data);

        // Быстрая гидратация связей из кэша
        foreach ($this->relationships as $property => $config) {
            $foreignKeyValue = $data[$config['foreign_key']] ?? null;
            
            if ($foreignKeyValue !== null) {
                $cacheKey = $this->getCacheKey($config['repository']->getEntityClass(), $foreignKeyValue);
                
                if (isset($this->cache[$cacheKey])) {
                    $entity->$property = $this->cache[$cacheKey];
                }
            }
        }

        return $entity;
    }

    // Остальные методы остаются без изменений...
    public function getAll_array(string $sql = null): ?array
    {
        $pdo = $this->database->getConnection();
        $finalQuery = $sql ?? "SELECT * FROM {$this->tableName}";
        
        $stmt = $pdo->query($finalQuery);
        $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return empty($entities) ? null : $entities;
    }

    public function addRelationship(string $property, RepositoryInterface $repository, string $foreignKey, string $targetIdField): void
    {
        $this->relationships[$property] = [
            'repository' => $repository,
            'foreign_key' => $foreignKey,
            'target_id_field' => $targetIdField
        ];
    }

    public function findBy(string $property): ?Collection
    {
        $pdo = $this->database->getConnection();
        $sql = "SELECT * FROM {$this->tableName} {$property}";
        
        $stmt = $pdo->query($sql);
        $entities = [];
        $batchData = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batchData[] = $row;
        }

        if (empty($batchData)) {
            return null;
        }

        // Пакетная загрузка связей
        $this->preloadRelationships($batchData);

        foreach ($batchData as $row) {
            $entities[] = $this->hydrate($row);
        }

        return new Collection($this->entityClass, $entities);
    }

    public function first(string $property): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} {$property}";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data === false) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    public function findById(int $id, string $nameID): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$nameID} = :id";
        //error_log('findById - sql:' . $sql);
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data === false) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    // Остальные методы (save, insert, update, delete) остаются без изменений...
    
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
    
    public function getTableName(): string
    {
        return $this->tableName;
    }
    
    private function logAction(string $action, string $message, array $context = []): void
    {
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($action),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        error_log($logMessage, 3, __DIR__ . '/../storage/logs/GenericRepository.log');
    }

    public function save(object $entity, string $status = null): ?object
    {

        if (!$entity instanceof BaseEntity && !$entity instanceof IProperty) {
            throw new InvalidArgumentException("Тип сущности не определена, допустимые сущности BaseEntity и IProperty");
        }

        $type = is_object($entity) ? get_class($entity) : gettype($entity);

        /*   $this->logAction(
               'save',
               'Проверка типа сущности',
               [
                   'id' => $entity->getId(),
                   'тип сущности' => $type
               ]
           );*/

        if ($entity->getId() === 0) {
            /* $this->logAction(
                 'save',
                 'Запуск метода insert',
                 [
                     'id' => $entity->getId()
                 ]
             );*/
            $result = $this->insert($entity);
        } else {
            if ($status === 'create') {
                /* $this->logAction(
                     'save',
                     'Запуск метода insert при условии Action::CREATE',
                     [
                         'id' => $entity->getId()
                     ]
                 );*/
                $result = $this->insert($entity);
            } else {
                /*   $this->logAction(
                       'save',
                       'Запуск метода update',
                       [
                           'id' => $entity->getId()
                       ]
                   );*/
                $result = $this->update($entity);
            }
        }
        return $result;
    }
    private function formatSqlWithParams(string $sql, array $params): string
    {
        return array_reduce(
            array_keys($params),
            function ($sql, $key) use ($params) {
                $value = is_numeric($params[$key])
                    ? $params[$key]
                    : "'" . str_replace("'", "''", $params[$key]) . "'";
                return str_replace(":$key", $value, $sql);
            },
            $sql
        );
    }
    private function insert(BaseEntity $entity): ?object
    {
        $properties = [];
        $persistableProps = $entity->getPersistableProperties(); // перечень полей
        $autoDateFields = $entity->getAutoDateFields(); // Получаем поля для авто-дат


        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($persistableProps as $prop) {
            $columns[] = $prop;
            if (in_array($prop, $autoDateFields)) {
                $placeholders[] = 'GETDATE()';
            } else {
                $placeholders[] = ":$prop";
                $params[":$prop"] = $entity->$prop;
            }
        }

        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);

        $sql = "INSERT INTO {$this->tableName} ($columnsStr) VALUES ($placeholdersStr);";
        //error_log($sql);


        try {
            $pdo = $this->database->getConnection();

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $id = $pdo->lastInsertId();

            if ($id != null) {
                $entity->setId($id);
            }
            /* $this->logAction('insert', 'Объект сохранен', [
                 'id' => $id
             ]);*/

            return $entity;
        } catch (PDOException $e) {
            // Логируем ошибку с деталями
            $this->logAction('ERROR', 'Ошибка БД', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $sql,
                'params' => $params
            ]);

            // Возвращаем null для обработки ошибки в вызывающем коде
            return null;
        }
    }

    private function update(BaseEntity $entity): ?object
    {
        $setParts = [];
        $params = [];
        $readOnlyFields = $entity->getReadOnlyFields();

        $persistableProps = $entity->getPersistableProperties();
        $autoDateFields = $entity->getAutoDateFields();
        $idField = $entity->getIdFieldName(); // Получаем имя ID-поля

        // Формируем SET-части запроса и параметры
        foreach ($persistableProps as $prop) {
            if ($prop === $idField) {
                continue;
            }

            if ($this->isReadOnlyField($readOnlyFields, $prop) === true)
                continue;

            if (in_array($prop, $autoDateFields)) {
                $setParts[] = "$prop = GETDATE()"; //для mysql - NOW()
            } else {
                $setParts[] = "$prop = :$prop";
                $params[":$prop"] = $entity->$prop;
            }
        }

        // Добавляем ID в параметры для условия WHERE
        $params[":$idField"] = $entity->getId();

        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$this->tableName} SET $setClause WHERE $idField = :$idField";

        /*$this->logAction('update', $sql, $params);*/

        try {
            $pdo = $this->database->getConnection();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            /*  $this->logAction('update', 'Объект успешно обновлен', [
                  'id' => $entity->getId(),
                  'affected_rows' => $stmt->rowCount()
              ]);*/

            return $entity;
        } catch (PDOException $e) {
            $this->logAction('ERROR', 'Ошибка БД при обновлении', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $sql,
                'params' => $params
            ]);
            return null;
        }
    }

    private function isReadOnlyField($readOnlyFields, $prop): bool
    {
        foreach ($readOnlyFields as $readOnlyField) {
            if ($prop === $readOnlyField) {
                return true;
            }
        }
        return false;
    }

    public function delete(object $entity): void
    {
        if (!$entity instanceof BaseEntity || !$entity->getId()) {
            throw new InvalidArgumentException("Invalid entity for deletion");
        }

        $nameID = $entity->getIdFieldName();
        $sql = "DELETE FROM {$this->tableName} WHERE {$nameID} = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare(
            $sql
            /*    "DELETE FROM {$this->tableName} WHERE id = :id"*/
        );
        $stmt->execute(['id' => $entity->getId()]);
    }
}