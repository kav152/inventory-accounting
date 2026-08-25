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
<<<<<<< HEAD
    private $currentUser;
    public function __construct()
    {
=======

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
>>>>>>> source/feature/local-updates-2026-08
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/HistoryOperationsController.log');
        });
        $this->logger = $this->container->get(Logger::class);
    }

<<<<<<< HEAD
    /**
     * Подтверждение о создании ТМЦ
     * @param InventoryItem $item
     * @return void
     */
    public function OperationCreateTMC(InventoryItem $item)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "Создание ТМЦ. ID:{$item->getId()}");

        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }

    /**
     * Обновление ТМЦ
     * @param InventoryItem $item
     * @return void
     */
    public function OperationUpdateTMC(InventoryItem $item)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "Картачка ТМЦ изменена.");

        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }



    /**
     * Подтверждение о приемки ТМЦ
     * @param InventoryItem $item
     * @return void
     */
    public function AcceptanceConfirmedTMC(InventoryItem $item)
    {
        $historyOperation = new HistoryOperations($item,$_SESSION["IDUser"], "ТМЦ принято. Объект - {$item->Location->NameLocation}.");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
=======
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
>>>>>>> source/feature/local-updates-2026-08
    }

    public function RefusedConfirmedTMC(InventoryItem $item)
    {
<<<<<<< HEAD
        //"ТМЦ не принято. Возвращено на объект - {tmc.Location.NameLocation}."
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ не принято. Возвращено на объект - {$item->Location->NameLocation}.");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
=======
        $this->persistHistory($item, "ТМЦ не принято. Возвращено на объект - {$this->locationName($item)}.");
>>>>>>> source/feature/local-updates-2026-08
    }

    public function getHistoryOperations(int $currentID): Collection
    {
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $userRepository = $this->container->get(UserRepository::class);
<<<<<<< HEAD
        // Добавление связи между HistoryOperations и CommentsHistory
        $historyOperationsRepository->addRelationship(
            'CommentsHistory',          // Свойство в HistoryOperations для связи
            $commentsHistoryRepository,       // Репозиторий связанной сущности
            'IDComment',                // Внешний ключ в HistoryOperations
            'IDComment'                 // Первичный ключ в CommentsHistory
        );
        // Добавление связи между HistoryOperations и CommentsHistory
        $historyOperationsRepository->addRelationship(
            'User',                         // Свойство в User для связи
            $userRepository,                // Репозиторий связанной сущности
            'IDUser',                       // Внешний ключ в IDUser
            'IDUser'                    // Первичный ключ в IDUser
        );

        $historyOperations = $historyOperationsRepository->findBy("where ID_TMC = {$currentID} ORDER BY HistoryData DESC");
        return $historyOperations;
    }

    /**
     * Операция распределения ТМС
     * @param InventoryItem $item
     * @return void
     */
    public function OperationDistributeTMC(InventoryItem $item)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ передано. Объект - {$item->Location->NameLocation}.");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);

        /*
        $this->logger->log(
            'OperationDistributeTMC',
            'historyOperation',
            [
                'historyOperation' => $historyOperation
            ]
        );*/
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }
    /**
     * Операция подтверждения ремонта ТМЦ
     * @param InventoryItem $item
     * @return void
     */
    public function RepairConfirmedTMC(InventoryItem $item, string $note)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "Ремонт ТМЦ согласован - № счета {$note}");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }
    /**
     * Операция передать ТМЦ в бригаду
     * @param InventoryItem $item
     * @return void
     */
    public function AssignToBrigadeTMC(InventoryItem $item, Brigades $brigade)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ выдано в бригаду - {$brigade->NameBrigade}, бригадир - {$brigade->NameBrigadir}");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);

        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }
    /**
     * Операция of WriteOffTMC
     * @param InventoryItem $item
     * @return void
     */
    public function WriteOffTMC(InventoryItem $item)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ списано. Объект - {$item->Location->NameLocation}.");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }
    /**
     * Операция of ReturnFromRepairTMC
     * @param InventoryItem $item
     * @return void
     */
    public function ReturnFromRepairTMC(InventoryItem $item, string $note)
    {
        $comment = "ТМЦ возвращено из сервиса, на объект - {$item->Location->NameLocation}. ";
        if($note)
        {
            $comment = $comment . "\nПримечания: {$note}";
        }
            

        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], $comment);
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);

        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }

    /**
     * Операция возвпащение на склад кладовщика от бригады
     * @param InventoryItem $item
     * @return void
     */
    public function ReturnFromWork(InventoryItem $item, Brigades $brigade)
    {
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ изъято из бригады - {$brigade->NameBrigade}, бригадир - {$brigade->NameBrigadir}");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);

        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }
    /**
     * Операция of AcceptForRepairTMC
     * @param InventoryItem $item
     * @return void
     */
    public function AcceptForRepairTMC(InventoryItem $item, string $note)
    {
        $comment = "ТМЦ отправлено в сервис. Объект - {$item->Location->NameLocation}. "
            . "Причина - {$note} "
            . "Ожидание подтверждение ремонта.";

        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], $comment);
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);

        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
=======

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
>>>>>>> source/feature/local-updates-2026-08
    }
}
