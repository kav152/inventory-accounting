<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/login.log');
set_time_limit(0);
ini_set('memory_limit', '1024M');


require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/GenericRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../BusinessLogic/CsvImporter.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../BusinessLogic/AccessLogger.php';

$logAccess = new AccessLogger();
$logAccess->logPageAccess('login.php');

DatabaseFactory::setConfig();
$container = new Container();

$container->set(Database::class, function () {
    return DatabaseFactory::create();
});

$userRepository = $container->get(UserRepository::class);
$users = $userRepository->getAll();

$isAdmin = false;
$errorMessage = '';
$successMessage = '';
$router = new Routes();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['idUser'])) {
    $currentUser = $users->findBy('IDUser', (int) $_POST["idUser"]);
    //$currentUser = $userRepository->findById();

    if ($currentUser && $currentUser->isActive) {
        if ($currentUser->verifyPassword($_POST["password"])) {
            // Успешный вход            
            $logAccess->logLoginAttempt($currentUser->FIO, true, 'LOGIN_SUCCESS');

            session_start();
            session_regenerate_id();
            $_SESSION["IDUser"] = $currentUser->IDUser;
            $_SESSION["Status"] = $currentUser->Status;
            $_SESSION["FIO"] = $currentUser->FIO;
            $isAdmin = ($currentUser->Status == 0);

            $router->dispatch("/home");
            exit;
        } else {
            // Неверный пароль
            $logAccess->logLoginAttempt($currentUser->FIO, false, 'LOGIN_FAILED_WRONG_PASSWORD');
            $errorMessage = "Неверный пароль";
        }
    } else {
        $logAccess->logLoginAttempt($currentUser->FIO, false, 'User_Not_Found_Inactive');
        $errorMessage = "Пользователь не найден или деактивирован";
    }
}

// Выход из системы
if (isset($_GET['logout'])) {

    $logAccess->logLogout();
    $logAccess->log('USER_LOGOUT', [
        'user_id' => $_SESSION['IDUser'] ?? null,
        'user_name' => $_SESSION['FIO'] ?? null
    ]);
    session_unset();
    session_destroy();
    header("Location: index.php");
    //$router->dispatch("/home");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учёта ТМЦ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script type="module" src="/js/modals/setting.js"></script>
    <script type="module" src="/src/constants/typeMessage.js"></script>

    <style>
        /* Сохраняем только стили для формы входа */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            /*align-items: center;*/
            padding: 20px 0;
        }

        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-header {
            background: #2c3e50;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .auth-body {
            padding: 30px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .btn-primary {
            background: #3498db;
            border: none;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .welcome-container {
            text-align: center;
            padding: 40px 20px;
        }

        .profile-btn {
            font-size: 1.2rem;
            padding: 12px 30px;
        }
    </style>
</head>

<body>

    <?php
    include __DIR__ . '/Modal/message_modal.php';
    ?>

    <div class="auth-container">
        <!-- Шапка системы -->
        <div class="auth-header">
            <h1><i class="bi bi-building"></i> Система учёта ТМЦ</h1>
            <?php if (isset($_SESSION['FIO'])): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>Вы вошли как: <strong><?= htmlspecialchars($_SESSION['FIO']) ?></strong></div>
                    <a href="?logout=1" class="btn btn-sm btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Выйти
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Основной контент -->
        <div class="auth-body">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h2 class="text-center mb-4">Авторизация</h2>
                    <form method="post">
                        <div class="mb-3">
                            <label for="idUser" class="form-label">Пользователь</label>
                            <select class="form-select" name="idUser" id="idUser" required>
                                <option value="">Выберите пользователя</option>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user->isActive): ?>
                                        <option value="<?= $user->IDUser ?>">
                                            <?= htmlspecialchars($user->FIO) ?>
                                            <?= $user->Status == 0 ? ' (Админ)' : '' ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Войти в систему
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--div class="text-center mt-4">
        <button class="btn btn-sm btn-secondary" onclick="testNotifications()">
            Тест уведомлений
        </button>
    </div-->


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Закрытие модального окна при клике вне его области
        document.addEventListener('click', function (event) {
            const modal = document.getElementById('adminPanelModal');
            if (modal && event.target === modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
            }
        });

        function testNotifications() {
            showNotification(TypeMessage.error, 'Это тестовое сообщение об ошибке');
            setTimeout(() => {
                showNotification(TypeMessage.success, 'Это тестовое сообщение об успехе');
            }, 1000);

            setTimeout(() => {
                showNotification(TypeMessage.notification, 'Это тестовое сообщение об уведомлении');
            }, 2000);
        }
    </script>
</body>

</html>