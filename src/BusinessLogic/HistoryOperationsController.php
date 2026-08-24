<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/HistoryOperationsController.log');
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Repositories/HistoryOperationsRepository.php';
require_once __DIR__ . '/../Repositories/CommentsHistoryRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';

class HistoryOperationsController
{
    private Container $container;
    private Logger $logger;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/HistoryOperationsController.log');
        });
        $this->logger = $this->container->get(Logger::class);
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['IDUser'] ?? 0);
    }

    private function locationName(?InventoryItem $item): string
    {
        if (!$item || empty($item->Location)) {
            return 'не указан';
        }
        $name = $item->Location->NameLocation ?? '';
        return $name !== '' ? (string) $name : 'не указан';
    }

    /**
     * Надёжная запись операции в историю
     */
    private function persistHistory(InventoryItem $item, string $comment): void
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            throw new Exception('Нет ID пользователя в сессии для записи истории');
        }

        $historyOperation = new HistoryOperations($item, $userId, $comment);
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);
        if (!$resultComment || empty($resultComment->IDComment)) {
            throw new Exception('Не удалось сохранить комментарий истории');
        }

        $historyOperation->IDComment = (int) $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
        if (!$resultHistory) {
            throw new Exception('Не удалось сохранить запись HistoryOperations');
        }
    }

    public function OperationCreateTMC(InventoryItem $item)
    {
        $this->persistHistory($item, "Создание ТМЦ. ID:{$item->getId()}");
    }

    public function OperationUpdateTMC(InventoryItem $item)
    {
        $this->persistHistory($item, "Карточка ТМЦ изменена.");
    }

    public function AcceptanceConfirmedTMC(InventoryItem $item)
    {
        $this->persistHistory($item, "ТМЦ принято. Объект - {$this->locationName($item)}.");
    }

    public function RefusedConfirmedTMC(InventoryItem $item)
    {
        $this->persistHistory($item, "ТМЦ не принято. Возвращено на объект - {$this->locationName($item)}.");
    }

    public function getHistoryOperations(int $currentID): Collection
    {
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $userRepository = $this->container->get(UserRepository::class);

        $historyOperationsRepository->addRelationship(
            'CommentsHistory',
            $commentsHistoryRepository,
            'IDComment',
            'IDComment'
        );
        $historyOperationsRepository->addRelationship(
            'User',
            $userRepository,
            'IDUser',
            'IDUser'
        );

        $historyOperations = $historyOperationsRepository->findBy("where ID_TMC = {$currentID} ORDER BY HistoryData DESC");
        return $historyOperations ?? new Collection(HistoryOperations::class, []);
    }

    public function OperationDistributeTMC(InventoryItem $item, string $upd = '')
    {
        $comment = "ТМЦ передано. Объект - {$this->locationName($item)}.";
        $upd = trim($upd);
        if ($upd !== '') {
            $comment .= " УПД: {$upd}.";
        }
        $this->persistHistory($item, $comment);
    }

    public function RepairConfirmedTMC(InventoryItem $item, string $note)
    {
        $this->persistHistory($item, "Ремонт ТМЦ согласован - № счета {$note}");
    }

    public function AssignToBrigadeTMC(InventoryItem $item, Brigades $brigade)
    {
        $brigadeName = $brigade->NameBrigade ?? '';
        $brigadir = $brigade->NameBrigadir ?? '';
        $this->persistHistory($item, "ТМЦ выдано в бригаду - {$brigadeName}, бригадир - {$brigadir}");
    }

    public function WriteOffTMC(InventoryItem $item, ?string $note = null)
    {
        $comment = "ТМЦ списано. Объект - {$this->locationName($item)}.";
        $note = trim((string) $note);
        if ($note !== '') {
            $comment .= " Причина: {$note}";
        }
        $this->persistHistory($item, $comment);
    }

    public function CancelWriteOffTMC(InventoryItem $item)
    {
        $this->persistHistory(
            $item,
            "Отмена списания. ТМЦ возвращено на склад. Объект - {$this->locationName($item)}."
        );
    }

    public function ReturnFromRepairTMC(InventoryItem $item, string $note)
    {
        $comment = "ТМЦ возвращено из сервиса, на объект - {$this->locationName($item)}.";
        $note = trim((string) $note);
        if ($note !== '') {
            $comment .= " Примечания: {$note}";
        }
        $this->persistHistory($item, $comment);
    }

    public function ReturnFromWork(InventoryItem $item, Brigades $brigade)
    {
        $brigadeName = $brigade->NameBrigade ?? '';
        $brigadir = $brigade->NameBrigadir ?? '';
        $this->persistHistory($item, "ТМЦ изъято из бригады - {$brigadeName}, бригадир - {$brigadir}");
    }

    public function AcceptForRepairTMC(InventoryItem $item, string $note)
    {
        $comment = "ТМЦ отправлено в сервис. Объект - {$this->locationName($item)}. "
            . "Причина - {$note}. "
            . "Ожидание подтверждения ремонта.";
        $this->persistHistory($item, $comment);
    }
}
