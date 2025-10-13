<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/ItemController.log');
require_once __DIR__ . '/../Repositories/BrandTMCRepository.php';
require_once __DIR__ . '/../Repositories/ModelTMCRepository.php';
require_once __DIR__ . '/../Repositories/LocationRepository.php';
require_once __DIR__ . '/../Repositories/InventoryItemRepository.php';
require_once __DIR__ . '/../Repositories/RepairItemRepository.php';
require_once __DIR__ . '/../Repositories/RegistrationInventoryItemRepository.php';
require_once __DIR__ . "/../Entity/RegistrationInventoryItem.php";
require_once __DIR__ . '/../Repositories/BrigadesRepository.php';
require_once __DIR__ . '/../Repositories/LinkBrigadesToItemRepository.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../Entity/LinkBrigadesToItem.php';
require_once __DIR__ . '/../Container.php';
require_once __DIR__ . '/../Repositories/Collection.php';
require_once 'HistoryOperationsController.php';
require_once 'StatusItem.php';
require_once 'OperationType.php';
require_once 'StatusUser.php';

class ItemController
{
    public Container $container;

    public function __construct()
    {
        /* if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }*/
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });
    }
    /**
     * Запись действия в лог
     */
    private function logAction(string $action, string $message, array $context = []): void
    {
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($action),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        error_log($logMessage, 3, __DIR__ . '/../storage/logs/ItemController.log');
    }

    /**
     * Получить перечень элементов для пользователя в зависимости от его статуса
     */
    public function getInventoryItems(int $value_statusUser, int $idUser): ?Collection
    {
        $statusUser = new StatusUser();
        $statusItem = new StatusItem();
        $sql = "";
        switch ($statusUser->getDescription($value_statusUser)) {
            case 'Администратор':
                // Исправлена конкатенация с "+" на "."
                $sql = "LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = IDRegItem"
                    . " WHERE InventoryItem.Status != "
                    . StatusItem::getByDescription('Списано')
                    . " ORDER BY NameTMC";
                break;
            case 'Кладовщик':
                // Исправлена конкатенация и форматирование
                $sql = "LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = IDRegItem"
                    . " WHERE InventoryItem.Status != " . StatusItem::getByDescription('Списано')
                    . " AND RegistrationInventoryItem.CurrentUser = {$idUser}"
                    . " ORDER BY NameTMC";

                break;
        }

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        //$brandTMCRepository = $this->container->get(BrandTMCRepository::class);
        $modelTMCRepository = $this->container->get(ModelTMCRepository::class);
        //$locationRepository = $this->container->get(LocationRepository::class);
        //$registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $userRepository = $this->container->get(UserRepository::class);


        /* $inventoryItemRepository->addRelationship(
            'BrandTMC',                             // Свойство в User для связи
            $brandTMCRepository,                    // Репозиторий связанной сущности
            'IDBrandTMC',                               // Внешний ключ в InventoryItem
            'IDBrandTMC'                             // Первичный ключ в BrandTMC
        );*/

        $inventoryItemRepository->addRelationship(
            'ModelTMC',                             // Свойство в User для связи
            $modelTMCRepository,                    // Репозиторий связанной сущности
            'IDModel',                               // Внешний ключ в InventoryItem
            'IDModel '                             // Первичный ключ в ModelTMC
        );
        /*
        $inventoryItemRepository->addRelationship(
            'Location',                             // Свойство в Location для связи
            $locationRepository,                    // Репозиторий связанной сущности
            'IDLocation',                               // Внешний ключ в InventoryItem
            'IDLocation'                             // Первичный ключ в Location
        );*/

        $inventoryItemRepository->addRelationship(
            'User',                     // Свойство в InventoryItem
            $userRepository,            // Репозиторий User
            'CurrentUser',              // ID пользователя в InventoryItem
            'IDUser'                    // Первичный ключ в User
        );

        $inventoryItems = $inventoryItemRepository->findBy($sql);

        return $inventoryItems ?? null;
    }
    /**
     * Получить InventoryItem, если idItem===null, то InventoryItem верется пустым
     */
    public function getInventoryItem(?int $idItem): InventoryItem
    {
        if (!$idItem) {
            $result = new InventoryItem();
            $result->mountEmptyDocument();
            return $result;
        }

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $inventoryItem = $inventoryItemRepository->findById((int) $idItem, "ID_TMC");

        
        return $inventoryItem;
    }

    /**
     * Обновление существующего InventoryItem
     * @param array $data
     * @return object|null
     */
    public function updateItemInventory(array $data): ?object
    {
        // Проверяем аутентификацию пользователя
        if (!isset($_SESSION['IDUser'])) {
            $this->logAction('ERROR', 'Пользователь не аутентифицирован');
            return null;
        }

        // Проверяем, что передан ID ТМЦ
        if (empty($data['id'])) {
            $this->logAction('ERROR', 'ID ТМЦ не указан для обновления');
            return null;
        }

        $id = (int)$data['id'];

        // Получаем текущий ТМЦ
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $currentItem = $inventoryItemRepository->findById($id, "ID_TMC");

        if ($currentItem === null) {
            $this->logAction('ERROR', 'ТМЦ с ID ' . $id . ' не найден');
            return null;
        }
        // Проверяем наличие изменений
        $hasChanges = false;

        // Проверяем каждое поле на изменение
        $fieldsToCheck = [
            'IDTypesTMC',
            'IDBrandTMC',
            'IDModel',
            'NameTMC',
            'SerialNumber'
        ];

        foreach ($fieldsToCheck as $field) {
            if (array_key_exists($field, $data)) {
                $newValue = $data[$field];

                // Особая обработка для SerialNumber (преобразование пустой строки в null)
                if ($field === 'SerialNumber') {
                    $newValue = ($newValue !== '') ? $newValue : null;
                }

                // Сравниваем текущее значение с новым
                if ($currentItem->$field != $newValue) {
                    $hasChanges = true;
                    break;
                }
            }
        }

        // Если нет изменений, прекращаем выполнение
        if (!$hasChanges) {
            $this->logAction('INFO', 'Попытка обновления ТМЦ без изменений', ['id' => $id]);
            return null;
        }


        // Обновляем поля ТМЦ
        $currentItem->IDTypesTMC = $data['IDTypesTMC'] ?? $currentItem->IDTypesTMC;
        $currentItem->IDBrandTMC = $data['IDBrandTMC'] ?? $currentItem->IDBrandTMC;
        $currentItem->IDModel = $data['IDModel'] ?? $currentItem->IDModel;
        $currentItem->NameTMC = $data['NameTMC'] ?? $currentItem->NameTMC;
        $currentItem->SerialNumber = isset($data['SerialNumber']) && $data['SerialNumber'] !== ''
            ? $data['SerialNumber']
            : null;

        // Сохраняем изменения
        $updatedItem = $inventoryItemRepository->save($currentItem, Action::EDIT);

        if ($updatedItem !== null) {
           /* $this->logAction('UPDATE', 'ТМЦ обновлен', [
                'id' => $updatedItem->ID_TMC,
                'name' => $updatedItem->NameTMC,
                'type' => $updatedItem->IDTypesTMC,
                'brand' => $updatedItem->IDBrandTMC,
                'model' => $updatedItem->IDModel
            ]);*/

            // Обновляем дату изменения в RegistrationInventoryItem
            $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
            $regItem = $registrationInventoryItemRepository->findById($id, "IDRegItem");

            if ($regItem !== null) {
                $regItem->ChangeDate = date('Y-m-d\TH:i:s');
                $registrationInventoryItemRepository->save($regItem);
            }

            // Регистрация в HistoryOperations
            $historyOperations = new HistoryOperationsController();
            $historyOperations->OperationUpdateTMC($updatedItem);

            return $updatedItem;
        }

        return null;
    }



    /**
     * Создание InventoryItem
     * @param mixed $data
     */
    public function createItemInventory($data): ?object
    {
        // Проверяем аутентификацию пользователя
        if (!isset($_SESSION['IDUser'])) {
            $this->logAction('ERROR', 'Пользователь не аутентифицирован' . $_SESSION["Status"]);
            return null;
        }

        $item = new InventoryItem($data);
        $item->Status = StatusItem::NotDistributed;

        // Получаем основной склад
        $locationRepo = $this->container->get(LocationRepository::class);
        $sql = 'WHERE isMainWarehouse = 1';
        $mainWarehouse = $locationRepo->first($sql);

        if ($mainWarehouse === null) {
            $str = "Основной склад по запросу " . $sql . " не найден";
            $this->logAction('CREATE', $str);
            return null;
        }
        // Заносим в InventoryItem
        $item->IDLocation = $mainWarehouse->IDLocation;
        // Регистрация InventoryItem
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);


        $resultItem = $inventoryItemRepository->save($item, Action::CREATE);
       /* $this->logAction('CREATE', 'Объект создан item', [
            'id' => $resultItem->ID_TMC,
            'name' => $resultItem->NameTMC,
            'location' => $resultItem->IDLocation
        ]);*/

        // Регистрация в RegistrationInventoryItem
        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $regItem = new RegistrationInventoryItem([
            'IDRegItem' => $resultItem->ID_TMC,
          /*  'CreationDate' => date('Y-m-d\TH:i:s'),*/
            'CreatedUser' => $_SESSION['IDUser'],
            'CurrentUser' => $_SESSION['IDUser'],
        /*    'ChangeDate' => date('Y-m-d\TH:i:s')*/
        ]);

        $regItem = $registrationInventoryItemRepository->save($regItem, Action::CREATE);
        if ($regItem === null) {
            // ВЫполнить откат по созданию resultItem
            $this->logAction('CREATE', 'Ошибка создания RegistrationInventoryItem');
            return null;
        } else {
           /* $this->logAction('CREATE', 'RegistrationInventory сохранен', [
                'id' => $regItem->IDRegItem,
                '[CreationDate]' => $resultItem->CreationDate,
                '[CreatedUser]' => $regItem->CreatedUser,
                '[CurrentUser]' => $regItem->CurrentUser,
                '[ChangeDate]' => $regItem->ChangeDate,
            ]);*/
        }

        // Регистрация в HistoryOperations
        $historyOperations = new HistoryOperationsController();
        $historyOperations->OperationCreateTMC($resultItem);

        return $this->getInventoryItem($resultItem->ID_TMC);
    }



    /**
     * Получить элементы которые нужно будет подтверждать
     * @param int $statusUser
     * @param int $idUser
     */
    public function getConfirmItems(int $statusUser, int $idUser): ?Collection
    {
        return $this->getItemsByStatus($statusUser, $idUser, StatusItem::ConfirmItem);
    }
    /**
     * Получить элементы которые требует подтверждения ремонта
     * @param int $statusUser
     * @param int $idUser
     */
    public function getConfirmRepairItems(int $statusUser, int $idUser): ?Collection
    {
        return $this->getItemsByStatus($statusUser, $idUser, StatusItem::ConfirmRepairTMC);
    }
    public function getBrigadesToItems(int $statusUser, int $idUser): ?Collection
    {
        return $this->getItemsByStatus($statusUser, $idUser, StatusItem::AtWorkTMC);
    }
    private function getItemsByStatus(int $statusUser, int $idUser, int $status): ?Collection
    {
        $sql = "LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = IDRegItem"
            . " WHERE Status = {$status}";

        if ($statusUser != 0) {
            $sql .= " AND RegistrationInventoryItem.CurrentUser = {$idUser}";
        }
        $sql .= " ORDER BY NameTMC";

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        return $inventoryItemRepository->findBy($sql) ?? null;
    }

    /**
     * Получить коллекцию локаций. Если $isRepair=true то локации для ремонта
     * @param bool $isRepair
     */
    public function getLocations(bool $isRepair = false): ?Collection
    {
        $locationRepo = $this->container->get(LocationRepository::class);
        $sql = $isRepair == true ? ' WHERE IsRepair = 1' : ' WHERE IsRepair = 0';
        $locationRepair = $locationRepo->findBy($sql);
        return $locationRepair ?? null;
    }
    public function getAtWorkItemsGrouped(int $statusUser, int $idUser): ?array
    {
        //GROUP_CONCAT - заменить STRING_AGG при использовании mysql
        $brigadesRepository = $this->container->get(BrigadesRepository::class);
        $sql = "SELECT 
                b.IDBrigade,
                b.NameBrigade,
                CONCAT(u.Surname, ' ', u.Name, ' ', u.Patronymic) AS NameBrigadir,
                COUNT(ii.ID_TMC) AS item_count,
                STRING_AGG(ii.ID_TMC, ',') AS item_ids
                FROM Brigades b
                JOIN [User] u ON b.IDResponsibleIssuing = u.IDUser
                JOIN LinkBrigadesToItem lbt ON b.IDBrigade = lbt.IDBrigade
                JOIN InventoryItem ii ON lbt.ID_TMC = ii.ID_TMC
                LEFT JOIN RegistrationInventoryItem ON ii.ID_TMC = IDRegItem
                WHERE ii.Status = " . StatusItem::AtWorkTMC;

        if ($statusUser != 0) {
            $sql .= " AND RegistrationInventoryItem.CurrentUser = {$idUser}";
        }
        $sql .= " GROUP BY b.IDBrigade, b.NameBrigade, CONCAT(u.Surname, ' ', u.Name, ' ', u.Patronymic)";

        $groups = $brigadesRepository->getAll_array($sql);

        $result = [];
        foreach ($groups as $group) {

            $arr = explode(',', $group['item_ids']);

            $itemIds = !empty($group['item_ids'])
                ? array_map('intval', explode(',', $group['item_ids']))
                : [];

            $items = $this->getItemsByIds($itemIds);

            $result[] = [
                'id' => $group['IDBrigade'],
                'name' => $group['NameBrigade'],
                'brigadir' => $group['NameBrigadir'],
                'count' => $group['item_count'],
                'items' => $items
            ];
        }

        return $result ?? null;
    }

    private function getItemsByIds(array $ids): ?Collection
    {
        if (empty($ids))
            return null;
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $userRepository = $this->container->get(UserRepository::class);

        $idsString = implode(',', $ids);
        $query = "SELECT * FROM InventoryItem "
            . "LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = IDRegItem "
            . "WHERE ID_TMC IN ($idsString)";

        //$this->logAction('getItemsByIds', $query);

        $inventoryItemRepository->addRelationship(
            'User',                     // Свойство в InventoryItem
            $userRepository,            // Репозиторий User
            'CurrentUser',              // ID пользователя в InventoryItem
            'IDUser'                    // Первичный ключ в User
        );

        return $result = $inventoryItemRepository->getAll($query) ?? null;
        /* if($result != null)
        {
            error_log("Коллекция получена");
            foreach($result as $item)
            {
                error_log($item->User->FIO);
            }
        }   */



        // return $inventoryItemRepository->getAll_array($query) ?? null;
    }

    /**
     * Принять ТМЦ кладовщиком
     * @param int $id
     * @return bool
     */
    public function confirmItem(int $id): bool
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $inventoryItemRepository->addRelationship(
            'Location',                             // Свойство в Location для связи
            $locationRepository,                    // Репозиторий связанной сущности
            'IDLocation',                               // Внешний ключ в InventoryItem
            'IDLocation'                             // Первичный ключ в Location
        );

        $inventoryItem = $inventoryItemRepository->findById($id, "ID_TMC");
        $inventoryItem->Status = StatusItem::Released;
        $inventoryItemRepository->save($inventoryItem);


        //$this->logAction('confirmItem','save', ['IDLocation' => $inventoryItem->Location]);

        if ($inventoryItem != null) {
            $locationRepository = $this->container->get(LocationRepository::class);
            $location = $locationRepository->findById($inventoryItem->IDLocation, "IDLocation");
            $inventoryItem->Location = $location;
            $inventoryItem->CurrentUser = $_SESSION["IDUser"];

            // Регистрация в HistoryOperations
            $historyOperations = new HistoryOperationsController();
            $historyOperations->AcceptanceConfirmedTMC($inventoryItem);
        }

        return $inventoryItem != null ? true : false;
    }
    /**
     * Отказать принимать ТМЦ
     * @param int $id
     * @return bool
     */
    public function rejectItem(int $id): bool
    {
        $historyController = new HistoryOperationsController();
        $historyOperations = $historyController->getHistoryOperations($id);

        $operation = $historyOperations->indexOf(1);

        //$this->logAction('first operation', 'operation', ['$operation' => $operation]);

        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $registrationItem = $registrationInventoryItemRepository->findById((int) $id, "IDRegItem");

        $registrationItem->CurrentUser = $operation->IDUser;
        $registrationInventoryItemRepository->save($registrationItem);


        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
        $inventoryItem->IDLocation = $operation->IDLocation;
        $inventoryItem->Status = StatusItem::Released;
        error_log('$inventoryItem->IDLocation: ' . $inventoryItem->IDLocation);
        $inventoryItemRepository->save($inventoryItem);

        // Регистрация в HistoryOperations
        $locationRepository = $this->container->get(LocationRepository::class);
        $location = $locationRepository->findById($inventoryItem->IDLocation, "IDLocation");
        $inventoryItem->Location = $location;
        $inventoryItem->CurrentUser = $_SESSION["IDUser"];

        $historyOperations = new HistoryOperationsController();
        $historyOperations->RefusedConfirmedTMC($inventoryItem);

        return true;
    }


    /**
     * Переместить ТМц на новую локацию
     * @param array $tmcIds
     * @param int $locationId
     * @param int $userId
     * @return void
     */
    public function distributeItems(array $tmcIds, int $locationId, int $userId)
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $historyOperations = new HistoryOperationsController();

        foreach ($tmcIds as $id) {

            $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
            $inventoryItem->IDLocation = $locationId;
            $inventoryItem->Status = StatusItem::ConfirmItem;
            $inventoryItemRepository->save($inventoryItem);

            $registrationItem = $registrationInventoryItemRepository->findById((int) $id, "IDRegItem");
            $registrationItem->CurrentUser = $userId;
            $registrationInventoryItemRepository->save($registrationItem);

            // Регистрация в HistoryOperations
            $location = $locationRepository->findById($inventoryItem->IDLocation, "IDLocation");
            $inventoryItem->Location = $location;

            $historyOperations->OperationDistributeTMC($inventoryItem);
        }
    }

    /**
     * Получить бригады закрепленные к пользователю
     * @param int $idUser
     */
    public function getBrigades(int $idUser): ?Collection
    {
        $brigadesRepository = $this->container->get(BrigadesRepository::class);
        $sql = " WHERE IDResponsibleIssuing = $idUser";
        $brigades = $brigadesRepository->findBy($sql);
        return $brigades;
    }

    /**
     * Назначить в бригаду ТМЦ
     * @param mixed $tmcId
     * @param mixed $brigadeId
     * @return void
     */
    public function assignToBrigade($tmcId, $brigadeId): bool
    {
        try {
            $linkBrigadesToItemRepository = $this->container->get(LinkBrigadesToItemRepository::class);
            $linkBrigadesToItem = new LinkBrigadesToItem(null, $tmcId, $brigadeId);
            $result = $linkBrigadesToItemRepository->save($linkBrigadesToItem);

            if ($result != null) {
                $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::ASSIGN_TO_BRIGADE));
                $this->logHistoryOperation($tmcId, $brigadeId, null, OperationType::ASSIGN_TO_BRIGADE);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Создать бригаду
     * @param Brigades $brigades
     */
    public function createBrigade(Brigades $brigades): ?Brigades
    {
        $brigadesRepository = $this->container->get(BrigadesRepository::class);
        return $brigadesRepository->save($brigades);
    }

    public function returnTMCtoWork($tmcId, $brigadeId): bool
    {
        try {
            $linkBrigadesToItemRepository = $this->container->get(LinkBrigadesToItemRepository::class);
            $result = $linkBrigadesToItemRepository->findById($tmcId, 'ID_TMC');
            $linkBrigadesToItemRepository->delete($result);

            $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::Return_TMC_toWork));
            $this->logHistoryOperation($tmcId, $brigadeId, null, OperationType::Return_TMC_toWork);

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Отправить/вернуть ТМЦ в ремонт. После отправки в ремонт, нужно выполнить подтверждение ремонта с заполнением формы о ремонте!
     * @param int $tmcId
     * @param int $statusService
     * @param string $note
     * @return bool
     */
    public function sendToService(int $tmcId, int $statusService, string $note): bool
    {
        try {

            $brigadeId_current = 0;
            $linkBrigadesToItemRepository = $this->container->get(LinkBrigadesToItemRepository::class);
            $brigadesToItemRepository = $this->container->get(BrigadesRepository::class);

            if ($statusService == 0) //sendService = 0
            {
                $lbi = $linkBrigadesToItemRepository->findById($tmcId, 'ID_TMC');

                if ($lbi != null) {
                    $brigade = $brigadesToItemRepository->findById($lbi->IDBrigade, 'IDBrigade');
                    $brigadeId_current = $brigade->IDBrigade ?? 0;
                    if ($brigadeId_current != 0) {
                        $this->returnTMCtoWork($tmcId, $brigadeId_current);
                    }
                }

                $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::ACCEPT_FOR_REPAIR));
                $this->logHistoryOperation($tmcId, null, $note, OperationType::ACCEPT_FOR_REPAIR);
                return true;
            }
            if ($statusService == 1) //  returnService = 1;
            {
                $this->changeDateReturnService($tmcId);
                $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::RETURN_FROM_REPAIR));
                $this->logHistoryOperation($tmcId, null, $note, OperationType::RETURN_FROM_REPAIR);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error sending to service: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Изменить статус ТМЦ
     * @param int $idTMC индификатор ТМС
     * @param int $statusItem номер статуса согласно StatusItem
     * @return void
     */
    public function changeStatusTMC(int $idTMC, int $statusItem): bool
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $inventoryItem = $inventoryItemRepository->findById((int) $idTMC, "ID_TMC");

        if ($inventoryItem === null) {
            return false;
        }

        $inventoryItem->Status = $statusItem;
        $result = $inventoryItemRepository->save($inventoryItem);
        return $result !== null;
    }

    public function logHistoryOperation(int $id, int $brigadeId = null, string $note = null, string $operationType): void
    {
        $locationRepository = $this->container->get(LocationRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $brigadesRepository = $this->container->get(BrigadesRepository::class);
        $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
        $location = $locationRepository->findById($inventoryItem->IDLocation, "IDLocation");
        $inventoryItem->Location = $location;

        $historyOperations = new HistoryOperationsController();

        switch ($operationType) {
            case OperationType::CREATE:
                $historyOperations->OperationCreateTMC($inventoryItem);
                break;
            case OperationType::DISTRIBUTE:
                $historyOperations->OperationDistributeTMC($inventoryItem);
                break;
            case OperationType::CONFIRM:
                $historyOperations->AcceptanceConfirmedTMC($inventoryItem);
                break;
            case OperationType::SEND_REPAIR:
                $historyOperations->RepairConfirmedTMC($inventoryItem, $note);
                break;
            case OperationType::ASSIGN_TO_BRIGADE:
                if ($brigadeId == null) {
                    throw new Exception("brigadeId не указан. Ошибка в ItemController->logHistoryOperation");
                }
                $brigade = $brigadesRepository->findById((int) $brigadeId, "IDBrigade");
                $historyOperations->AssignToBrigadeTMC($inventoryItem, $brigade);
                break;
            case OperationType::WRITE_OFF:
                $historyOperations->WriteOffTMC($inventoryItem);
                break;
            case OperationType::Return_TMC_toWork:
                if ($brigadeId == null) {
                    throw new Exception("brigadeId не указан. Ошибка в ItemController->logHistoryOperation");
                }
                $brigade = $brigadesRepository->findById((int) $brigadeId, "IDBrigade");
                $historyOperations->ReturnFromWork($inventoryItem, $brigade);
                break;
            case OperationType::RETURN_FROM_REPAIR:
                $historyOperations->ReturnFromRepairTMC($inventoryItem, $note);
                break;
            case OperationType::ACCEPT_FOR_REPAIR:
                $historyOperations->AcceptForRepairTMC($inventoryItem, $note);
                break;
        };
    }

    /**
     * Установить дату возврещения ТМЦ из сервиса
     * @param int $id
     * @throws \Exception
     * @return void
     */
    public function changeDateReturnService(int $id)
    {
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $repairs = $repairItemRepository->findBy("where ID_TMC = " . $id . " order by ID_Repair");
        $repairItem = $repairs->last();
        $repairItem->DateToService =  date("Y-m-d H:i:s");
        $repair = $repairItemRepository->save($repairItem);
        if (!$repair) {
            throw new Exception("Ошибка указании даты возвращения из сервиса");
        }
    }
}
