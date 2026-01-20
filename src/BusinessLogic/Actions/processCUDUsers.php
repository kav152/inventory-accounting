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

    protected function update($id, $data, int $patofID = null)
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

    /*  protected function executeAction($action, $data, $id, $patofID)
    {
        error_log('Выполняем массовое обновление пользователей');

        if ($action !== 'update') {
            throw new Exception('Для массового обновления доступно только действие UPDATE');
        }

        // Получаем все данные пользователей из исходного POST
        $jsonData = json_decode(file_get_contents('php://input'), true);

        if (!isset($jsonData['users']) || empty($jsonData['users'])) {
            throw new Exception('Нет данных для обновления');
        }

        $results = [];
        $userController = new UserController();

        foreach ($jsonData['users'] as $userId => $userData) {
            try {
                // Создаем полный объект пользователя
                $userUpdateData = [
                    'IDUser' => (int)$userId,
                    'Surname' => $userData['Surname'] ?? '',
                    'Name' => $userData['Name'] ?? '',
                    'Patronymic' => $userData['Patronymic'] ?? '',
                    'Password' => '', // Пароль не меняем при массовом обновлении
                    'Status' => isset($userData['Status']) ? (int)$userData['Status'] : 1,
                    'isActive' => in_array($userId, $jsonData['active'] ?? [])
                ];

                // Получаем текущие данные пользователя для сохранения пароля
                $currentUser = $userController->getUser($userId);
                if ($currentUser && !empty($currentUser->Password)) {
                    $userUpdateData['Password'] = $currentUser->Password;
                }

                $user = new User($userUpdateData);
                $result = $userController->update($user);
                $results[] = $result;

                error_log("Обновлен пользователь ID: {$userId}, Статус: {$userUpdateData['Status']}, Активен: " . ($userUpdateData['isActive'] ? 'Да' : 'Нет'));
            } catch (Exception $e) {
                error_log("Ошибка при обновлении пользователя ID {$userId}: " . $e->getMessage());
                throw new Exception("Ошибка при обновлении пользователя ID {$userId}: " . $e->getMessage());
            }
        }

        return $results;
    }*/

    protected function prepareResultEntity($user)
    {
        return [
                'id' => $user->getId(),
                'Surname' => $user->Surname,
                'Name' => $user->Name,
                'Patronymic' => $user->Patronymic,
                'Status' => $user->Status,
                'isActive' => $user->isActive,
                'FIO' => $user->FIO
            ];
    }
}

// Использование
$handler = new processCUDUsers();
$handler->handleRequest();
