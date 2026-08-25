<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/ItemRepairController.log');

require_once __DIR__ . '/../BusinessLogic/ItemController.php';

require_once __DIR__ . '/../Repositories/RepairItemRepository.php';
require_once __DIR__ . '/../Repositories/InventoryItemRepository.php';
require_once __DIR__ . '/../Repositories/LocationRepository.php';

require_once __DIR__ . '/../Entity/RepairItem.php';

require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once 'HistoryOperationsController.php';
require_once 'StatusItem.php';
require_once 'OperationType.php';
require_once 'StatusUser.php';



class ItemRepairController
{
    private Container $container;
    private Logger $logger;
    private CUDFactory $cudFactory;
    public function __construct()
    {
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/ItemRepairController.log');
        });
        $this->logger = $this->container->get(Logger::class);

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
    }

    public function create($data): ?object
    {
        $result = $this->cudFactory->create($data);
        return $result;
    }
    public function update($data): ?object
    {
        $result = $this->cudFactory->update($data);
        return $result;
    }



    public function sendForRepair($data, $filename): ?object
    {
        $ressult = $this->repairManager($data, $filename, OperationType::SEND_REPAIR) ?? null;
        return $ressult;
    }
    public function writeOffItem($data, $filename): ?object
    {
        $ressult = $this->repairManager($data, $filename, OperationType::WRITE_OFF) ?? null;
        return $ressult;
    }

<<<<<<< HEAD
=======
    /**
     * Списание ТМЦ без отправки в сервис (только админ, с главной таблицы).
     */
    public function directWriteOffByIds(array $tmcIds, string $reason = ''): array
    {
        $written = [];
        $errors = [];
        $atWorkCount = 0;
        $reason = trim($reason) !== '' ? trim($reason) : 'Списание без отправки в сервис';
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $blocked = [
            StatusItem::WrittenOff,
            StatusItem::Repair,
            StatusItem::ConfirmRepairTMC,
        ];

        foreach ($tmcIds as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }

            $item = $inventoryItemRepository->findById($id, 'ID_TMC');
            if (!$item) {
                $errors[] = "ТМЦ {$id} не найден";
                continue;
            }

            $status = (int) ($item->Status ?? -1);
            if (in_array($status, $blocked, true)) {
                $errors[] = "ТМЦ {$id}: нельзя списать из статуса «" . (StatusItem::getDescription($status) ?? $status) . "»";
                continue;
            }

            $locationId = (int) ($item->IDLocation ?? 0);
            if ($locationId <= 0) {
                $errors[] = "ТМЦ {$id}: не указана локация";
                continue;
            }

            $this->writeOffItem([
                'ID_TMC' => $id,
                'IDLocation' => $locationId,
                'InvoiceNumber' => 'Без счета',
                'UPD' => '',
                'RepairCost' => 0,
                'RepairDescription' => $reason,
            ], null);

            if ($status === StatusItem::AtWorkTMC) {
                $atWorkCount++;
            }
            $written[] = $id;
        }

        return [
            'written' => $written,
            'errors' => $errors,
            'atWorkCount' => $atWorkCount,
        ];
    }

