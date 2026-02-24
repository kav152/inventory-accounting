<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDUsers.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/User.php';
require_once __DIR__ . '/../UserController.php';

class processCUDUsers extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new UserController(), User::class);
    }

    protected function create($data, ?int $patofID = null)
    {
        // Хешируем пароль при создании пользователя
        if (!empty($data['Password'])) {
            $user = new User();
            $user->setPassword($data['Password']);
            $data['Password'] = $user->Password;
        } else {
            throw new Exception('Пароль обязателен при создании пользователя');
        }
        $result = parent::create($data);
        return $result;
    }

    protected function update($id, $data, ?int $patofID = null)
    {
        // Получаем текущего пользователя для сохранения пароля при массовом обновлении
        $userController = new UserController();
        $currentUser = $userController->getUser($id);
        
        // Если пароль не указан в данных, сохраняем текущий
        if (empty($data['Password']) && $currentUser) {
            $data['Password'] = $currentUser->Password;
        } 
        // Если пароль указан, хешируем его
        elseif (!empty($data['Password'])) {
            $user = new User();
            $user->setPassword($data['Password']);
            $data['Password'] = $user->Password;
        }
        
        $result = parent::update($id, $data);
        return $result;
    }

    

    protected function prepareData($postData)
    {
        //error_log("Данные для массового обновления пользователей: " . print_r($postData, true));

        return [
            'IDUser' => (int)$postData['id'],
            'Surname' => $postData['Surname'] ?? '',
            'Name' => $postData['Name'] ?? '',
            'Patronymic' => $postData['Patronymic'] ?? '',
            'Password' => $postData['Password'] ?? '', // Пароль не меняется при массовом обновлении
            'Status' => isset($postData['Status']) ? (int)$postData['Status'] : 1,
            'isActive' => $postData['isActive']
        ];
    }

    protected function prepareResultEntity($user)
    {
        return [
                'id' => $user->getId(),
                'Surname' => $user->Surname,
                'Name' => $user->Name,        
                'Patronymic' => $user->Patronymic,        
                'Status' => $user->Status,
                'isActive' => $user->isActive,
            ];
    }
}

// Использование
$handler = new processCUDUsers();
$handler->handleRequest();
