<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
//date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

require_once __DIR__ . '/CudService/CUDFactory.php';
require_once __DIR__ . '/../Logging/Logger.php';

class ItemController
{
    public Container $container;
    private CUDFactory $cudFactory;
    private Logger $logger;

    public function __construct()
    {
        /* if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }*/
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/ItemController.log');
        });
        $this->logger = $this->container->get(Logger::class);

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
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
                $sql = " WHERE ii.Status != "
                    . StatusItem::getByDescription('Списано');
                break;
            case 'Кладовщик':
                // Исправлена конкатенация и форматирование
                $sql = " WHERE ii.Status != " . StatusItem::getByDescription('Списано')
                    . " AND r.CurrentUser = {$idUser}";

                break;
        }

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);

        $sql1 = "
        SELECT 
            ii.*,
            b.NameBrand,
            l.NameLocation,
            l.FormsJointStockCompanies AS LocationLegalEntity,
            u.IDUser,
            r.CurrentUser,
            m.NameModel,
            u.Surname,
            u.Name,
            u.Patronymic
        FROM InventoryItem ii
        LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
        LEFT JOIN Location l ON ii.IDLocation = l.IDLocation
        LEFT JOIN RegistrationInventoryItem r ON ii.ID_TMC = r.IDRegItem
        LEFT JOIN [User] u ON r.CurrentUser = u.IDUser
        LEFT JOIN ModelTMC m ON ii.IDModel = m.IDModel
        {$sql}    
        ORDER BY ii.NameTMC
    ";

        $inventoryItems = $inventoryItemRepository->getAll($sql1);
        if ($inventoryItems) {
            foreach ($inventoryItems as $item) {
                // Страховка: юр. лицо из JOIN, если связь Location пришла без поля
                $legalFromJoin = trim((string) ($item->LocationLegalEntity ?? ''));
                if ($item->Location) {
                    $legalOnLoc = trim((string) ($item->Location->FormsJointStockCompanies ?? ''));
                    if ($legalOnLoc === '' && $legalFromJoin !== '') {
                        $item->Location->FormsJointStockCompanies = $legalFromJoin;
                    }
                }
            }
        }
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
            $this->logger->log('ERROR', 'Пользователь не аутентифицирован');
            return null;
        }

        // Проверяем, что передан ID ТМЦ
        if (empty($data['id'])) {
            $this->logger->log('ERROR', 'ID ТМЦ не указан для обновления');
            return null;
        }

        $id = (int) $data['id'];

        // Получаем текущий ТМЦ
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $currentItem = $inventoryItemRepository->findById($id, "ID_TMC");

        if ($currentItem === null) {
            $this->logger->log('ERROR', 'ТМЦ с ID ' . $id . ' не найден');
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
            $this->logger->log('INFO', 'Попытка обновления ТМЦ без изменений', ['id' => $id]);
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



    public function create($data): ?object
    {
        $result = $this->cudFactory->create($data);
        // Если это InventoryItem, создаем RegistrationInventoryItem
        if ($result instanceof InventoryItem && $result->getId()) {
            $this->createRegistrationForInventoryItem($result);

            $userRepository = $this->container->get(UserRepository::class);
            $userResult = $userRepository->findById($result->RegistrationInventoryItem->CurrentUser, "IDUser");
            $result->User = $userResult;

            $brandTMCRepository = $this->container->get(BrandTMCRepository::class);
            $brandResult = $brandTMCRepository->findById($result->IDBrandTMC, "IDBrandTMC");
            $result->BrandTMC = $brandResult;
        }

        return $result;
    }

    public function update($data)
    {
        $result = $this->cudFactory->update($data);
        if ($result instanceof InventoryItem && $result->getId()) {
            $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
            $userRepository = $this->container->get(UserRepository::class);

            $registrationInventory = $registrationInventoryItemRepository->findById($result->getId(), 'IDRegItem');
            $userResult = $userRepository->findById($registrationInventory->CurrentUser, "IDUser");
            $result->User = $userResult;

            $brandTMCRepository = $this->container->get(BrandTMCRepository::class);
            $brandResult = $brandTMCRepository->findById($result->IDBrandTMC, "IDBrandTMC");
            $result->BrandTMC = $brandResult;

            $locationRepository = $this->container->get(LocationRepository::class);
            $location = $locationRepository->findById($result->IDLocation, "IDLocation");
            $result->Location = $location;
        }

        return $result;
    }

    public function delete($data): bool
    {
        //error_log(print_r($data, true));
        // $brigadesRepository = new BrigadesRepository(DatabaseFactory::create());
        // $brigades = $brigadesRepository->findById($data->getID(), 'IDBrigade');

        return $this->cudFactory->delete($data);
    }

    /**
     * Создание RegistrationInventoryItem для нового InventoryItem
     */
    private function createRegistrationForInventoryItem(InventoryItem $item): void
    {
        if (!isset($_SESSION['IDUser'])) {
            return;
        }

        $registration = new RegistrationInventoryItem([
            'IDRegItem' => $item->ID_TMC,
            'CreatedUser' => $_SESSION['IDUser'],
            'CurrentUser' => $_SESSION['IDUser'],
        ]);

        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $result = $registrationInventoryItemRepository->save($registration, Action::CREATE);

        $item->RegistrationInventoryItem = $result;
    }

    /**
     * Получить основной склад
     */
    public function getMainWarehouse(): ?Location
    {
        $locationRepo = $this->container->get(LocationRepository::class);
        $sql = 'WHERE isMainWarehouse = 1';
        return $locationRepo->first($sql);
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
        $userCondition = $statusUser != 0 ? "AND r.CurrentUser = {$idUser}" : "";

        $sql = "
        SELECT 
            ii.*,
            b.NameBrand,
            l.NameLocation,
            u.Surname,
            u.Name,
            u.Patronymic
        FROM InventoryItem ii
        LEFT JOIN RegistrationInventoryItem r ON ii.ID_TMC = r.IDRegItem
        LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
        LEFT JOIN Location l ON ii.IDLocation = l.IDLocation
        LEFT JOIN [User] u ON r.CurrentUser = u.IDUser
        WHERE ii.Status = {$status}
        {$userCondition}
        ORDER BY ii.NameTMC
    ";

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        return $inventoryItemRepository->getAll($sql) ?? null;
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
        //GROUP_CONCAT - заменить на STRING_AGG(ii.ID_TMC, ',') при использовании mysql
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
            . "WHERE InventoryItem.ID_TMC IN ($idsString)";

        return $result = $inventoryItemRepository->getAll($query) ?? null;
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
        if ($inventoryItem === null) {
            throw new Exception("ТМЦ {$id} не найден");
        }
        $inventoryItem->Status = StatusItem::Released;
        $inventoryItemRepository->save($inventoryItem);

        $locationRepository = $this->container->get(LocationRepository::class);
        $location = $locationRepository->findById((int) ($inventoryItem->IDLocation ?? 0), "IDLocation");
        $inventoryItem->Location = $location;

        $historyOperations = new HistoryOperationsController();
        $historyOperations->AcceptanceConfirmedTMC($inventoryItem);

        return true;
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
        if ($historyOperations === null || $historyOperations->count() < 2) {
            throw new Exception("Недостаточно записей истории для отказа по ТМЦ {$id}");
        }

        $operation = $historyOperations->indexOf(1);
        if ($operation === null) {
            throw new Exception("Не найдена предыдущая операция для отказа по ТМЦ {$id}");
        }

        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $registrationItem = $registrationInventoryItemRepository->findById((int) $id, "IDRegItem");
        if ($registrationItem === null) {
            throw new Exception("Не найдена регистрация ТМЦ {$id}");
        }

        $registrationItem->CurrentUser = $operation->IDUser;
        $registrationInventoryItemRepository->save($registrationItem);

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
        if ($inventoryItem === null) {
            throw new Exception("ТМЦ {$id} не найден");
        }

        $inventoryItem->IDLocation = $operation->IDLocation;
        $inventoryItem->Status = StatusItem::Released;
        $inventoryItemRepository->save($inventoryItem);

        $locationRepository = $this->container->get(LocationRepository::class);
        $location = $locationRepository->findById((int) $inventoryItem->IDLocation, "IDLocation");
        $inventoryItem->Location = $location;
        $inventoryItem->CurrentUser = $_SESSION["IDUser"] ?? null;

        $historyWriter = new HistoryOperationsController();
        $historyWriter->RefusedConfirmedTMC($inventoryItem);

        return true;
    }

    /**
     * Вернуть ТМЦ после списания
     * @param int $id
     * @return bool
     */
    public function cancelWriteOffTMC(int $id)
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $inventoryItem = $inventoryItemRepository->findById($id, "ID_TMC");
        if (!$inventoryItem) {
            throw new Exception("ТМЦ с ID {$id} не найден");
        }
        if ((int) $inventoryItem->Status !== StatusItem::WrittenOff) {
            throw new Exception("ТМЦ не находится в статусе «Списано»");
        }

        $inventoryItem->Status = StatusItem::NotDistributed;
        $saved = $inventoryItemRepository->save($inventoryItem);
        if (!$saved) {
            throw new Exception("Не удалось вернуть ТМЦ из списания");
        }

        $location = $locationRepository->findById((int) $inventoryItem->IDLocation, "IDLocation");
        $inventoryItem->Location = $location;

        $historyOperations = new HistoryOperationsController();
        $historyOperations->CancelWriteOffTMC($inventoryItem);
        return true;
    }


    /**
     * ТМЦ на указанной локации (для передачи объект → объект)
     */
    public function getItemsByLocation(int $locationId): array
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $sql = "
            SELECT 
                ii.ID_TMC,
                ii.NameTMC,
                ii.SerialNumber,
                ii.Status,
                l.NameLocation,
                l.FormsJointStockCompanies AS LocationLegalEntity,
                b.NameBrand
            FROM InventoryItem ii
            LEFT JOIN Location l ON ii.IDLocation = l.IDLocation
            LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
            WHERE ii.IDLocation = {$locationId}
              AND ii.Status IN (" . StatusItem::NotDistributed . ", " . StatusItem::Released . ", " . StatusItem::ConfirmItem . ")
            ORDER BY ii.NameTMC
        ";
        $items = $inventoryItemRepository->getAll_array($sql) ?? [];
        $result = [];
        foreach ($items as $row) {
            $result[] = [
                'id' => (int) ($row['ID_TMC'] ?? 0),
                'name' => (string) ($row['NameTMC'] ?? ''),
                'serial' => (string) ($row['SerialNumber'] ?? ''),
                'status' => (int) ($row['Status'] ?? 0),
                'statusText' => StatusItem::getDescription((int) ($row['Status'] ?? 0)) ?? '',
                'location' => (string) ($row['NameLocation'] ?? ''),
                'legal' => trim((string) ($row['LocationLegalEntity'] ?? '')),
                'brand' => (string) ($row['NameBrand'] ?? ''),
            ];
        }
        return $result;
    }

    /**
     * Переместить ТМц на новую локацию
     * @param array $tmcIds
     * @param int $locationId
     * @param int $userId
     * @return void
     */
    public function distributeItems(array $tmcIds, int $locationId, int $userId, string $upd = '')
    {
        //error_log('Мы в distributeItems: ' . print_r($tmcIds, true));
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $historyOperations = new HistoryOperationsController();
        $upd = trim($upd);

        foreach ($tmcIds as $id) {

            $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
            if ($inventoryItem === null) {
                throw new Exception("ТМЦ {$id} не найден для передачи");
            }
            $prevStatus = (int) ($inventoryItem->Status ?? -1);

            if ($prevStatus === StatusItem::Repair) {
                try {
                    $this->changeDateReturnService((int) $id);
                } catch (Exception $e) {
                    error_log('distributeItems return-from-repair: ' . $e->getMessage());
                }
                $this->logHistoryOperation(OperationType::RETURN_FROM_REPAIR, (int) $id, null, $upd !== '' ? $upd : 'Возврат из сервиса на объект');
            }

            $inventoryItem->IDLocation = $locationId;
            $inventoryItem->Status = StatusItem::ConfirmItem;
            $inventoryItemRepository->save($inventoryItem);

            $registrationItem = $registrationInventoryItemRepository->findById((int) $id, "IDRegItem");
            if ($registrationItem === null) {
                throw new Exception("Не найдена регистрация ТМЦ {$id} — передача невозможна");
            }
            $registrationItem->CurrentUser = $userId;
            $registrationInventoryItemRepository->save($registrationItem);

            $location = $locationRepository->findById($inventoryItem->IDLocation, "IDLocation");
            $inventoryItem->Location = $location;

            $historyOperations->OperationDistributeTMC($inventoryItem, $upd);
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
        return $brigades ?? null;
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
                $this->logHistoryOperation(OperationType::ASSIGN_TO_BRIGADE, $tmcId, $brigadeId, null);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }


    public function unlinkFromBrigade(int $tmcId): void
    {
        $linkBrigadesToItemRepository = $this->container->get(LinkBrigadesToItemRepository::class);
        $link = $linkBrigadesToItemRepository->findById($tmcId, 'ID_TMC');
        if ($link != null) {
            $linkBrigadesToItemRepository->delete($link);
        }
    }

    public function returnTMCtoWork($tmcId, $brigadeId): bool
    {
        try {
            $this->unlinkFromBrigade($tmcId);

            $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::Return_TMC_toWork));
            $this->logHistoryOperation(OperationType::Return_TMC_toWork, $tmcId, $brigadeId, null);

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
                $this->logHistoryOperation(OperationType::ACCEPT_FOR_REPAIR, $tmcId, null, $note);
                return true;
            }
            if ($statusService == 1) //  returnService = 1;
            {
                $this->changeDateReturnService($tmcId);
                $this->changeStatusTMC($tmcId, OperationType::getStatusTransition(OperationType::RETURN_FROM_REPAIR));
                $this->logHistoryOperation(OperationType::RETURN_FROM_REPAIR, $tmcId, null, $note);
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

    /**
     * Логирует операцию с ТМЦ в истории
     * 
     * @param int $id ID ТМЦ
     * @param string $operationType Тип операции (константа OperationType::*)
     * @param int|null $brigadeId ID бригады (требуется для некоторых операций)
     * @param string|null $note Примечание к операции
     * @throws Exception При отсутствии обязательных данных
     */
    public function logHistoryOperation(string $operationType, int $id, ?int $brigadeId = null, ?string $note = null): void
    {
        try {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $locationRepository = $this->container->get(LocationRepository::class);
            $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
            $brigadesRepository = $this->container->get(BrigadesRepository::class);
            $inventoryItem = $inventoryItemRepository->findById((int) $id, "ID_TMC");
            if (!$inventoryItem) {
                throw new Exception("ТМЦ {$id} не найден для записи истории");
            }

            $locationId = (int) ($inventoryItem->IDLocation ?? 0);
            $location = $locationId > 0
                ? $locationRepository->findById($locationId, "IDLocation")
                : null;
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
                    $historyOperations->RepairConfirmedTMC($inventoryItem, (string) $note);
                    break;
                case OperationType::ASSIGN_TO_BRIGADE:
                    if ($brigadeId == null) {
                        throw new Exception("brigadeId не указан. Ошибка в ItemController->logHistoryOperation");
                    }
                    $brigade = $brigadesRepository->findById((int) $brigadeId, "IDBrigade");
                    $historyOperations->AssignToBrigadeTMC($inventoryItem, $brigade);
                    break;
                case OperationType::WRITE_OFF:
                    $historyOperations->WriteOffTMC($inventoryItem, $note);
                    break;
                case OperationType::Return_TMC_toWork:
                    if ($brigadeId == null) {
                        throw new Exception("brigadeId не указан. Ошибка в ItemController->logHistoryOperation");
                    }
                    $brigade = $brigadesRepository->findById((int) $brigadeId, "IDBrigade");
                    $historyOperations->ReturnFromWork($inventoryItem, $brigade);
                    break;
                case OperationType::RETURN_FROM_REPAIR:
                    $historyOperations->ReturnFromRepairTMC($inventoryItem, (string) $note);
                    break;
                case OperationType::ACCEPT_FOR_REPAIR:
                    $historyOperations->AcceptForRepairTMC($inventoryItem, (string) $note);
                    break;
                default:
                    throw new Exception("Неизвестный тип операции истории: {$operationType}");
            }
        } catch (Throwable $e) {
            error_log('logHistoryOperation error [' . $operationType . ' / TMC ' . $id . ']: ' . $e->getMessage());
            // История не должна откатывать основную операцию (списание/ремонт уже выполнены)
        }
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
        $repairs = $repairItemRepository->findBy("where ID_TMC = " . (int) $id . " order by ID_Repair");
        if ($repairs === null || $repairs->count() === 0) {
            throw new Exception("Не найдено записи об отправке в ремонт ТМЦ id:{$id}");
        }
        $repairItem = $repairs->last();
        if ($repairItem === null) {
            throw new Exception("Не найдено записи об отправке в ремонт ТМЦ id:{$id}");
        }

        $result = $repairItemRepository->updateDateWithGetDate($repairItem->ID_Repair, 'DateReturnService');
        if (!$result) {
            throw new Exception("Ошибка указания даты возвращения из сервиса для ТМЦ id:{$id}");
        }
    }
}
