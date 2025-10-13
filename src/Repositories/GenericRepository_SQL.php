<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/GenericRepository.log');
require __DIR__ . '/../Repositories/RepositoryInterface.php';
require __DIR__ . '/Collection.php';
require_once __DIR__ . '/../BusinessLogic/Action.php';

//Для sql СЕРВера не путать с mysql
class GenericRepository_SQL implements RepositoryInterface
{

    private string $entityClass;
    private string $tableName;
    private array $relationships = [];

    public function __construct(
        private Database $database,
        string $entityClass,
        string $tableName
    ) {
        //$this->connection = $connection;
        $this->entityClass = $entityClass;
        $this->tableName = $tableName;

        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException("Класс $entityClass не существует!");
        }
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

    public function getAll(): Collection
    {
        $pdo = $this->database->getConnection();
        $sql = $pdo->query("SELECT * FROM {$this->tableName}");
        $entities = [];
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            // Создаём объект текущего entityClass
            $entities[] = new $this->entityClass($row);
        }
        // Возвращаем коллекцию с типом 
        return new Collection($this->entityClass, $entities);
    }

    public function addRelationship(string $property, RepositoryInterface $repository, string $foreignKey, string $targetIdField): void
    {
        $this->relationships[$property] = [
            'repository' => $repository,
            'foreign_key' => $foreignKey,
            'target_id_field' => $targetIdField
        ];
    }
    public function findBy(string $property): Collection
    {
        $pdo = $this->database->getConnection();
        $sql = $pdo->query("SELECT * FROM {$this->tableName} {$property}");

        $entities = [];
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            // Создаём объект текущего entityClass
            //$entities[] = new $this->entityClass($row);
            $entities[] = $this->hydrate($row);
        }

        // Возвращаем коллекцию с типом 
        return new Collection($this->entityClass, $entities);
    }

    /**
     * Получить первое значение по запросу или null. Обязательно указывать критерий поиска WHERE
     * @param string $property 
     * @return object
     */
    public function first(string $property): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} {$property}";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $data = $stmt->fetch();
        if ($data === false) {
            $this->logAction('first', 'Получено значение null');
            return null;
        }
        $entity = new $this->entityClass($data);
        return $entity;
    }



    public function findById(int $id, string $nameID): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$nameID} = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }
        $entity = new $this->entityClass($data);
        return $entity;
    }

    /**
     * Функция сохранения сущностей, зависимые сущности для создания должны быть переданы со статусом Action::CREATE
     * @param object $entity
     * @param string $status
     * @throws \InvalidArgumentException
     * @return object|null
     */
    public function save(object $entity, string $status = null): ?object
    {
        if (!$entity instanceof BaseEntity && !$entity instanceof IProperty) {
            throw new InvalidArgumentException("Тип сущности не определена, допустимые сущности BaseEntity и IProperty");
        }

        $type = is_object($entity) ? get_class($entity) : gettype($entity);

        $this->logAction(
            'save',
            'Проверка типа сущности',
            [
                'id' => $entity->getId(),
                'тип сущности' => $type
            ]
        );

        if ($entity->getId() === 0) {
            $this->logAction(
                'save',
                'Запуск метода insert',
                [
                    'id' => $entity->getId()
                ]
            );
            $result = $this->insert($entity);
        } else {
            if ($status === 'create') {
                $this->logAction(
                    'save',
                    'Запуск метода insert при условии Action::CREATE',
                    [
                        'id' => $entity->getId()
                    ]
                );
                $result = $this->insert($entity);
            } else {
                $this->logAction(
                    'save',
                    'Запуск метода update',
                    [
                        'id' => $entity->getId()
                    ]
                );
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
        $this->logAction('START', 'Старт функции insert', [
            'id' => $entity->getId()
        ]);
        $properties = [];
        $this->logAction('START', 'сбор properties', $properties);
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
        $this->logAction('DEBUG', $sql, $params);


        try {
            $pdo = $this->database->getConnection();

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->logAction('insert', 'Выполнено подключение к БД');
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            //$stmt->execute($properties);
            $this->logAction('insert', 'Переданы параметры properties', $properties);
            $id = $pdo->lastInsertId();
            $this->logAction('insert', 'Выполнение функции lastInsertId', [
                'Получаем ID' => $id
            ]);
            if ($id != null) {
                $entity->setId($id);
            }
            $this->logAction('insert', 'Объект сохранен', [
                'id' => $id
            ]);

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
        $properties = get_object_vars($entity);
        $set = implode(', ', array_map(
            fn($key) => "$key = :$key",
            array_keys($properties)
        ));

        $sql = "UPDATE {$this->tableName} SET $set WHERE id = :id";
        $this->logAction('update', 'sql', ['sql' => $sql]);
        $pdo = $this->database->getConnection();
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute($properties);

        return null;
    }

    public function delete(object $entity): void
    {
        if (!$entity instanceof BaseEntity || !$entity->getId()) {
            throw new InvalidArgumentException("Invalid entity for deletion");
        }
        
        $pdo = $this->database->getConnection();  
        $nameID = $entity->getIdFieldName();
        //$this->logAction(action: "delete","DELETE FROM {$this->tableName} WHERE {$nameID} = :id");      
        $stmt = $this->$pdo->prepare(
            "DELETE FROM {$this->tableName} WHERE {$entity->getIdFieldName()} = :id"
        );
        $stmt->execute(['id' => $entity->getId()]);
    }

    private function hydrate(array $data): object
    {
        $entity = new $this->entityClass($data);

        foreach ($this->relationships as $property => $config) {
            $foreignKeyValue = $data[$config['foreign_key']] ?? null;
            if ($foreignKeyValue !== null) {
                $relatedEntity = $config['repository']->findById(
                    $foreignKeyValue,
                    $config['target_id_field']
                );
                $entity->$property = $relatedEntity;
            }
        }

        return $entity;
    }
}
