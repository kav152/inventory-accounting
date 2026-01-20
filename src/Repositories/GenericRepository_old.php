<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/GenericRepository.log');
require __DIR__ . '/../Repositories/RepositoryInterface.php';
require __DIR__ . '/Collection.php';
require_once __DIR__ . '/../BusinessLogic/Action.php';

//Для sql СЕРВера не путать с mysql
class GenericRepository1 implements RepositoryInterface
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

    public function getAll(string $query = null): ?Collection
    {
        $pdo = $this->database->getConnection();

        $finalQuery = $query ?? "SELECT * FROM {$this->tableName}";
        $startTime = microtime(true);
        $sql = $pdo->query($finalQuery); // Всегда выполняем $finalQuery
        $loadTime = microtime(true) - $startTime;

        if ($loadTime > 1.0) {
            error_log("Запрос был принят {$loadTime}s: {$finalQuery}");
        }

        $entities = [];
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $startTime = microtime(true);
            $entities[] = $this->hydrate($row);
            $loadTime = microtime(true) - $startTime;
            if ($loadTime > 0.5) {
                error_log("Время обработка строки " . print_r($row, true) . "в hydrate: {$loadTime}");
            }

        }
        if (empty($entities)) {
            return null;
        }
        // Возвращаем коллекцию с типом 
        return new Collection($this->entityClass, $entities);
    }

    public function getAll_array(string $sql = null): ?array
    {
        $pdo = $this->database->getConnection();

        if ($sql === null)
            $stmt = $pdo->query("SELECT * FROM {$this->tableName}");
        else
            $stmt = $pdo->query($sql); // Выполняем переданный SQL-запрос

        //$this->logAction('getAll_array', $sql);
        $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($entities)) {
            return null;
        }
        return $entities ?? null;
    }

    /**
     * Добавляет определение связи между текущей сущностью и связанной сущностью
     * 
     * Регистрирует связь "один-ко-одному" или "многие-к-одному", которая будет автоматически
     * загружаться при гидратации объектов. Связь позволяет получать связанные сущности
     * через указанный репозиторий по значению внешнего ключа.
     *
     * @param string $property Имя свойства в текущей сущности, куда будет установлена связанная сущность
     * @param RepositoryInterface $repository Репозиторий для загрузки связанной сущности
     * @param string $foreignKey Имя поля в текущей таблице, содержащего внешний ключ
     * @param string $targetIdField Имя первичного ключа в целевой таблице связанной сущности
     * @return void
     *
     * @example
     * $userRepository->addRelationship('profile', $profileRepository, 'profile_id', 'id');
     * // Установит связь: User имеет один Profile через поле profile_id
     */
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

        //$this->logAction('findBy', "SELECT * FROM {$this->tableName} {$property}");
        $sql = $pdo->query("SELECT * FROM {$this->tableName} {$property}");

        $entities = [];
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            // Создаём объект текущего entityClass
            //$entities[] = new $this->entityClass($row);
            $entities[] = $this->hydrate($row);
        }
        if (empty($entities)) {
            return null;
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
        //$entity = new $this->entityClass($data);
        $entity = $this->hydrate($data);
        return $entity;
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
        /*$this->logAction('START', 'Старт функции insert', [
            'id' => $entity->getId()
        ]);*/
        $properties = [];
        /* $this->logAction('START', 'сбор properties', $properties);*/
        $persistableProps = $entity->getPersistableProperties(); // перечень полей
        $autoDateFields = $entity->getAutoDateFields(); // Получаем поля для авто-дат


        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($persistableProps as $prop) {
            $columns[] = $prop;
            if (in_array($prop, $autoDateFields)) {
                $placeholders[] = 'NOW()';
            } else {
                $placeholders[] = ":$prop";
                $params[":$prop"] = $entity->$prop;
            }
        }

        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);

        $sql = "INSERT INTO {$this->tableName} ($columnsStr) VALUES ($placeholdersStr);";
        //  $this->logAction('DEBUG', $sql, $params);


        try {
            $pdo = $this->database->getConnection();

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            /*$this->logAction('insert', 'Выполнено подключение к БД');*/
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            //$stmt->execute($properties);
            /*$this->logAction('insert', 'Переданы параметры properties', $properties);*/
            $id = $pdo->lastInsertId();
            /* $this->logAction('insert', 'Выполнение функции lastInsertId', [
                 'Получаем ID' => $id
             ]);*/
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
        /* $this->logAction('START', 'Старт функции update', [
             'id' => $entity->getId()
         ]);*/

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
                $setParts[] = "$prop = NOW()"; //для mysql - NOW()
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

    /*private function hydrate(array $data): object
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
    }*/

    // Старая версия
    /* private function hydrate(array $data): object
     {
         $entity = new $this->entityClass($data);

         foreach ($this->relationships as $property => $config) {
             $foreignKeyValue = $data[$config['foreign_key']] ?? null;

             if ($foreignKeyValue !== null) {
                 // Проверяем, есть ли уже данные связанной сущности в результате JOIN
                 $relatedData = $this->extractRelatedData($data, $config['repository']->getEntityClass());

                 if (!empty($relatedData)) {
                     // Создаем связанную сущность из данных JOIN
                     $relatedEntity = new ($config['repository']->getEntityClass())($relatedData);
                     $entity->$property = $relatedEntity;
                 } else {
                     // Fallback: ленивая загрузка если данных нет в JOIN
                     $relatedEntity = $config['repository']->findById(
                         $foreignKeyValue,
                         $config['target_id_field']
                     );
                     $entity->$property = $relatedEntity;
                 }
             }
         }

         return $entity;
     }*/

    private function hydrate(array $data): object
    {
        $entity = new $this->entityClass($data);

        // ВРЕМЕННО: используем быстрые связи только для основных данных
        foreach ($this->relationships as $property => $config) {
            $foreignKeyValue = $data[$config['foreign_key']] ?? null;

            if ($foreignKeyValue !== null) {
                // Для InventoryItem и Location используем отдельные запросы
                // но предварительно кэшируем их
                static $cache = [];
                $cacheKey = $config['repository']->getEntityClass() . '_' . $foreignKeyValue;

                if (!isset($cache[$cacheKey])) {
                    $cache[$cacheKey] = $config['repository']->findById(
                        $foreignKeyValue,
                        $config['target_id_field']
                    );
                }

                $entity->$property = $cache[$cacheKey];
            }
        }

        return $entity;
    }



    /* private function hydrate(array $data): object
     {
         $entity = new $this->entityClass($data);

         foreach ($this->relationships as $property => $config) {
             $foreignKeyValue = $data[$config['foreign_key']] ?? null;

             if ($foreignKeyValue !== null) {
                 // Логирование для отладки
                 //error_log("Проверка отношений: {$property}, FK: {$foreignKeyValue}");

                 $relatedEntity = $config['repository']->findById(
                     $foreignKeyValue,
                     $config['target_id_field']
                 );

                 if ($relatedEntity) {
                     $entity->$property = $relatedEntity;
                     //error_log("УСпешные отношения : {$property}");
                 } else {
                     //error_log("ОШибка в установки отношений: {$property}, ID: {$foreignKeyValue}");
                 }
             }
         }

         return $entity;
     }*/

    private function extractRelatedData(array $data, string $entityClass): array
    {
        $relatedData = [];
        $tempEntity = new $entityClass();
        $persistableProps = $tempEntity->getPersistableProperties();

        foreach ($persistableProps as $prop) {
            if (isset($data[$prop])) {
                $relatedData[$prop] = $data[$prop];
            }
        }
        // Если нашли хоть одно свойство - считаем что данные есть
        return !empty($relatedData) ? $relatedData : [];
        // return $relatedData;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
