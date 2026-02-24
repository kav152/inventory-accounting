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
        $itemController->changeStatusTMC(
            $ID_TMC,
            OperationType::getStatusTransition($operationType)
        );
        $itemController->logHistoryOperation(
            $operationType,
            $ID_TMC,
            null,
            $repairItem->InvoiceNumber
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

    public function getItemWithRepairs($ID_TMC): ?Collection
    {
        //$repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        /*$query = " LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem "
            . " WHERE inBasket = 0"
            . " SELECT *FROM InventoryItem WHERE Status = " . StatusItem::Repair . " or Status =" . StatusItem::WrittenOff
            . " SELECT *FROM Location"            
            . " SELECT *FROM [User]";*/

        $query = "LEFT JOIN RegistrationInventoryItem ON RepairItem.ID_TMC = RegistrationInventoryItem.IDRegItem
          LEFT JOIN InventoryItem ON RegistrationInventoryItem.IDRegItem = InventoryItem.ID_TMC
          LEFT JOIN Location ON Location.IDLocation = RepairItem.IDLocation
          WHERE RepairItem.ID_TMC = {$ID_TMC}";

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
}
