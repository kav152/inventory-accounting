<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/SettingController.log');
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';

class SettingController
{
    private Container $container;
    private Logger $logger;
    public function __construct()
    {
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/SettingController.log');
        });
        $this->logger = $this->container->get(Logger::class);
    }
    /**
     * Добавить пользователя
     * @param mixed $data
     */
    public function addUser($data): ?object
    {
        $user = new User($data);
        $user->setPassword($user->Password);
        $userRepository = $this->container->get(UserRepository::class);
        return $userRepository->save($user);
    }

    /**
     * Обновить данные пользователя
     * @param mixed $data
     * @return void
     */
    public function updateUser($data): ?object
    {
        //$this->logger->log('updateUser', 'НАчало работы updateUser', $data);

        $user = new User($data);
        $properties = $user->getPersistableProperties();
        $userRepository = $this->container->get(UserRepository::class);
        $oldUser = $userRepository->findById($user->IDUser, "IDUser");
        if ($user->Password === "") {
            $user->Password = $oldUser->Password;
        }
        else
        {
            $user->setPassword($user->Password);
        }

        foreach ($properties as $prop) {
            if ($oldUser->$prop !== $user->$prop) {                
                $userRepository->save($user);
                return $user;
            }
        }
        return null;
    }

    /**
     * Получить список пользователей
     * @param mixed $isActiveOnly только актуальноые пользователи по уполчанию = true
     */
    public function getUsers($isActiveOnly = true): ?Collection
    {
        $userRepository = $this->container->get(UserRepository::class);
        if($isActiveOnly)
        {
            return $userRepository->findBy("WHERE isActive = 1");
        }       
        return $userRepository->getAll();

    }

}