>>>>>>> source/feature/local-updates-2026-08
    private function repairManager($data, $filename, $operationType): ?object
    {
        $ID_TMC = isset($data['ID_TMC']) ? (int) $data['ID_TMC'] : 0;
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $repairItem = new RepairItem($data);
        $repairItem->UPD = $filename ?? null;
        if ($operationType === OperationType::SEND_REPAIR)
            $repairItem->DateReturnService = null;
        else
            $repairItem->DateReturnService = (new \DateTime())->format('Y-m-d H:i:s');
        $repair = $repairItemRepository->save($repairItem, Action::CREATE);
        if (!$repair) {
            throw new Exception("Ошибка создания repair в repairManager. RepairCost = {$repairItem->RepairCost}");
        }

        //error_log('Передача ID_TMC');
        //error_log($ID_TMC);
        $itemController = new ItemController();
<<<<<<< HEAD
=======
        if ($operationType === OperationType::WRITE_OFF) {
            $itemController->unlinkFromBrigade($ID_TMC);
        }
>>>>>>> source/feature/local-updates-2026-08
        $itemController->changeStatusTMC(
            $ID_TMC,
            OperationType::getStatusTransition($operationType)
        );
<<<<<<< HEAD
=======

        $historyNote = $repairItem->InvoiceNumber ?? '';
        if ($operationType === OperationType::WRITE_OFF) {
            $historyNote = trim((string) ($repairItem->RepairDescription ?? '')) !== ''
                ? (string) $repairItem->RepairDescription
                : (string) ($repairItem->InvoiceNumber ?? 'Списание');
        }

>>>>>>> source/feature/local-updates-2026-08
        $itemController->logHistoryOperation(
            $operationType,
            $ID_TMC,
            null,
<<<<<<< HEAD
            $repairItem->InvoiceNumber
=======
            $historyNote
>>>>>>> source/feature/local-updates-2026-08
        );
        return $repairItem;
    }

    public function updateRepair($data): bool
    {
        //$this->logger->log('updateRepair', "1");
        $repairItemRepository = $this->container->get(RepairItemRepository::class);

        $repairData = new RepairItem($data);
        // Получаем текущую запись о ремонте из базы
        $currentRepair = $repairItemRepository->findById($repairData->ID_Repair, 'ID_Repair');
        if (!$currentRepair) {
            throw new Exception("Запись о ремонте с ID {$repairData->ID_Repair} не найдена");
        }

        //$this->logger->log('updateRepair', "3");
        $changed = false;
        $persistableProps = $currentRepair->getPersistableProperties();
        $readOnlyFields = $currentRepair->getReadOnlyFields();



        foreach ($persistableProps as $prop) {
            // Пропускаем read-only поля
            if (in_array($prop, $readOnlyFields)) {
                continue;
            }

            // Если в переданных данных нет этого свойства, пропускаем
            if (!property_exists($repairData, $prop)) {
                continue;
            }

            $newValue = $repairData->$prop;
            $currentValue = $currentRepair->$prop;

            // Приведение типа нового значения к типу текущего значения
            if (is_int($currentValue)) {
                $newValue = (int) $newValue;
            } elseif (is_float($currentValue)) {
                $newValue = (float) $newValue;
            } elseif (is_bool($currentValue)) {
                $newValue = filter_var($newValue, FILTER_VALIDATE_BOOLEAN);
            }

            // Сравниваем значения
            if ($currentValue !== $newValue) {
                $changed = true;
                $currentRepair->$prop = $newValue;
            }
        }

        // Если есть изменения, сохраняем
        if ($changed) {
            $result = $repairItemRepository->save($currentRepair);
            //$result = true;
            return $result !== null ? true : false;
        }

        // Если изменений нет, возвращаем true
        return false;
    }

    public function writeOffItems(): ?Collection
    {
        //$repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $userRepository = $this->container->get(UserRepository::class);
        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);
        $brandTMCRepository = $this->container->get(BrandTMCRepository::class);

        /* $query = " LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem "            
             . " WHERE inBasket = 0"
             . " SELECT *FROM InventoryItem WHERE Status = " . StatusItem::Repair . " or Status =" . StatusItem::WrittenOff
             . " SELECT *FROM Location"            
             . " SELECT *FROM [User]";*/

        /* $query = "LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem
           LEFT JOIN InventoryItem ON RegistrationInventoryItem.IDRegItem = InventoryItem.ID_TMC
           LEFT JOIN Location ON Location.IDLocation = RepairItem.IDLocation
           WHERE RepairItem.inBasket = 0";*/

        /*  $query = "LEFT JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC
            LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = RegistrationInventoryItem.IDRegItem
            LEFT JOIN Location ON InventoryItem.IDLocation = Location.IDLocation
            LEFT JOIN BrandTMC ON InventoryItem.IDBrandTMC = BrandTMC.IDBrandTMC
            LEFT JOIN User ON RegistrationInventoryItem.CurrentUser = User.IDUser          
            WHERE RepairItem.inBasket = 0";*/

        $query = "SELECT 
            RepairItem.*,
            InventoryItem.*,
            Location.*,
            BrandTMC.*,
            [User].*,
            RegistrationInventoryItem.*
        FROM RepairItem
        LEFT JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC
        LEFT JOIN Location ON InventoryItem.IDLocation = Location.IDLocation
        LEFT JOIN BrandTMC ON InventoryItem.IDBrandTMC = BrandTMC.IDBrandTMC
        LEFT JOIN RegistrationInventoryItem ON InventoryItem.ID_TMC = RegistrationInventoryItem.IDRegItem
        LEFT JOIN [User] ON RegistrationInventoryItem.CurrentUser = [User].IDUser
        WHERE RepairItem.inBasket = 0";

        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');
        $repairItemRepository->addRelationship('InventoryItem', $inventoryItemRepository, 'ID_TMC', 'ID_TMC');



        /*     $query = "LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem
                 LEFT JOIN Location ON Location.IDLocation = RepairItem.IDLocation
                 LEFT JOIN User ON RegistrationInventoryItem.CurrentUser = User.IDUser                        
                 LEFT JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC                  
                 WHERE RepairItem.inBasket = 0";*/


        /*   $query = "LEFT JOIN Location ON Location.IDLocation = RepairItem.IDLocation                                       
                   LEFT JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC                  
                   WHERE RepairItem.inBasket = 0";*/

        // Добавляем отношения для RepairItem
        /* $repairItemRepository->addRelationship(
             'Location',
             $locationRepository,
             'IDLocation',
             'IDLocation'
         );

         $repairItemRepository->addRelationship(
             'InventoryItem',
             $inventoryItemRepository,
             'ID_TMC',
             'ID_TMC'
         );

         // Добавляем отношения для InventoryItem
         $inventoryItemRepository->addRelationship(
             'BrandTMC',
             $brandTMCRepository,
             'IDBrandTMC',
             'IDBrandTMC'
         );

         $inventoryItemRepository->addRelationship(
             'Location',
             $locationRepository,
             'IDLocation',
             'IDLocation'
         );

         $inventoryItemRepository->addRelationship(
             'User',
             $userRepository,
             'CurrentUser',
             'IDUser'
         );*/


        $repairItems = $repairItemRepository->getAll($query);

        //error_log(print_r($repairItems, true));

        return $repairItems;


    }

    /**
     * Summary of RepairInBasket
     * @param mixed $ID_TMC
     * @return bool
     */
    public function RepairInBasket($ID_TMC): bool
    {
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $repairItems = $repairItemRepository->findBy("WHERE ID_TMC = {$ID_TMC}");
        if (!$repairItems) {
            return false;
        }

        foreach ($repairItems as $item) {
            //$this->logger->log("Найден ТМЦ", "", $item);
            //error_log(print_r($item, true));
            $inBasket = $item->inBasket ? false : true;
            $item->inBasket = $inBasket;
            $result = $repairItemRepository->save($item);
            if ($result == null) {
                return false;
            }
        }
        return true;
    }

