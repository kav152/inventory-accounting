<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/UserController.log');
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/CudService/CUDFactory.php';

require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../Container.php';

class UserController
{
    private Container $container;
    private Logger $logger;
    private CUDFactory $cudFactory;
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/UserController.log');
        });
        $this->logger = $this->container->get(Logger::class);

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
    }

    public function getUsers(): Collection
    {
        $userRepository = $this->container->get(UserRepository::class);
        return $userRepository->findBy(' where idUser > 0');
    }

    /**
     * Получить сущность пользователя
     * @param ?int $idUser
     */
    public function getUser(?int $idUser): ?object
    {

        if (!$idUser) {
            $result = new User();
            $result->mountEmptyDocument();
            return $result;
        }

        $userRepository = $this->container->get(UserRepository::class);
        return $userRepository->findById($idUser, "idUser");
    }


    /*public function createUser($data): ?object
    {
        $newUser = new User($data);
        return $this->cudFactory->create($newUser);
    }*/

    /**
     * Создать сущность объекта
     * @param mixed $object
     * @return User|null - список сущностей
     */
    public function create($object): ?object
    {
        //$newUser = new User($data);
        $result = $this->cudFactory->create($object);

        return $result;
    }
    /**
     * Обновить сущность объекта
     * @param mixed $object
     * @return User|null - список сущностей
     */
    public function update($object): ?object
    {
        return $this->cudFactory->update($object);
    }
}