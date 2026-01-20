<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/CUDHandler.log');
/**
 * Универсальный обработчик CUD операций
 */
abstract class CUDHandler
{
    protected $controller;
    protected $entityClass;

    public function __construct($controller, $entityClass)
    {
        $this->controller = $controller;
        $this->entityClass = $entityClass;
    }

    /**
     * Основной метод обработки запроса
     */
    public function handleRequest()
    {        
        header('Content-Type: application/json');

        try {
            // Проверка метода
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не разрешен');
            }

            
            $dataJson = json_decode(file_get_contents('php://input'), true);
            if (!$dataJson) {
                error_log('Необнаружены данные для выполнения дейтсвия для сущости: ' . $this->entityClass);
                throw new Exception('Необнаружены данные для выполнения дейтсвия для сущости: ' . $this->entityClass);
            }

            //error_log('dataJson');
            //error_log(print_r($dataJson, true));
            $data = $this->prepareData($dataJson);
            // Получение действия
            $action = $dataJson['statusEntity'] ?? 'статус не определен';
            $id = $dataJson['id'] ?? 0;
            $patofID = $dataJson['patofID'] ?? null;


            //error_log('выполнение действия - executeAction');
           // error_log(`action -`. print_r($action, true));
           // error_log(`data -`. print_r($data, true));
           // error_log(`id -`. print_r($id, true));
            // Выполнение действия
            $result = $this->executeAction($action, $data, $id, $patofID);

            // Формирование ответа
            $this->sendSuccessResponse($result, $action);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }

    /**
     * Подготовка данных (может быть переопределен в дочерних классах)
     */
    protected function prepareData($postData)
    {
        //error_log(print_r($postData, true));
        return $postData;
    }

    /**
     * Выполнение действия
     * @param mixed $action
     * @param mixed $data
     * @param mixed $id
     * @param mixed $patofID
     * @throws \Exception
     */
    protected function executeAction($action, $data, $id, $patofID)
    {        
        switch ($action) {
            case 'create':
                //error_log('Создаем сущность!');
                return $this->create($data, $patofID);
            case 'update':
                //error_log('Обновляем сущность!');
                return $this->update($id, $data);
            case 'delete':
                return $this->delete($id);
            default:
                throw new Exception('Неизвестное действие: ' . $action);
        }
    }

    /**
     * Создание сущности
     */
    protected function create($data, int $patofID = null)
    {
        //error_log('CUDHandler patofID: ' . $patofID);
        $entity = new $this->entityClass($data);
        return $this->controller->create($entity, $patofID);
    }

    /**
     * Обновление сущности
     */
    protected function update($id, $data, int $patofID = null)
    {       
        $entity = new $this->entityClass($data);
        $entity->{$this->getIdFieldName()} = $id;
        return $this->controller->update($entity);
    }

    /**
     * Удаление сущности
     */
    protected function delete($id)
    {
        return $this->controller->delete($id);
    }

    /**
     * Получение имени ID поля
     */
    protected function getIdFieldName()
    {
        // По умолчанию предполагаем, что у сущности есть метод getIdFieldName
        if (method_exists($this->entityClass, 'getIdFieldName')) {
            $tempEntity = new $this->entityClass();
            return $tempEntity->getIdFieldName();
        }
        return 'id';
    }

    /**
     * Отправка успешного ответа
     */
    protected function sendSuccessResponse($result, $action)
    {
        //error_log('Тут результаты успешного добавления сущности');
        //error_log(print_r($result, true));

        $response = [
            'success' => true,
            'message' => $this->getSuccessMessage($action),
            'resultEntity' => $this->prepareResultEntity($result),
            'fields' => $this->getFields($this->prepareResultEntity($result)),
            'statusCUD' => $action
        ];

        echo json_encode($response);
    }

    /**
     * Отправка ошибки
     */
    protected function sendErrorResponse($message)
    {
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }

    /**
     * Получение сообщения об успехе
     */
    protected function getSuccessMessage($action)
    {
        $messages = [
            'create' => 'Данные успешно созданы',
            'update' => 'Данные успешно обновлены',
            'delete' => 'Данные успешно удалены'
        ];

        return $messages[$action] ?? 'Операция выполнена успешно';
    }

    /**
     * Подготовка сущности для ответа (может быть переопределен)
     */
    protected function prepareResultEntity($entity)
    {
        return $entity;
    }

    /**
     * Получение полей из масива от prepareResultEntity (Подготовка сущности для ответа)
     * @param array $array
     * @param string $prefix
     * @return string[]
     */
    function getFields(array $array, string $prefix = ''): array
    {
        $fields = [];
        foreach ($array as $key => $value) {
            $fieldName = $prefix . $key;
            if (is_array($value)) {
                // Рекурсивно обрабатываем вложенный массив
                $fields = array_merge($fields, $this->getFields($value, $fieldName . '.'));
            } else {
                $fields[] = $fieldName;
            }
        }
        return $fields;
    }
}