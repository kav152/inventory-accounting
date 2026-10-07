<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/ItemRepairController.log');

require_once __DIR__ . '/../BusinessLogic/ItemController.php';

require_once __DIR__ . '/../Repositories/RepairItemRepository.php';
require_once __DIR__ . '/Action.php';
require_once __DIR__ . '/../Repositories/InventoryItemRepository.php';
require_once __DIR__ . '/../Repositories/LocationRepository.php';
require_once __DIR__ . '/../Repositories/BrandTMCRepository.php';
require_once __DIR__ . '/../Repositories/RegistrationInventoryItemRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

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

    /**
     * Кладовщик кидает в сервис — статус «Согласование» (ConfirmRepairTMC), счёт потом в архиве.
     */
    public function registerPendingServiceSend(int $tmcId, string $note, string $operationDate = ''): ?RepairItem
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $item = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        if ($item === null) {
            throw new Exception("ТМЦ {$tmcId} не найден");
        }

        $status = (int) ($item->Status ?? -1);
        if ($status === StatusItem::WrittenOff) {
            throw new Exception("ТМЦ {$tmcId} списан");
        }

        $dateToService = $this->normalizeRepairDate($operationDate);

        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $openRepairs = $repairItemRepository->findBy(
            'WHERE ID_TMC = ' . (int) $tmcId . ' AND DateReturnService IS NULL ORDER BY ID_Repair'
        );

        $itemController = new ItemController();
        if ($openRepairs !== null && $openRepairs->count() > 0) {
            $open = $openRepairs->last();
            if ($dateToService !== '' && (string) ($open->DateToService ?? '') !== $dateToService) {
                $open->DateToService = $dateToService;
                $repairItemRepository->save($open);
            }
            $itemController->changeStatusTMC($tmcId, StatusItem::ConfirmRepairTMC);
            $itemController->logHistoryOperation(OperationType::ACCEPT_FOR_REPAIR, $tmcId, null, $note);
            return $open;
        }

        if ($status === StatusItem::Repair) {
            throw new Exception("ТМЦ {$tmcId} уже в сервисе — сначала верните из сервиса");
        }

        // Устаревшие записи «Подтвердить ремонт» — переводим в нормальный поток
        if ($status === StatusItem::ConfirmRepairTMC) {
            $description = trim($note) !== '' ? trim($note) : 'Отправлено в сервис';
            $locationId = (int) ($item->IDLocation ?? 0);
            if ($locationId <= 0) {
                $main = $itemController->getMainWarehouse();
                $locationId = (int) ($main->IDLocation ?? 0);
            }
            $repairItem = new RepairItem([
                'ID_TMC' => $tmcId,
                'IDLocation' => $locationId,
                'InvoiceNumber' => '',
                'RepairCost' => 0,
                'RepairDescription' => $description,
                'UPD' => '',
                'DateToService' => $dateToService,
            ]);
            $saved = $repairItemRepository->save($repairItem, Action::CREATE);
            if (!$saved) {
                throw new Exception("Не удалось создать запись ремонта для ТМЦ {$tmcId}");
            }
            $itemController->changeStatusTMC($tmcId, StatusItem::ConfirmRepairTMC);
            $itemController->logHistoryOperation(OperationType::ACCEPT_FOR_REPAIR, $tmcId, null, $description);
            return $saved;
        }

        $locationId = (int) ($item->IDLocation ?? 0);
        if ($locationId <= 0) {
            $main = $itemController->getMainWarehouse();
            $locationId = (int) ($main->IDLocation ?? 0);
        }
        if ($locationId <= 0) {
            throw new Exception("Не указана локация для ТМЦ {$tmcId}");
        }

        $description = trim($note) !== '' ? trim($note) : 'Отправлено в сервис';
        $repairItem = new RepairItem([
            'ID_TMC' => $tmcId,
            'IDLocation' => $locationId,
            'InvoiceNumber' => '',
            'RepairCost' => 0,
            'RepairDescription' => $description,
            'UPD' => '',
            'DateToService' => $dateToService,
        ]);

        $saved = $repairItemRepository->save($repairItem, Action::CREATE);
        if (!$saved) {
            throw new Exception("Не удалось создать запись ремонта для ТМЦ {$tmcId}");
        }

        // Кладовщик кидает в сервис — статус «Согласование» (ConfirmRepairTMC), счёт приложит админ.
        $itemController->changeStatusTMC($tmcId, StatusItem::ConfirmRepairTMC);
        $itemController->logHistoryOperation(OperationType::ACCEPT_FOR_REPAIR, $tmcId, null, $description);

        return $saved;
    }

    private function normalizeRepairDate(string $operationDate): string
    {
        $operationDate = trim($operationDate);
        if ($operationDate === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            return (new DateTime($operationDate))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Админ согласовал ремонт — сохраняем счёт, синхронизируем статус с открытой записью.
     */
    public function approveRepair(int $repairId, int $tmcId, array $data): bool
    {
        $invoice = trim((string) ($data['InvoiceNumber'] ?? ''));
        if ($invoice === '') {
            throw new Exception('Укажите № счёта');
        }
        $upd = trim((string) ($data['UPD'] ?? ''));

        $itemController = new ItemController();
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $item = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        if (!$item) {
            throw new Exception("ТМЦ {$tmcId} не найден");
        }
        $prevStatus = (int) ($item->Status ?? -1);

        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        if ($repairId > 0) {
            $current = $repairItemRepository->findById($repairId, 'ID_Repair');
            if (!$current) {
                throw new Exception('Запись ремонта не найдена');
            }
            $this->updateRepair([
                'ID_Repair' => $repairId,
                'ID_TMC' => $tmcId,
                'IDLocation' => (int) ($data['IDLocation'] ?? $current->IDLocation ?? 0),
                'InvoiceNumber' => $invoice,
                'RepairCost' => (float) ($data['RepairCost'] ?? $current->RepairCost ?? 0),
                'RepairDescription' => (string) ($data['RepairDescription'] ?? $current->RepairDescription ?? ''),
                'UPD' => $upd !== '' ? $upd : (string) ($current->UPD ?? ''),
                'inBasket' => 0,
                // даты только если передали — иначе не трогаем (не ставим «сегодня»)
                ...(array_key_exists('DateToService', $data) ? ['DateToService' => $data['DateToService']] : []),
                ...(array_key_exists('DateReturnService', $data) ? ['DateReturnService' => $data['DateReturnService']] : []),
            ], false);
        } else {
            $locationId = (int) ($data['IDLocation'] ?? $item->IDLocation ?? 0);
            if ($locationId <= 0) {
                throw new Exception('Не указана организация сервиса');
            }
            $repairItem = new RepairItem([
                'ID_TMC' => $tmcId,
                'IDLocation' => $locationId,
                'InvoiceNumber' => $invoice,
                'RepairCost' => (float) ($data['RepairCost'] ?? 0),
                'RepairDescription' => (string) ($data['RepairDescription'] ?? 'Согласовано'),
                'UPD' => $upd,
                'DateToService' => $data['DateToService'] ?? date('Y-m-d H:i:s'),
                'DateReturnService' => $data['DateReturnService'] ?? null,
            ]);
            $saved = $repairItemRepository->save($repairItem, Action::CREATE);
            if (!$saved) {
                throw new Exception('Не удалось создать запись ремонта');
            }
        }

        // Статус только по открытой записи: не «воскрешаем» уже возвращённые ТМЦ
        $this->reconcileInventoryStatusWithRepairs($tmcId);
        $fresh = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        $newStatus = (int) ($fresh->Status ?? -1);

        if (
            $prevStatus === StatusItem::ConfirmRepairTMC
            && $newStatus === StatusItem::Repair
        ) {
            $itemController->logHistoryOperation(
                OperationType::SEND_REPAIR,
                $tmcId,
                null,
                $invoice
            );
        }

        return true;
    }

    /**
     * Админ отказал в ремонте — возврат ТМЦ на объект.
     */
    public function rejectRepair(int $tmcId, string $reason = ''): bool
    {
        $itemController = new ItemController();
        $reason = trim($reason) !== '' ? trim($reason) : 'Отказ в согласовании ремонта';
        return $itemController->sendToService($tmcId, 1, $reason);
    }

    /**
     * ТМЦ в ремонте / на согласовании — кнопка «Согласование ремонта» на главной.
     * Включает и «Подтвердить ремонт» (21), и «В ремонте» (2).
     * @return array<int, object|RepairItem>
     */
    public function getItemsAwaitingRepairApproval(): array
    {
        $items = [];
        $seenTmc = [];

        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        $repairItemRepository->addRelationship('InventoryItem', $inventoryItemRepository, 'ID_TMC', 'ID_TMC');
        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');

        $statusRepair = (int) StatusItem::Repair;
        $statusConfirm = (int) StatusItem::ConfirmRepairTMC;
        $query = "SELECT RepairItem.*
            FROM RepairItem
            INNER JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC
            WHERE RepairItem.inBasket = 0
              AND RepairItem.DateReturnService IS NULL
              AND InventoryItem.Status IN ({$statusRepair}, {$statusConfirm})
            ORDER BY
              CASE WHEN InventoryItem.Status = {$statusConfirm} THEN 0 ELSE 1 END,
              RepairItem.DateToService DESC";

        $repairs = $repairItemRepository->getAll($query);
        if ($repairs) {
            foreach ($repairs as $repair) {
                $id = (int) ($repair->ID_TMC ?? 0);
                if ($id <= 0 || isset($seenTmc[$id])) {
                    continue;
                }
                $seenTmc[$id] = true;
                $items[] = $repair;
            }
        }

        // Устаревшие ТМЦ в статусах ремонта без открытой записи RepairItem
        $legacy = $inventoryItemRepository->findBy(
            "WHERE Status IN ({$statusRepair}, {$statusConfirm}) ORDER BY NameTMC"
        );
        if ($legacy) {
            foreach ($legacy as $inv) {
                $id = (int) ($inv->ID_TMC ?? 0);
                if ($id > 0 && !isset($seenTmc[$id])) {
                    $seenTmc[$id] = true;
                    $items[] = (object) [
                        'ID_Repair' => 0,
                        'ID_TMC' => $id,
                        'isLegacyConfirm' => true,
                        'InventoryItem' => $inv,
                        'RepairDescription' => '',
                        'InvoiceNumber' => '',
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Лёгкий счётчик для бейджа на главной (без reconcile и полной выборки).
     */
    public function countItemsAwaitingRepairApproval(): int
    {
        $statusRepair = (int) StatusItem::Repair;
        $statusConfirm = (int) StatusItem::ConfirmRepairTMC;
        $pdo = $this->container->get(Database::class)->getConnection();
        $sql = "SELECT COUNT(*) FROM InventoryItem
            WHERE Status IN ({$statusRepair}, {$statusConfirm})";
        $stmt = $pdo->query($sql);
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    /**
     * ТМЦ без счёта + старые записи в статусе ConfirmRepairTMC.
     * @return array<int, object|RepairItem>
     */
    public function getRepairsPendingInvoice(): array
    {
        $items = [];
        $seenTmc = [];

        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        $repairItemRepository->addRelationship('InventoryItem', $inventoryItemRepository, 'ID_TMC', 'ID_TMC');
        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');

        $query = "SELECT RepairItem.*, InventoryItem.*, Location.*
            FROM RepairItem
            LEFT JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC
            LEFT JOIN Location ON RepairItem.IDLocation = Location.IDLocation
            WHERE RepairItem.inBasket = 0
              AND InventoryItem.Status IN (" . StatusItem::Repair . ", " . StatusItem::ConfirmRepairTMC . ")
              AND (
                RepairItem.InvoiceNumber IS NULL
                OR LTRIM(RTRIM(RepairItem.InvoiceNumber)) = ''
              )
            ORDER BY RepairItem.DateToService DESC";

        $repairs = $repairItemRepository->getAll($query);
        if ($repairs) {
            foreach ($repairs as $repair) {
                $items[] = $repair;
                $seenTmc[(int) $repair->ID_TMC] = true;
            }
        }

        $inventoryItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');
        $legacy = $inventoryItemRepository->findBy(
            'WHERE Status = ' . StatusItem::ConfirmRepairTMC . ' ORDER BY NameTMC'
        );
        if ($legacy) {
            foreach ($legacy as $inv) {
                $id = (int) ($inv->ID_TMC ?? 0);
                if ($id > 0 && !isset($seenTmc[$id])) {
                    $items[] = (object) [
                        'ID_Repair' => 0,
                        'ID_TMC' => $id,
                        'isLegacyConfirm' => true,
                        'InventoryItem' => $inv,
                        'RepairDescription' => '',
                        'InvoiceNumber' => '',
                    ];
                }
            }
        }

        return $items;
    }

    public function countRepairsPendingInvoice(): int
    {
        $seen = [];
        foreach ($this->getRepairsPendingInvoice() as $item) {
            $id = (int) ($item->ID_TMC ?? 0);
            if ($id > 0) {
                $seen[$id] = true;
            }
        }
        return count($seen);
    }
    public function writeOffItem($data, $filename): ?object
    {
        $ressult = $this->repairManager($data, $filename, OperationType::WRITE_OFF) ?? null;
        return $ressult;
    }

    /**
     * Списание ТМЦ без отправки в сервис (только админ, с главной таблицы).
     * Статус «Предложение списания» — утверждается как предложение кладовщика.
     */
    public function directWriteOffByIds(array $tmcIds, string $reason = ''): array
    {
        $written = [];
        $errors = [];
        $atWorkCount = 0;
        $reason = trim($reason) !== '' ? trim($reason) : 'Списание без отправки в сервис';
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
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

            // Утвердить предложение кладовщика
            if ($status === StatusItem::ProposeWriteOff) {
                try {
                    $repairId = 0;
                    $repairs = $repairItemRepository->findBy("WHERE ID_TMC = {$id} ORDER BY ID_Repair DESC");
                    if ($repairs && $repairs->count() > 0) {
                        $repairId = (int) ($repairs->first()->ID_Repair ?? 0);
                    }
                    $this->approveProposedWriteOff($id, $repairId);
                    $written[] = $id;
                } catch (Exception $e) {
                    $errors[] = "ТМЦ {$id}: " . $e->getMessage();
                }
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

    /**
     * Кладовщик предлагает списание — статус «Предложение списания», запись в архив ремонтов.
     * Админ утверждает/отклоняет в write_off.php.
     */
    public function proposeWriteOffByIds(array $tmcIds, string $reason = ''): array
    {
        $proposed = [];
        $errors = [];
        $reason = trim($reason) !== '' ? trim($reason) : 'Предложение списания';
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $itemController = new ItemController();
        $blocked = [
            StatusItem::WrittenOff,
            StatusItem::Repair,
            StatusItem::ConfirmRepairTMC,
            StatusItem::ProposeWriteOff,
        ];
        $now = date('Y-m-d H:i:s');

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
                $errors[] = "ТМЦ {$id}: нельзя предложить списание из статуса «" . (StatusItem::getDescription($status) ?? $status) . "»";
                continue;
            }

            $locationId = (int) ($item->IDLocation ?? 0);
            if ($locationId <= 0) {
                $main = $itemController->getMainWarehouse();
                $locationId = (int) ($main->IDLocation ?? 0);
            }
            if ($locationId <= 0) {
                $errors[] = "ТМЦ {$id}: не указана локация";
                continue;
            }

            $repairItem = new RepairItem([
                'ID_TMC' => $id,
                'IDLocation' => $locationId,
                'InvoiceNumber' => '',
                'UPD' => '',
                'RepairCost' => 0,
                'RepairDescription' => 'Предложение списания: ' . $reason,
                'DateToService' => $now,
                'DateReturnService' => null,
                'inBasket' => 0,
            ]);
            $saved = $repairItemRepository->save($repairItem, Action::CREATE);
            if (!$saved) {
                $err = $repairItemRepository->getLastError() ?: 'ошибка БД';
                $errors[] = "ТМЦ {$id}: не удалось создать запись ({$err})";
                continue;
            }

            if (!$itemController->changeStatusTMC($id, StatusItem::ProposeWriteOff)) {
                $errors[] = "ТМЦ {$id}: запись создана, но статус не обновлён";
                continue;
            }

            $itemController->logHistoryOperation(
                OperationType::WRITE_OFF,
                $id,
                null,
                'Предложение списания: ' . $reason
            );
            $proposed[] = $id;
        }

        return [
            'proposed' => $proposed,
            'errors' => $errors,
        ];
    }

    public function countProposeWriteOff(): int
    {
        $pdo = $this->container->get(Database::class)->getConnection();
        $status = (int) StatusItem::ProposeWriteOff;
        $stmt = $pdo->query("SELECT COUNT(*) FROM InventoryItem WHERE Status = {$status}");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    /**
     * Админ утверждает предложение списания.
     */
    public function approveProposedWriteOff(int $tmcId, int $repairId = 0): bool
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $item = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        if (!$item) {
            throw new Exception("ТМЦ {$tmcId} не найден");
        }
        if ((int) ($item->Status ?? -1) !== StatusItem::ProposeWriteOff) {
            throw new Exception('ТМЦ не в статусе «Предложение списания»');
        }

        $locationId = (int) ($item->IDLocation ?? 0);
        $data = [
            'ID_TMC' => $tmcId,
            'IDLocation' => $locationId > 0 ? $locationId : 0,
            'InvoiceNumber' => 'Без счета',
            'UPD' => '',
            'RepairCost' => 0,
            'RepairDescription' => 'Утверждено списание по предложению кладовщика',
        ];
        if ($repairId > 0) {
            $data['ID_Repair'] = $repairId;
            $this->updateRepair([
                'ID_Repair' => $repairId,
                'ID_TMC' => $tmcId,
                'InvoiceNumber' => 'Без счета',
                'RepairDescription' => 'Утверждено списание по предложению кладовщика',
                'DateReturnService' => date('Y-m-d H:i:s'),
                'inBasket' => 0,
            ]);
            $itemController = new ItemController();
            $itemController->unlinkFromBrigade($tmcId);
            $ok = $itemController->changeStatusTMC($tmcId, StatusItem::WrittenOff);
            if ($ok) {
                $itemController->logHistoryOperation(
                    OperationType::WRITE_OFF,
                    $tmcId,
                    null,
                    'Утверждено списание по предложению кладовщика'
                );
            }
            return $ok;
        }

        if ($locationId <= 0) {
            throw new Exception('Не указана локация');
        }
        $this->writeOffItem($data, null);
        return true;
    }

    /**
     * Админ отклоняет предложение списания — возврат на объект.
     */
    public function rejectProposedWriteOff(int $tmcId, string $reason = ''): bool
    {
        $itemController = new ItemController();
        $item = $itemController->getInventoryItem($tmcId);
        if (!(int) ($item->ID_TMC ?? 0)) {
            throw new Exception("ТМЦ {$tmcId} не найден");
        }
        if ((int) ($item->Status ?? -1) !== StatusItem::ProposeWriteOff) {
            throw new Exception('ТМЦ не в статусе «Предложение списания»');
        }
        $reason = trim($reason) !== '' ? trim($reason) : 'Отклонено предложение списания';
        $ok = $itemController->changeStatusTMC($tmcId, StatusItem::Released);
        if ($ok) {
            $itemController->logHistoryOperation(OperationType::RETURN_FROM_REPAIR, $tmcId, null, $reason);
        }
        return $ok;
    }

    private function repairManager($data, $filename, $operationType): ?object
    {
        $ID_TMC = isset($data['ID_TMC']) ? (int) $data['ID_TMC'] : 0;
        $ID_Repair = isset($data['ID_Repair']) ? (int) $data['ID_Repair'] : 0;
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $updValue = ($filename !== null && $filename !== '')
            ? (string) $filename
            : (string) ($data['UPD'] ?? '');

        $repairItem = null;

        // Списание по уже открытой записи — обновляем её, не плодим дубликат INSERT
        if ($operationType === OperationType::WRITE_OFF && $ID_Repair > 0) {
            $existing = $repairItemRepository->findById($ID_Repair, 'ID_Repair');
            if ($existing) {
                $existing->IDLocation = (int) ($data['IDLocation'] ?? $existing->IDLocation ?? 0);
                $existing->InvoiceNumber = (string) ($data['InvoiceNumber'] ?? $existing->InvoiceNumber ?? '');
                $existing->RepairCost = (float) ($data['RepairCost'] ?? $existing->RepairCost ?? 0);
                $existing->RepairDescription = (string) ($data['RepairDescription'] ?? $existing->RepairDescription ?? '');
                if ($updValue !== '') {
                    $existing->UPD = $updValue;
                } elseif ($existing->UPD === null) {
                    $existing->UPD = '';
                }
                // дата возврата при списании — только если передали явно, иначе не затираем «сегодня»
                if (array_key_exists('DateReturnService', $data) && $data['DateReturnService'] !== null && $data['DateReturnService'] !== '') {
                    $existing->DateReturnService = (new RepairItem(['DateReturnService' => $data['DateReturnService']]))->DateReturnService;
                } elseif (array_key_exists('DateReturnService', $data) && ($data['DateReturnService'] === null || $data['DateReturnService'] === '')) {
                    // оставить как было — пустое значение не значит «сегодня»
                }
                if (array_key_exists('DateToService', $data) && $data['DateToService'] !== null && $data['DateToService'] !== '') {
                    $parsedTo = (new RepairItem(['DateToService' => $data['DateToService']]))->DateToService;
                    if ($parsedTo !== '') {
                        $existing->DateToService = $parsedTo;
                    }
                }
                $existing->inBasket = false;
                $repairItem = $repairItemRepository->save($existing);
                if (!$repairItem) {
                    $err = $repairItemRepository->getLastError() ?: 'неизвестная ошибка БД';
                    throw new Exception("Ошибка обновления repair при списании: {$err}");
                }
            }
        }

        if ($repairItem === null) {
            $payload = $data;
            $payload['UPD'] = $updValue;
            if (empty($payload['DateToService'])) {
                $payload['DateToService'] = $now;
            }
            // DateReturnService при списании — только из формы, не автоматом «сегодня»
            if ($operationType === OperationType::WRITE_OFF) {
                if (!array_key_exists('DateReturnService', $payload) || $payload['DateReturnService'] === '') {
                    $payload['DateReturnService'] = null;
                }
            }

            $locationId = (int) ($payload['IDLocation'] ?? 0);
            if ($ID_TMC <= 0) {
                throw new Exception('Не указан ID ТМЦ для записи ремонта');
            }
            if ($locationId <= 0) {
                throw new Exception('Не указана организация (локация) для записи ремонта');
            }

            $repairItem = new RepairItem($payload);
            if ($operationType === OperationType::SEND_REPAIR) {
                $repairItem->DateReturnService = null;
            }
            if ($repairItem->DateToService === '') {
                $repairItem->DateToService = $now;
            }
            if ($repairItem->UPD === null) {
                $repairItem->UPD = '';
            }

            $repair = $repairItemRepository->save($repairItem, Action::CREATE);
            if (!$repair) {
                $err = $repairItemRepository->getLastError() ?: 'неизвестная ошибка БД';
                throw new Exception(
                    "Ошибка создания repair в repairManager (RepairCost={$repairItem->RepairCost}): {$err}"
                );
            }
            $repairItem = $repair;
        }

        $itemController = new ItemController();
        if ($operationType === OperationType::WRITE_OFF) {
            $itemController->unlinkFromBrigade($ID_TMC);
        }
        $itemController->changeStatusTMC(
            $ID_TMC,
            OperationType::getStatusTransition($operationType)
        );

        $historyNote = $repairItem->InvoiceNumber ?? '';
        if ($operationType === OperationType::WRITE_OFF) {
            $historyNote = trim((string) ($repairItem->RepairDescription ?? '')) !== ''
                ? (string) $repairItem->RepairDescription
                : (string) ($repairItem->InvoiceNumber ?? 'Списание');
        }

        $itemController->logHistoryOperation(
            $operationType,
            $ID_TMC,
            null,
            $historyNote
        );
        return $repairItem;
    }

    public function updateRepair($data, bool $reconcileStatus = true): bool
    {
        $repairItemRepository = $this->container->get(RepairItemRepository::class);

        $repairId = (int) ($data['ID_Repair'] ?? 0);
        $currentRepair = $repairItemRepository->findById($repairId, 'ID_Repair');
        if (!$currentRepair) {
            throw new Exception("Запись о ремонте с ID {$repairId} не найдена");
        }
        $tmcId = (int) ($data['ID_TMC'] ?? $currentRepair->ID_TMC ?? 0);

        $repairData = new RepairItem($data);
        $changed = false;
        $persistableProps = $currentRepair->getPersistableProperties();
        $readOnlyFields = $currentRepair->getReadOnlyFields();

        foreach ($persistableProps as $prop) {
            if (in_array($prop, $readOnlyFields, true)) {
                continue;
            }
            // только явно переданные поля — иначе конструктор затирает даты «сегодня/null»
            // UPD: учитывать и нижний регистр ключа из FormData/драйвера
            if ($prop === 'UPD') {
                if (!array_key_exists('UPD', $data) && !array_key_exists('upd', $data)) {
                    continue;
                }
            } elseif (!array_key_exists($prop, $data)) {
                continue;
            }

            // DateToService не инициализирован в DTO (пустая/невалидная дата из формы)
            if ($prop === 'DateToService' && !isset($repairData->DateToService)) {
                continue;
            }

            $newValue = $prop === 'UPD'
                ? (string) ($data['UPD'] ?? $data['upd'] ?? $repairData->UPD ?? '')
                : $repairData->$prop;
            $currentValue = $currentRepair->$prop;
            if ($prop === 'UPD') {
                $currentValue = (string) ($currentValue ?? '');
            }

            // пустая дата отправки из формы — не затираем существующую
            if ($prop === 'DateToService' && ($newValue === '' || $newValue === null)) {
                continue;
            }
            // локация 0 из формы (не подгрузилась) — не затираем
            if ($prop === 'IDLocation' && (int) $newValue <= 0 && (int) $currentValue > 0) {
                continue;
            }

            if (is_int($currentValue)) {
                $newValue = (int) $newValue;
            } elseif (is_float($currentValue)) {
                $newValue = (float) $newValue;
            } elseif (is_bool($currentValue)) {
                $newValue = filter_var($newValue, FILTER_VALIDATE_BOOLEAN);
            }

            // сравнение дат без времени/миллисекунд
            if (in_array($prop, ['DateToService', 'DateReturnService'], true)) {
                $raw = $data[$prop];
                if ($raw === null || $raw === '') {
                    // пустая дата возврата — очищаем; дату отправки пустой не затираем
                    if ($prop === 'DateToService') {
                        continue;
                    }
                    $newValue = null;
                } else {
                    $newValue = RepairItem::formatDateForSQL($raw);
                    if ($newValue === null) {
                        throw new Exception(
                            "Некорректная дата «{$raw}». Укажите в формате дд.мм.гггг"
                        );
                    }
                }

                $curNorm = $this->normalizeDateForCompare($currentValue);
                $newNorm = $this->normalizeDateForCompare($newValue);
                if ($curNorm === $newNorm) {
                    continue;
                }
                $changed = true;
                $currentRepair->$prop = $newValue;
                continue;
            }

            if ($currentValue !== $newValue) {
                $changed = true;
                $currentRepair->$prop = $newValue;
            }
        }

        if ($changed) {
            // перед UPDATE в SQL Server даты только в Y-m-d H:i:s
            // (иначе дд.мм.гггг / локаль PDO даёт nvarchar→datetime out of range)
            foreach (['DateToService', 'DateReturnService'] as $dateProp) {
                if (!isset($currentRepair->$dateProp) || $currentRepair->$dateProp === null || $currentRepair->$dateProp === '') {
                    if ($dateProp === 'DateReturnService') {
                        $currentRepair->DateReturnService = null;
                    }
                    continue;
                }
                $normalized = RepairItem::formatDateForSQL($currentRepair->$dateProp);
                if ($normalized === null) {
                    throw new Exception(
                        "Некорректная дата в поле {$dateProp}. Укажите в формате дд.мм.гггг"
                    );
                }
                $currentRepair->$dateProp = $normalized;
            }

            $result = $repairItemRepository->save($currentRepair);
            if ($result === null) {
                $err = $repairItemRepository->getLastError() ?: 'неизвестная ошибка БД';
                throw new Exception("Не удалось сохранить ремонт №{$repairId}: {$err}");
            }
        }

        if ($reconcileStatus && $tmcId > 0) {
            $this->reconcileInventoryStatusWithRepairs($tmcId);
        }

        return true;
    }

    private function normalizeDateForCompare($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        try {
            return (new DateTime($raw))->format('Y-m-d');
        } catch (Exception $e) {
            return $raw;
        }
    }

    /**
     * Счёт заполнен (не пустой / не прочерк / не «без счета»).
     */
    public function repairRecordHasInvoice($repair): bool
    {
        $invoice = trim((string) ($repair->InvoiceNumber ?? ''));
        if ($invoice === '' || $invoice === '-') {
            return false;
        }
        if (preg_match('/^0+$/', $invoice)) {
            return false;
        }
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $invoice));
        $normalized = str_replace('ё', 'е', $normalized);
        $placeholders = [
            'без счета',
            'без счета.',
            'нет счета',
            'нет счета.',
            'без счет',
            'без счет.',
        ];
        return !in_array($normalized, $placeholders, true);
    }

    /**
     * Открытая (не закрытая возвратом) запись ремонта ТМЦ.
     */
    public function findOpenRepairForTmc(int $tmcId): ?RepairItem
    {
        if ($tmcId <= 0) {
            return null;
        }
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $open = $repairItemRepository->findBy(
            'WHERE ID_TMC = ' . (int) $tmcId
            . ' AND inBasket = 0 AND DateReturnService IS NULL ORDER BY ID_Repair DESC'
        );
        if ($open === null || $open->count() === 0) {
            return null;
        }
        $first = $open->first();
        return $first instanceof RepairItem ? $first : null;
    }

    /**
     * Единый источник правды: статус InventoryItem ↔ открытая запись RepairItem.
     * Устраняет «на главной в ремонте / в архиве сдан / на объекте ещё висит».
     */
    public function reconcileInventoryStatusWithRepairs(int $tmcId): void
    {
        if ($tmcId <= 0) {
            return;
        }

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $item = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        if ($item === null) {
            return;
        }

        $status = (int) ($item->Status ?? -1);
        // спецпотоки списания не трогаем
        if (in_array($status, [StatusItem::WrittenOff, StatusItem::ProposeWriteOff], true)) {
            return;
        }

        $openRepair = $this->findOpenRepairForTmc($tmcId);

        if ($openRepair !== null) {
            $target = $this->repairRecordHasInvoice($openRepair)
                ? StatusItem::Repair
                : StatusItem::ConfirmRepairTMC;
            if ($status !== $target) {
                $item->Status = $target;
                $inventoryItemRepository->save($item);
            }
            return;
        }

        // Нет открытого ремонта — нельзя оставаться «в ремонте» / «на согласовании»
        if (in_array($status, [StatusItem::Repair, StatusItem::ConfirmRepairTMC], true)) {
            $item->Status = StatusItem::Released;
            $inventoryItemRepository->save($item);
        }
    }

    /**
     * Починить рассинхрон по всем активным ремонтам (уведомления + архив).
     * @return int сколько ТМЦ поправили
     */
    public function reconcileAllActiveRepairStatuses(): int
    {
        $fixed = 0;
        $seen = [];
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);

        $statusRepair = (int) StatusItem::Repair;
        $statusConfirm = (int) StatusItem::ConfirmRepairTMC;

        $byStatus = $inventoryItemRepository->findBy(
            "WHERE Status IN ({$statusRepair}, {$statusConfirm})"
        );
        if ($byStatus) {
            foreach ($byStatus as $inv) {
                $id = (int) ($inv->ID_TMC ?? 0);
                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $before = (int) ($inv->Status ?? -1);
                $this->reconcileInventoryStatusWithRepairs($id);
                $afterItem = $inventoryItemRepository->findById($id, 'ID_TMC');
                $after = (int) ($afterItem->Status ?? -1);
                if ($before !== $after) {
                    $fixed++;
                }
            }
        }

        // Открытый ремонт при «нормальном» статусе на объекте — тоже чиним
        $pdo = $this->container->get(Database::class)->getConnection();
        $sql = "SELECT DISTINCT ri.ID_TMC
            FROM RepairItem ri
            INNER JOIN InventoryItem ii ON ri.ID_TMC = ii.ID_TMC
            WHERE ri.inBasket = 0
              AND ri.DateReturnService IS NULL
              AND ii.Status NOT IN (
                  {$statusRepair}, {$statusConfirm},
                  " . (int) StatusItem::ProposeWriteOff . ",
                  " . (int) StatusItem::WrittenOff . "
              )";
        $stmt = $pdo->query($sql);
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = (int) ($row['ID_TMC'] ?? 0);
                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $beforeItem = $inventoryItemRepository->findById($id, 'ID_TMC');
                $before = (int) ($beforeItem->Status ?? -1);
                $this->reconcileInventoryStatusWithRepairs($id);
                $afterItem = $inventoryItemRepository->findById($id, 'ID_TMC');
                $after = (int) ($afterItem->Status ?? -1);
                if ($before !== $after) {
                    $fixed++;
                }
            }
        }

        return $fixed;
    }

    /**
     * Затраты на ремонт для аналитики (все записи вне корзины).
     * @return list<array<string, mixed>>
     */
    public function getRepairSpendForAnalytics(): array
    {
        // Один SQL вместо ORM + вложенных связей (Location/Brand/Model на каждую строку)
        $pdo = $this->container->get(Database::class)->getConnection();
        $sql = "SELECT
                ri.ID_Repair,
                ri.ID_TMC,
                ri.RepairCost,
                ri.InvoiceNumber,
                ri.DateToService,
                ri.DateReturnService,
                ii.NameTMC,
                ii.SerialNumber,
                b.NameBrand,
                m.NameModel,
                objLoc.NameLocation AS ObjectLocationName,
                repLoc.NameLocation AS RepairLocationName,
                CAST(ISNULL(repLoc.IsRepair, 0) AS INT) AS RepairLocIsRepair
            FROM RepairItem ri
            LEFT JOIN InventoryItem ii ON ri.ID_TMC = ii.ID_TMC
            LEFT JOIN BrandTMC b ON ii.IDBrandTMC = b.IDBrandTMC
            LEFT JOIN ModelTMC m ON ii.IDModel = m.IDModel
            LEFT JOIN Location objLoc ON ii.IDLocation = objLoc.IDLocation
            LEFT JOIN Location repLoc ON ri.IDLocation = repLoc.IDLocation
            WHERE ri.inBasket = 0
            ORDER BY ri.DateToService DESC, ri.ID_Repair DESC";

        $stmt = $pdo->query($sql);
        $rows = [];
        if (!$stmt) {
            return $rows;
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dateRaw = $row['DateToService'] ?? $row['DateReturnService'] ?? '';
            $dateIso = '';
            if ($dateRaw) {
                try {
                    $dt = new DateTime(is_string($dateRaw) ? $dateRaw : (string) $dateRaw);
                    $dateIso = $dt->format('Y-m-d');
                } catch (Throwable $e) {
                    $dateIso = '';
                }
            }

            $supplierName = '';
            if (!empty($row['RepairLocIsRepair'])) {
                $supplierName = trim((string) ($row['RepairLocationName'] ?? ''));
            }

            $locationName = trim((string) ($row['ObjectLocationName'] ?? ''));
            if ($locationName === '' && empty($row['RepairLocIsRepair'])) {
                $locationName = trim((string) ($row['RepairLocationName'] ?? ''));
            }

            $rows[] = [
                'idRepair' => (int) ($row['ID_Repair'] ?? 0),
                'idTmc' => (int) ($row['ID_TMC'] ?? 0),
                'cost' => (float) ($row['RepairCost'] ?? 0),
                'invoice' => trim((string) ($row['InvoiceNumber'] ?? '')),
                'date' => $dateIso,
                'name' => (string) ($row['NameTMC'] ?? ''),
                'brand' => (string) ($row['NameBrand'] ?? ''),
                'model' => (string) ($row['NameModel'] ?? ''),
                'location' => $locationName,
                'supplier' => $supplierName,
                'serial' => (string) ($row['SerialNumber'] ?? ''),
            ];
        }

        return $rows;
    }

    public function writeOffItems(): ?Collection
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $registrationInventoryItemRepository = $this->container->get(RegistrationInventoryItemRepository::class);

        $statusWrittenOff = (int) StatusItem::WrittenOff;

        // Только колонки RepairItem — иначе PDO FETCH_ASSOC затирает Status/IDLocation
        // чужими одноимёнными полями из JOIN (User.Status, Location.IDLocation и т.д.).
        $query = "SELECT RepairItem.*
        FROM RepairItem
        INNER JOIN InventoryItem ON RepairItem.ID_TMC = InventoryItem.ID_TMC
        WHERE RepairItem.inBasket = 0
          AND InventoryItem.Status <> {$statusWrittenOff}
        ORDER BY RepairItem.ID_TMC, RepairItem.ID_Repair";

        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');
        $repairItemRepository->addRelationship('InventoryItem', $inventoryItemRepository, 'ID_TMC', 'ID_TMC');
        $repairItemRepository->addRelationship(
            'RegistrationInventoryItem',
            $registrationInventoryItemRepository,
            'ID_TMC',
            'IDRegItem'
        );

        return $repairItemRepository->getAll($query);
    }

    /**
     * Прямой UPDATE inBasket — без полного save (даты/поля не затираем и не валимся на PDO-датах).
     */
    private function setRepairBasketFlag(?int $repairId, ?int $tmcId, bool $inBasket): bool
    {
        $pdo = $this->container->get(Database::class)->getConnection();
        $flag = $inBasket ? 1 : 0;
        if ($repairId !== null && $repairId > 0) {
            $stmt = $pdo->prepare('UPDATE RepairItem SET inBasket = :flag WHERE ID_Repair = :id');
            return $stmt->execute([':flag' => $flag, ':id' => $repairId]);
        }
        if ($tmcId !== null && $tmcId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE RepairItem SET inBasket = :flag WHERE ID_TMC = :tmc AND inBasket <> :flag'
            );
            return $stmt->execute([':flag' => $flag, ':tmc' => $tmcId]);
        }
        return false;
    }

    /**
     * Переместить все записи ремонта ТМЦ в корзину (без toggle).
     */
    public function RepairInBasket($ID_TMC): bool
    {
        return $this->setRepairBasketFlag(null, (int) $ID_TMC, true);
    }

    /**
     * Переместить одну запись ремонта в корзину
     */
    public function RepairRecordInBasket(int $ID_Repair): bool
    {
        return $this->setRepairBasketFlag((int) $ID_Repair, null, true);
    }

    public function getBasketItems(): ?Collection
    {
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        // Только RepairItem.* — JOIN через SELECT * затирал поля
        $query = "SELECT RepairItem.*
          FROM RepairItem
          WHERE RepairItem.inBasket = 1
          ORDER BY RepairItem.ID_TMC, RepairItem.ID_Repair";

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

        return $repairItemRepository->getAll($query);
    }

    /**
     * Вернуть все записи ремонта ТМЦ из корзины (без toggle).
     */
    public function returnFromBasket($ID_TMC): bool
    {
        return $this->setRepairBasketFlag(null, (int) $ID_TMC, false);
    }

    /**
     * Вернуть одну запись ремонта из корзины.
     */
    public function returnRepairRecordFromBasket(int $ID_Repair): bool
    {
        return $this->setRepairBasketFlag((int) $ID_Repair, null, false);
    }

    /**
     * Очистить корзину ремонта — вернуть все позиции (inBasket = 0)
     */
    public function clearBasket(): bool
    {
        $pdo = $this->container->get(Database::class)->getConnection();
        $stmt = $pdo->prepare('UPDATE RepairItem SET inBasket = 0 WHERE inBasket <> 0');
        return $stmt->execute();
    }

    public function getItemWithRepairs($ID_TMC, ?int $ID_Repair = null): ?Collection
    {
        //$repairItemRepository = $this->container->get(RepairItemRepository::class);
        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);

        $repairFilter = $ID_Repair ? " AND RepairItem.ID_Repair = {$ID_Repair}" : "";

        // Только колонки RepairItem — SELECT * + JOIN затирает UPD/Status/IDLocation
        $query = "SELECT RepairItem.*
          FROM RepairItem
          WHERE RepairItem.ID_TMC = {$ID_TMC}
            AND RepairItem.inBasket = 0
            {$repairFilter}
          ORDER BY RepairItem.ID_Repair";

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

        return $repairItemRepository->getAll($query);
    }

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
                    if (empty($repair->Location)) {
                        $repair->Location = $inventoryItem->Location;
                    }
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
                $main->Location = $inventoryItem->Location;
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
     * Краткий список списанных для модалки на home.
     * Берём по Status=Списано, без истории ремонтов.
     */
    public function getWrittenOffSummary(int $limit = 50): array
    {
        $limit = max(1, min(200, (int) $limit));
        $statusWrittenOff = (int) StatusItem::WrittenOff;
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
            WHERE ii.Status = {$statusWrittenOff}
            ORDER BY ii.ID_TMC DESC
        ";

        $pdo = DatabaseFactory::create()->getConnection();
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            // query() молча падает — лучше явная ошибка в json
            $error = $pdo->errorInfo();
            throw new Exception($error[2] ?? 'Ошибка запроса списанных ТМЦ');
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

    /**
     * Полная история ремонтов ТМЦ: сдача/приём + затраты.
     */
    public function getRepairHistoryForTmc(int $tmcId): array
    {
        if ($tmcId <= 0) {
            throw new Exception('Не указан ТМЦ');
        }

        $inventoryItemRepository = $this->container->get(InventoryItemRepository::class);
        $repairItemRepository = $this->container->get(RepairItemRepository::class);
        $locationRepository = $this->container->get(LocationRepository::class);
        $brandTMCRepository = $this->container->get(BrandTMCRepository::class);

        $item = $inventoryItemRepository->findById($tmcId, 'ID_TMC');
        if (!$item) {
            throw new Exception("ТМЦ {$tmcId} не найден");
        }

        $location = null;
        if ((int) ($item->IDLocation ?? 0) > 0) {
            $location = $locationRepository->findById((int) $item->IDLocation, 'IDLocation');
        }
        $brand = null;
        if ((int) ($item->IDBrandTMC ?? 0) > 0) {
            $brand = $brandTMCRepository->findById((int) $item->IDBrandTMC, 'IDBrandTMC');
        }

        $repairItemRepository->addRelationship('Location', $locationRepository, 'IDLocation', 'IDLocation');
        $repairs = $repairItemRepository->findBy(
            "WHERE ID_TMC = {$tmcId} AND inBasket = 0 ORDER BY ID_Repair DESC"
        );

        $rows = [];
        $totalCost = 0.0;
        if ($repairs) {
            foreach ($repairs as $repair) {
                $cost = (float) ($repair->RepairCost ?? 0);
                $totalCost += $cost;
                $serviceName = (string) ($repair->Location?->NameLocation ?? '');
                $dateTo = '';
                if (!empty($repair->DateToService)) {
                    $ts = strtotime((string) $repair->DateToService);
                    $dateTo = $ts ? date('d.m.Y', $ts) : (string) $repair->DateToService;
                }
                $dateRet = '';
                if (!empty($repair->DateReturnService)) {
                    $ts = strtotime((string) $repair->DateReturnService);
                    $dateRet = $ts ? date('d.m.Y', $ts) : (string) $repair->DateReturnService;
                }
                $rows[] = [
                    'id' => (int) ($repair->ID_Repair ?? 0),
                    'invoice' => (string) ($repair->InvoiceNumber ?? ''),
                    'upd' => (string) ($repair->UPD ?? ''),
                    'cost' => $cost,
                    'dateTo' => $dateTo,
                    'dateReturn' => $dateRet,
                    'note' => (string) ($repair->RepairDescription ?? ''),
                    'service' => $serviceName,
                ];
            }
        }

        $historyController = new HistoryOperationsController();
        $ops = $historyController->getHistoryOperations($tmcId);
        $operations = [];
        if ($ops) {
            foreach ($ops as $op) {
                $comment = (string) ($op->CommentsHistory->ValueComment ?? '');
                $lower = mb_strtolower($comment);
                $isRepairRelated =
                    str_contains($lower, 'ремонт')
                    || str_contains($lower, 'сервис')
                    || str_contains($lower, 'списан')
                    || str_contains($lower, 'возврат');
                if (!$isRepairRelated) {
                    continue;
                }
                $opDate = '';
                if (!empty($op->HistoryData)) {
                    $ts = strtotime((string) $op->HistoryData);
                    $opDate = $ts ? date('d.m.Y H:i', $ts) : (string) $op->HistoryData;
                }
                $operations[] = [
                    'date' => $opDate,
                    'comment' => $comment,
                    'user' => (string) ($op->User->FIO ?? '—'),
                ];
            }
        }

        return [
            'tmc' => [
                'id' => $tmcId,
                'name' => (string) ($item->NameTMC ?? ''),
                'serial' => (string) ($item->SerialNumber ?? ''),
                'brand' => (string) ($brand->NameBrand ?? ''),
                'location' => (string) ($location->NameLocation ?? ''),
                'status' => (int) ($item->Status ?? -1),
                'statusText' => StatusItem::getDescription((int) ($item->Status ?? -1)) ?? '—',
            ],
            'repairs' => $rows,
            'operations' => $operations,
            'totalCost' => $totalCost,
            'repairCount' => count($rows),
        ];
    }
}