<<<<<<< HEAD
=======
    /**
     * Переместить одну запись ремонта в корзину
     */
    public function RepairRecordInBasket(int $ID_Repair): bool
    {
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $item = $repairItemRepository->findById($ID_Repair, 'ID_Repair');
        if (!$item) {
            return false;
        }
        $item->inBasket = true;
        return $repairItemRepository->save($item) !== null;
    }

>>>>>>> source/feature/local-updates-2026-08
    public function getBasketItems(): ?Collection
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        $query = "LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem
          LEFT JOIN InventoryItem ON RegistrationInventoryItem.IDRegItem = InventoryItem.ID_TMC
          WHERE RepairItem.inBasket = 1";

        $repairItemRepository->addRelationship(
            'Location',
            $locationRepository,
            'IDLocation',
            'IDLocation'
        );

        $repairItemRepository->addRelationship(
            'InventoryItem',
            $inventoryItemRepository,
            'ID_TMC',
            'ID_TMC'
        );

        return $repairItemRepository->findBy($query);
    }

    public function returnFromBasket($ID_TMC): bool
    {
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $repairItems = $repairItemRepository->findBy("WHERE ID_TMC = {$ID_TMC}");

        foreach ($repairItems as $item) {
            $item->inBasket = false;
            $result = $repairItemRepository->save($item);
            if ($result == null)
                return false;
        }
        return true;
    }

