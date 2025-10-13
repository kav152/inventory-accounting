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
    private $currentUser;
    public function __construct()
    {
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/HistoryOperationsController.log');
        });
        $this->logger = $this->container->get(Logger::class);
    }

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
    }

    public function RefusedConfirmedTMC(InventoryItem $item)
    {
        //"ТМЦ не принято. Возвращено на объект - {tmc.Location.NameLocation}."
        $historyOperation = new HistoryOperations($item, $_SESSION["IDUser"], "ТМЦ не принято. Возвращено на объект - {$item->Location->NameLocation}.");
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $resultComment = $commentsHistoryRepository->save($historyOperation->CommentsHistory);

        $historyOperation->IDComment = $resultComment->IDComment;
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $resultHistory = $historyOperationsRepository->save($historyOperation);
    }

    public function getHistoryOperations(int $currentID): Collection
    {
        $commentsHistoryRepository = $this->container->get(CommentsHistoryRepository::class);
        $historyOperationsRepository = $this->container->get(HistoryOperationsRepository::class);
        $userRepository = $this->container->get(UserRepository::class);
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
    }
}