<<<<<<< HEAD
    public function getItemWithRepairs($ID_TMC): ?Collection
=======
    public function getItemWithRepairs($ID_TMC, ?int $ID_Repair = null): ?Collection
>>>>>>> source/feature/local-updates-2026-08
    {
        //$repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

<<<<<<< HEAD
        /*$query = " LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem "
            . " WHERE inBasket = 0"
            . " SELECT *FROM InventoryItem WHERE Status = " . StatusItem::Repair . " or Status =" . StatusItem::WrittenOff
            . " SELECT *FROM Location"            
            . " SELECT *FROM [User]";*/
=======
        $repairFilter = $ID_Repair ? " AND RepairItem.ID_Repair = {$ID_Repair}" : "";
>>>>>>> source/feature/local-updates-2026-08

        $query = "LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem
          LEFT JOIN InventoryItem ON RegistrationInventoryItem.IDRegItem = InventoryItem.ID_TMC
          LEFT JOIN Location ON Location.IDLocation = RepairItem.IDLocation
<<<<<<< HEAD
          WHERE RepairItem.ID_TMC = {$ID_TMC}";
=======
          WHERE RepairItem.ID_TMC = {$ID_TMC}
            AND RepairItem.inBasket = 0
            {$repairFilter}";
>>>>>>> source/feature/local-updates-2026-08

        $repairItemRepository->addRelationship(
            'Location',                             // Свойство в Location для связи
            $locationRepository,                    // Репозиторий связанной сущности
            'IDLocation',                           // Внешний ключ в InventoryItem
            'IDLocation'                            // Первичный ключ в Location
        );

        $repairItemRepository->addRelationship(
            'InventoryItem',
            $inventoryItemRepository,
            'ID_TMC',
            'ID_TMC'
        );

        return $repairItemRepository->findBy($query);
    }
<<<<<<< HEAD
=======

    /**
     * Списанные ТМЦ с историей ремонтов (для реестра «Все списанные»)
     * Берём по InventoryItem.Status, чтобы не показывать ТМЦ с прошлыми ремонтами.
     */
    public function getWrittenOffGroupedItems(): array
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        $statusWrittenOff = (int) StatusItem::WrittenOff;

        $sql = "
            SELECT
                ii.ID_TMC,
                ii.NameTMC,
                ii.SerialNumber,
                ii.Status,
                ii.IDBrandTMC,
                ii.IDLocation,
                l.NameLocation,
                l.FormsJointStockCompanies AS LocationLegalEntity,
                b.NameBrand,
                r.IDRegItem,
                r.CurrentUser,
                u.Surname,
                u.Name,
                u.Patronymic
            FROM InventoryItem ii
            LEFT JOIN Location l ON ii.IDLocation = l.IDLocation
            LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
            LEFT JOIN RegistrationInventoryItem r ON ii.ID_TMC = r.IDRegItem
            LEFT JOIN [User] u ON r.CurrentUser = u.IDUser
            WHERE ii.Status = {$statusWrittenOff}
            ORDER BY ii.ID_TMC DESC
        ";

        $rows = $inventoryItemRepository->getAll_array($sql) ?? [];
        if (!$rows) {
            return [];
        }

        $ids = array_map(static fn($row) => (int) ($row['ID_TMC'] ?? 0), $rows);
        $ids = array_values(array_filter($ids));
        $idsList = implode(',', $ids);

        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');
        $repairsByTmc = [];
        if ($idsList !== '') {
            $repairQuery = "
                SELECT RepairItem.*
                FROM RepairItem
                WHERE RepairItem.inBasket = 0
                  AND RepairItem.ID_TMC IN ({$idsList})
                ORDER BY RepairItem.ID_Repair DESC
            ";
            $repairItems = $repairItemRepository->getAll($repairQuery);
            if ($repairItems) {
                foreach ($repairItems as $repair) {
                    $tmcId = (int) $repair->ID_TMC;
                    $repairsByTmc[$tmcId][] = $repair;
                }
            }
        }

        $grouped = [];
        foreach ($rows as $row) {
            $tmcId = (int) ($row['ID_TMC'] ?? 0);
            if ($tmcId <= 0) {
                continue;
            }

            $inventoryItem = new InventoryItem([
                'ID_TMC' => $tmcId,
                'NameTMC' => $row['NameTMC'] ?? '',
                'SerialNumber' => $row['SerialNumber'] ?? null,
                'Status' => (int) ($row['Status'] ?? $statusWrittenOff),
                'IDBrandTMC' => (int) ($row['IDBrandTMC'] ?? 0),
                'IDLocation' => (int) ($row['IDLocation'] ?? 0),
                'LocationLegalEntity' => $row['LocationLegalEntity'] ?? '',
            ]);

            $inventoryItem->Location = new Location([
                'IDLocation' => (int) ($row['IDLocation'] ?? 0),
                'NameLocation' => $row['NameLocation'] ?? '',
                'FormsJointStockCompanies' => $row['LocationLegalEntity'] ?? '',
            ]);

            $inventoryItem->BrandTMC = new BrandTMC([
                'IDBrandTMC' => (int) ($row['IDBrandTMC'] ?? 0),
                'NameBrand' => $row['NameBrand'] ?? '',
            ]);

            $user = new User([
                'IDUser' => (int) ($row['CurrentUser'] ?? 0),
                'Surname' => $row['Surname'] ?? '',
                'Name' => $row['Name'] ?? '',
                'Patronymic' => $row['Patronymic'] ?? '',
                'Status' => 0,
            ]);

            $currentUserId = (int) ($row['CurrentUser'] ?? 0);
            $registration = new RegistrationInventoryItem([
                'IDRegItem' => (int) ($row['IDRegItem'] ?? $tmcId),
                'CreatedUser' => $currentUserId,
                'CurrentUser' => $currentUserId,
            ]);
            $registration->User = $user;

            $repairs = $repairsByTmc[$tmcId] ?? [];
            if ($repairs) {
                $main = $repairs[0];
                $main->InventoryItem = $inventoryItem;
                $main->RegistrationInventoryItem = $registration;
                foreach ($repairs as $repair) {
                    $repair->InventoryItem = $inventoryItem;
                    $repair->RegistrationInventoryItem = $registration;
                }
            } else {
                $main = new RepairItem([
                    'ID_Repair' => 0,
                    'ID_TMC' => $tmcId,
                    'IDLocation' => (int) ($row['IDLocation'] ?? 0),
                    'RepairCost' => 0,
                    'InvoiceNumber' => '',
                    'RepairDescription' => '',
                    'DateToService' => date('Y-m-d H:i:s'),
                    'inBasket' => 0,
                ]);
                $main->InventoryItem = $inventoryItem;
                $main->RegistrationInventoryItem = $registration;
                $repairs = [$main];
            }

            $grouped[$tmcId] = [
                'main' => $main,
                'repairs' => $repairs,
            ];
        }

        return $grouped;
    }

    /**
     * Списанные ТМЦ для мини-окна на главной
     */
    public function getWrittenOffSummary(int $limit = 50): array
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $sql = "
            SELECT TOP {$limit}
                ii.ID_TMC,
                ii.NameTMC,
                ii.SerialNumber,
                l.NameLocation,
                l.FormsJointStockCompanies AS LocationLegalEntity,
                b.NameBrand
            FROM InventoryItem ii
            LEFT JOIN Location l ON ii.IDLocation = l.IDLocation
            LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
            WHERE ii.Status = " . StatusItem::WrittenOff . "
            ORDER BY ii.ID_TMC DESC
        ";
        $rows = $inventoryItemRepository->getAll_array($sql) ?? [];
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) ($row['ID_TMC'] ?? 0),
                'name' => (string) ($row['NameTMC'] ?? ''),
                'serial' => (string) ($row['SerialNumber'] ?? ''),
                'location' => (string) ($row['NameLocation'] ?? ''),
                'legal' => trim((string) ($row['LocationLegalEntity'] ?? '')),
                'brand' => (string) ($row['NameBrand'] ?? ''),
            ];
        }
        return $items;
    }
>>>>>>> source/feature/local-updates-2026-08
}
