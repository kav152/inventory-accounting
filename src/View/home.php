<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['IDUser'])) {
    header('Location: index.php');
    exit();
}

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/home.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();

$confirmCount = 0;
$confirmRepairCount = 0;
$brigadesToItemsCount = 0;

$statusUser = $_SESSION["Status"];
$container = new ItemController();

$startTime = microtime(true);
$inventoryItems = $container->getInventoryItems($_SESSION["Status"], $_SESSION["IDUser"]);
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время загрузки inventoryItems: " . $loadTime . " секунд. Загружено объектов: " . ($inventoryItems ? count($inventoryItems) : 0));

$startTime = microtime(true);
$brigades = $container->getBrigades($_SESSION["IDUser"]);
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время brigades элементов: " . $loadTime . " секунд.");

$startTime = microtime(true);
if ($inventoryItems != null)
    $totalItems = count($inventoryItems);
else
    $totalItems = 0;
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время прочета totalItems элементов: " . $loadTime . " секунд.");


$startTime = microtime(true);
$confirmItems = $container->getConfirmItems($_SESSION["Status"], $_SESSION["IDUser"]);
if ($confirmItems != null) {
    $confirmCount = count($confirmItems);
}
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время confirmItems элементов: " . $loadTime . " секунд.");

$startTime = microtime(true);
$confirmRepairItems = $container->getConfirmRepairItems($_SESSION["Status"], $_SESSION["IDUser"]);
if ($confirmRepairItems != null) {
    $confirmRepairCount = count($confirmRepairItems);
    $locationRepairs = $container->getLocations(true);
}
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время загрузки confirmRepairItems: " . $loadTime . " секунд.");

$startTime = microtime(true);
$brigadesToItems = $container->getBrigadesToItems($_SESSION["Status"], $_SESSION["IDUser"]);
if ($brigadesToItems != null) {
    $brigadesToItemsCount = count($brigadesToItems);
    $atWorkGroups = $container->getAtWorkItemsGrouped($_SESSION["Status"], $_SESSION["IDUser"]);
}
$endTime = microtime(true);
$loadTime = $endTime - $startTime;
//error_log("Время brigadesToItems: " . $loadTime . " секунд.");

// Общее количество уведомлений
$totalNotifications = $confirmCount + $confirmRepairCount + $brigadesToItemsCount;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главное окно. <?= $_SESSION["FIO"] ?> </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="\..\..\styles\filterStyle.css" rel="stylesheet">
    <link href="\..\..\styles\homeStyle.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <script type="module" src="/src/constants/properties.js"></script>
    <script type="module" src="/src/constants/statusItem.js"></script>
    <script type="module" src="/src/constants/statusService.js"></script>
    <script type="module" src="/src/constants/actions.js"></script>
    <script type="module" src="/src/constants/typeMessage.js"></script>
    <script type="module" src="/js/modals/setting.js"></script>
    <script src="/app.js" async></script>


    <style>
        /* Стили для глобального индикатора загрузки */
        .global-loader-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9998;
        }

        .global-loader-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            text-align: center;
            min-width: 200px;
        }

        #global-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9997;
        }

        /* Стили для панели уведомлений */
        .notifications-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            z-index: 1000;
            min-width: 300px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .notifications-panel.collapsed {
            height: 50px;
            overflow: hidden;
        }

        .notifications-panel.expanded {
            max-height: 400px;
            overflow-y: auto;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .panel-title {
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin: 0;
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .notifications-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Сохраняем оригинальные стили уведомлений */
        .notification-alert {
            position: relative;
            transform: none;
            top: auto;
            left: auto;
            right: auto;
            bottom: auto;
            margin: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .notification-badge {
            position: relative;
            transform: none;
            top: auto;
            left: auto;
            right: auto;
            bottom: auto;
            margin: 0;
        }

        .notification-repair-alert,
        .notification-atwork-alert,
        .notification-repair-badge,
        .notification-atwork-badge {
            position: relative;
            transform: none;
            top: auto;
            left: auto;
            right: auto;
            bottom: auto;
            margin: 0;
            width: 100%;
            box-sizing: border-box;
        }
    </style>

</head>

<body class="main-body">

    <div id="rightModal" class="right-modal">
        <div class="modal-content">
            <div class="modal-Body" id="modalBody">
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/Modal/message_modal.php'; ?>


    <!-- Панель уведомлений -->
    <?php if ($totalNotifications > 0): ?>
        <div class="notifications-panel expanded" id="notificationsPanel">
            <div class="panel-header">
                <div class="panel-title-container">
                    <h3 class="panel-title">Уведомления</h3>
                    <div class="header-badges">
                        <?php if ($confirmCount > 0): ?>
                            <div class="notification-badge header-badge" id="confirmBadge" data-bs-toggle="modal"
                                data-bs-target="#confirmModal">
                                <?= $confirmCount ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($_SESSION["Status"] == 0): ?>
                            <?php if ($confirmRepairCount > 0): ?>
                                <div class="notification-badge notification-repair-badge header-badge" id="confirmRepairBadge"
                                    data-bs-toggle="modal" data-bs-target="#confirmRepairModal">
                                    <?= $confirmRepairCount ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($brigadesToItemsCount > 0): ?>
                            <div class="notification-badge notification-atwork-badge header-badge" id="atWorkBadge"
                                data-bs-toggle="modal" data-bs-target="#atWorkModal">
                                <?= $brigadesToItemsCount ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="toggle-btn" id="togglePanelBtn">Свернуть</button>
            </div>
            <div class="notifications-container">
                <!-- Всплывающее уведомление для ConfirmItem -->
                <?php if ($confirmCount > 0): ?>
                    <div class="notification-alert" id="confirmNotification" data-bs-toggle="modal"
                        data-bs-target="#confirmModal">
                        Принять <?= $confirmCount ?> ТМЦ
                    </div>
                <?php endif; ?>

                <?php if ($_SESSION["Status"] == 0): ?>
                    <!-- Всплывающее уведомление для ConfirmRepairTMC -->
                    <?php if ($confirmRepairCount > 0): ?>
                        <div class="notification-alert notification-repair-alert" id="confirmRepairNotification"
                            data-bs-toggle="modal" data-bs-target="#confirmRepairModal">
                            Подтвердить ремонт <?= $confirmRepairCount ?> ТМЦ
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Всплывающее уведомление для AtWorkTMC -->
                <?php if ($brigadesToItemsCount > 0): ?>
                    <div class="notification-alert notification-atwork-alert" id="atWorkNotification" data-bs-toggle="modal"
                        data-bs-target="#atWorkModal">
                        Выдано в работу <span id="atWorkCount"> <?= $brigadesToItemsCount ?> </span> ТМЦ
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    include __DIR__ . '/Modal/at_work_modal.php';
    include __DIR__ . '/Modal/confirm_modal.php';
    include __DIR__ . '/Modal/confirmRepair_modal.php';
    ?>

    <nav id="sidebar">
        <ul>
            <li>
                <span class="logo">Учет ТМЦ</span>
                <button id="toggle-btn" onclick=toggleSidebar()>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z" />
                    </svg>
                </button>
            </li>
            <?php if ($_SESSION["Status"] == 0): ?>
                <li>
                    <a href="#" onclick="openEntityModal(Action.CREATE, 'cardItemModal')" disabled="false">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z" />
                        </svg>
                        <span>Создать ТМЦ</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="openEntityModal(Action.CREATE_ANALOG, 'cardItemModal')">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z" />
                        </svg>
                        <span>Создать ТМЦ по аналогии</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="#" onclick="openEntityModal(Action.UPDATE, 'cardItemModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                    </svg>
                    <span>Редактировать ТМЦ</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="openDistributeModal(<?= $statusUser ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="m720-120-56-57 63-63H480v-80h247l-63-64 56-56 160 160-160 160Zm120-400h-80v-240h-80v120H280v-120h-80v560h200v80H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h167q11-35 43-57.5t70-22.5q40 0 71.5 22.5T594-840h166q33 0 56.5 23.5T840-760v240ZM480-760q17 0 28.5-11.5T520-800q0-17-11.5-28.5T480-840q-17 0-28.5 11.5T440-800q0 17 11.5 28.5T480-760Z" />
                    </svg>
                    <span>Передать ТМЦ</span>
                </a>
            </li>

            <li>
                <a href="#" onclick="openWorkModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M400-120q-17 0-28.5-11.5T360-160v-480H160q0-83 58.5-141.5T360-840h240v120l120-120h80v320h-80L600-640v480q0 17-11.5 28.5T560-120H400Zm40-80h80v-240h-80v240Zm0-320h80v-240H360q-26 0-49 10.5T271-720h169v200Zm40 40Z" />
                    </svg>
                    <span>В работу ТМЦ</span>
                </a>
            </li>

            <li>
                <a href="#" onclick="sendToService('row-container', 0)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M754-81q-8 0-15-2.5T726-92L522-296q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l85-85q6-6 13-8.5t15-2.5q8 0 15 2.5t13 8.5l204 204q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13l-85 85q-6 6-13 8.5T754-81Zm0-95 29-29-147-147-29 29 147 147ZM205-80q-8 0-15.5-3T176-92l-84-84q-6-6-9-13.5T80-205q0-8 3-15t9-13l212-212h85l34-34-165-165h-57L80-765l113-113 121 121v57l165 165 116-116-43-43 56-56H495l-28-28 142-142 28 28v113l56-56 142 142q17 17 26 38.5t9 45.5q0 24-9 46t-26 39l-85-85-56 56-42-42-207 207v84L233-92q-6 6-13 9t-15 3Zm0-96 170-170v-29h-29L176-205l29 29Zm0 0-29-29 15 14 14 15Zm549 0 29-29-29 29Z" />
                    </svg>
                    <span>Отправить в сервис</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="sendToService('row-container', 1)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M720-160v-120H600v-80h120v-120h80v120h120v80H800v120h-80Zm-600 40q-33 0-56.5-23.5T40-200v-560q0-33 23.5-56.5T120-840h560q33 0 56.5 23.5T760-760v200h-80v-80H120v440h520v80H120Zm0-600h560v-40H120v40Zm0 0v-40 40Z" />
                    </svg>
                    <span>Вернуть из сервиса</span>
                </a>
            </li>

            <?php if ($_SESSION["Status"] == 0): ?>
                <li>
                    <a href="/src/View/analytics.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z" />
                        </svg>
                        <span>Аналитика</span>
                    </a>
                </li>
                <li>
                    <a href="/src/View/write_off.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z" />
                        </svg>
                        <span>Списание/затраты на ремонт</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($_SESSION["Status"] == 0): ?>
                <li>
                    <a href="/src/View/adminPanel.php">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z" />
                        </svg>
                        <span>Администрирование</span>
                    </a>
                </li>
            <?php endif ?>

            <li class="active">
                <a href="/../../index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z" />
                    </svg>
                    <span>Выход</span>
                </a>
            </li>
        </ul>
    </nav>


    <div id="main">
        <div class="grid-container">
            <div class="container" id="cont1" style="grid-area: container1">
                <table id="inventoryTable">
                    <thead>
                        <tr class="header-container">
                            <th>Ид.
                                <button class="filter-btn" data-column="0">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-0"></div>
                            </th>
                            <th>Наименование
                                <button class="filter-btn" data-column="1">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-1"></div>
                            </th>
                            <th>Сер. номер
                                <button class="filter-btn" data-column="2">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-2"></div>
                            </th>
                            <th>Бренд
                                <button class="filter-btn" data-column="3">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-3"></div>
                            </th>
                            <th>Статус
                                <button class="filter-btn" data-column="4">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-4"></div>
                            </th>
                            <th>Ответств.
                                <button class="filter-btn" data-column="5">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-5"></div>
                            </th>
                            <th>Локация
                                <button class="filter-btn" data-column="6">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960"
                                        width="18">
                                        <path
                                            d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" />
                                    </svg>
                                </button>
                                <div class="dropdown-filter" id="dropdown-6"></div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="scroll">
                        <?php if ($inventoryItems && count($inventoryItems) > 0): ?>
                            <?php foreach ($inventoryItems as $inventoryItem): ?>
                                <tr class="row-container <?= ((new StatusItem())->getStatusClasses($inventoryItem->Status)) ?? '' ?>"
                                    onclick="handleAction(event)" data-id="<?= $inventoryItem->ID_TMC ?>"
                                    data-status="<?= $inventoryItem->Status ?>">
                                    <td class="rowGrid1"><?= $inventoryItem->ID_TMC ?></td>
                                    <td class="rowGrid1"><?= $inventoryItem->NameTMC ?></td>
                                    <td class="rowGrid1"><?= $inventoryItem->SerialNumber ?></td>
                                    <td class="rowGrid1"><?= $inventoryItem->BrandTMC->NameBrand ?></td>
                                    <td class="rowGrid1"><?= (new StatusItem())->getDescription($inventoryItem->Status) ?>
                                    </td>
                                    <td class="rowGrid1"><?= $inventoryItem->User->FIO ?? '' ?></td>
                                    <td class="rowGrid1"><?= $inventoryItem->Location->NameLocation ?></td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Нет данных для отображения</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="container" id="resultContainer" style="grid-area: container2">
            </div>

            <div class="status-bar" style="grid-area: container3">
                <span id="row-counter">
                    Кол-во строк: <?= ($inventoryItems ? count($inventoryItems) : 0) ?> из <?= $totalItems ?>
                </span>
            </div>
        </div>
    </div>



    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        //import {rowSelectionManager } from '../../js/templates/cudRowsInTable.js';
        // Инициализация выделения строк для пользователей
        document.addEventListener('DOMContentLoaded', function () {
            if (window.rowSelectionManager) {
                window.rowSelectionManager.initializeTable('inventoryTable', 'row-container');
            }
        });        
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setupModalHandlers();
            setupNotificationsPanel();
        });

        // Настройка панели уведомлений
        function setupNotificationsPanel() {
            const panel = document.getElementById('notificationsPanel');
            const toggleBtn = document.getElementById('togglePanelBtn');

            if (!panel || !toggleBtn) return;

            // По умолчанию панель раскрыта
            panel.classList.add('expanded');
            toggleBtn.textContent = 'Свернуть';

            toggleBtn.addEventListener('click', function () {
                if (panel.classList.contains('expanded')) {
                    panel.classList.remove('expanded');
                    panel.classList.add('collapsed');
                    toggleBtn.textContent = 'Развернуть';
                } else {
                    panel.classList.remove('collapsed');
                    panel.classList.add('expanded');
                    toggleBtn.textContent = 'Свернуть';
                }
            });
        }

        // функция для обновления таблицы без перезагрузки
       /* async function refreshTableAndNavigate(newItemId) {
            showGlobalLoader("Обновление таблицы...");

            try {
                const response = await fetch("/src/BusinessLogic/getUpdatedTable.php");

                if (!response.ok) {
                    throw new Error(`Ошибка сервера: ${response.status}`);
                }

                const html = await response.text();

                const tableBody = document.querySelector("#inventoryTable tbody");
                if (tableBody) {
                    tableBody.innerHTML = html;
                    reattachTableEventHandlers();
                    navigateToNewItemAJAX(newItemId);
                }
            } catch (error) {
                console.error("Ошибка при обновлении таблицы:", error);
                window.needFullReload = true;
                handleSuccess();
            } finally {
                hideGlobalLoader();
            }
        }*/

        // Функция для восстановления обработчиков событий
      /*  function reattachTableEventHandlers() {
            const rows = document.querySelectorAll(".row-container");

            rows.forEach((row) => {
                row.addEventListener("click", function (e) {
                    e.preventDefault();

                    if (e.ctrlKey) {
                        this.classList.toggle("selected");
                        lastSelectedRow = this;
                        return;
                    }

                    if (e.shiftKey && lastSelectedRow) {
                        const allRows = Array.from(document.querySelectorAll(".row-container"));
                        const startIndex = allRows.indexOf(lastSelectedRow);
                        const endIndex = allRows.indexOf(this);
                        const [start, end] = startIndex < endIndex ? [startIndex, endIndex] : [endIndex, startIndex];

                        removingSelection();
                        for (let i = start; i <= end; i++) {
                            allRows[i].classList.add("selected");
                        }
                    } else {
                        removingSelection();
                        this.classList.add("selected");
                        lastSelectedRow = this;
                    }
                });
            });

            console.log(`Обработчики событий восстановлены для ${rows.length} строк`);
        }*/



        /* ================================================================================*/

        // Обработчик клика по строке
        /* let lastSelectedRow = null;
         document.querySelectorAll(".row-container").forEach((row) => {
             row.addEventListener("click", function(e) {
                 e.preventDefault();

                 if (e.ctrlKey) {
                     this.classList.toggle("selected");
                     lastSelectedRow = this;
                 }

                 if (e.shiftKey && lastSelectedRow) {
                     const rows = Array.from(document.querySelectorAll(".row-container"));
                     const startIndex = rows.indexOf(lastSelectedRow);
                     const endIndex = rows.indexOf(this);

                     const [start, end] = startIndex < endIndex ? [startIndex, endIndex] : [endIndex, startIndex];

                     removingSelection();
                     for (let i = start; i <= end; i++) {
                         rows[i].classList.add("selected");
                     }

                 } else {
                     removingSelection();
                     this.classList.add("selected");
                     lastSelectedRow = this;
                 }
             });
         });*/

        // Снимаем выделение со всех строк
        function removingSelection() {
            document.querySelectorAll(".row-container").forEach(r =>
                r.classList.remove("selected"));
        }
        window.removingSelection = removingSelection;

        function setupModalHandlers() {
            const confirmModal = document.getElementById('confirmModal');
            if (confirmModal) {
                confirmModal.addEventListener('hidden.bs.modal', function () {
                    handleSuccess();
                });
            }

            const confirmRepairModal = document.getElementById('confirmRepairModal');
            if (confirmRepairModal) {
                confirmRepairModal.addEventListener('hidden.bs.modal', function () {
                    handleSuccess();
                });
            }

            const atWorkModal = document.getElementById('atWorkModal');
            if (atWorkModal) {
                atWorkModal.addEventListener('hidden.bs.modal', function () {
                    handleSuccess();
                });
            }
        }

      /*  document.addEventListener('click', function (event) {
            const modal = document.getElementById('adminPanelModal');
            if (modal && event.target === modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
            }
        });*/

        document.addEventListener('DOMContentLoaded', function () {
            navigateToNewItem();
            measureReloadTime();

            const filters = {};
            let currentDropdown = null;

            const totalRows = <?= $totalItems ?>;
            let visibleRows = <?= ($inventoryItems ? count($inventoryItems) : 0) ?>;
            const rowCounter = document.getElementById('row-counter');

            function updateRowCounter(visible) {
                rowCounter.textContent = `Кол-во строк: ${visible} из ${visibleRows}`;
            }

            function getColumnValues(columnIndex) {
                const values = new Set();
                const rows = document.querySelectorAll('#cont1 #inventoryTable tbody tr');
                rows.forEach(row => {
                    const cell = row.cells[columnIndex];
                    if (cell) {
                        values.add(cell.textContent.trim());
                    }
                });

                return Array.from(values).sort();
            }

            function createFilterDropdown(columnIndex) {
                const dropdown = document.createElement('div');
                dropdown.className = 'filter-dropdown-content';
                dropdown.style.zIndex = "100";

                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'search-input';
                searchInput.placeholder = 'Поиск...';
                dropdown.appendChild(searchInput);

                const filterList = document.createElement('div');
                filterList.className = 'filter-list';
                dropdown.appendChild(filterList);

                const actions = document.createElement('div');
                actions.className = 'filter-actions';

                const applyBtn = document.createElement('button');
                applyBtn.className = 'filter-apply';
                applyBtn.textContent = 'Поиск';

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'filter-cancel';
                cancelBtn.textContent = 'Отмена';

                actions.appendChild(applyBtn);
                actions.appendChild(cancelBtn);
                dropdown.appendChild(actions);

                function populateList(values, filterText = '') {
                    filterList.innerHTML = '';

                    const selectAllItem = document.createElement('div');
                    selectAllItem.className = 'filter-item';

                    const selectAllCheckbox = document.createElement('input');
                    selectAllCheckbox.type = 'checkbox';
                    selectAllCheckbox.id = `select-all-${columnIndex}`;
                    selectAllCheckbox.checked = !filters[columnIndex] || filters[columnIndex].length === 0;

                    const selectAllLabel = document.createElement('label');
                    selectAllLabel.htmlFor = `select-all-${columnIndex}`;
                    selectAllLabel.textContent = 'Выбрать все';

                    selectAllItem.appendChild(selectAllCheckbox);
                    selectAllItem.appendChild(selectAllLabel);
                    filterList.appendChild(selectAllItem);

                    selectAllCheckbox.addEventListener('change', function () {
                        const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });

                    const filteredValues = values.filter(value =>
                        value.toLowerCase().includes(filterText.toLowerCase())
                    );

                    filteredValues.forEach(value => {
                        const item = document.createElement('div');
                        item.className = 'filter-item';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = value;
                        checkbox.id = `filter-${columnIndex}-${value}`;

                        if (filters[columnIndex] && filters[columnIndex].includes(value)) {
                            checkbox.checked = true;
                        } else if (!filters[columnIndex]) {
                            checkbox.checked = true;
                        }

                        const label = document.createElement('label');
                        label.htmlFor = `filter-${columnIndex}-${value}`;
                        label.textContent = value;

                        item.appendChild(checkbox);
                        item.appendChild(label);
                        filterList.appendChild(item);
                    });

                    updateSelectAllState();
                }

                function updateSelectAllState() {
                    const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                    const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
                    const selectAll = document.getElementById(`select-all-${columnIndex}`);
                    if (selectAll) {
                        selectAll.checked = allChecked;
                    }
                }

                const columnValues = getColumnValues(columnIndex);
                populateList(columnValues);

                searchInput.addEventListener('input', function () {
                    populateList(columnValues, this.value);
                });

                applyBtn.addEventListener('click', function () {
                    const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                    const selectedValues = [];

                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            selectedValues.push(checkbox.value);
                        }
                    });

                    filters[columnIndex] = selectedValues;
                    applyFilters();

                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);
                    dropdownContainer.classList.remove('show');
                    currentDropdown = null;
                });

                cancelBtn.addEventListener('click', function () {
                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);
                    dropdownContainer.classList.remove('show');
                    currentDropdown = null;
                });

                return dropdown;
            }

            function applyFilters() {
                const rows = document.querySelectorAll('#inventoryTable tbody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    let visible = true;

                    for (const columnIndex in filters) {
                        if (filters[columnIndex].length === 0) continue;

                        const cellValue = row.cells[columnIndex].textContent.trim();
                        if (!filters[columnIndex].includes(cellValue)) {
                            visible = false;
                            break;
                        }
                    }
                    row.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });
                updateRowCounter(visibleCount);
            }

            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const columnIndex = this.getAttribute('data-column');
                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);

                    if (currentDropdown && currentDropdown !== dropdownContainer) {
                        currentDropdown.classList.remove('show');
                        currentDropdown.innerHTML = '';
                    }

                    if (dropdownContainer.classList.contains('show')) {
                        dropdownContainer.classList.remove('show');
                        dropdownContainer.innerHTML = '';
                        currentDropdown = null;
                    } else {
                        dropdownContainer.innerHTML = '';
                        const dropdownContent = createFilterDropdown(columnIndex);
                        dropdownContainer.appendChild(dropdownContent);
                        dropdownContainer.classList.add('show');
                        currentDropdown = dropdownContainer;
                    }
                    const cont1 = document.getElementById('cont1');
                    cont1.dataset.originalOverflow = cont1.style.overflow;
                    cont1.style.overflow = 'visible';
                });
            });

            document.addEventListener('click', function (e) {
                if (currentDropdown && !currentDropdown.contains(e.target)) {
                    currentDropdown.classList.remove('show');
                    currentDropdown.innerHTML = '';
                    currentDropdown = null;
                }
            });
        });

        function navigateToNewItem() {
            const newItemId = sessionStorage.getItem('newItemId');
            const scrollToNewItem = sessionStorage.getItem('scrollToNewItem');

            if (newItemId && scrollToNewItem === 'true') {
                sessionStorage.removeItem('newItemId');
                sessionStorage.removeItem('scrollToNewItem');

                setTimeout(() => {
                    const newItemRow = document.querySelector(`tr.row-container[data-id="${newItemId}"]`);

                    if (newItemRow) {
                        removingSelection();
                        newItemRow.classList.add('selected');
                        newItemRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'nearest'
                        });

                        newItemRow.style.transition = 'all 0.5s ease';
                        newItemRow.style.backgroundColor = '#e3f2fd';

                        setTimeout(() => {
                            newItemRow.style.backgroundColor = '';
                        }, 2000);

                        showNotification(TypeMessage.success, `Новый элемент создан и выделен (ID: ${newItemId})`);
                    } else {
                        showNotification(TypeMessage.warning, 'Новый элемент создан, но не отображается в текущем виде. Сбросьте фильтры для просмотра.');
                    }
                }, 500);
            }
        }

        function navigateToNewItemAJAX(newItemId) {
            setTimeout(() => {
                const newItemRow = document.querySelector(`tr.row-container[data-id="${newItemId}"]`);

                if (newItemRow) {
                    removingSelection();
                    newItemRow.classList.add('selected');
                    newItemRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    newItemRow.style.transition = 'all 0.5s ease';
                    newItemRow.style.backgroundColor = '#e3f2fd';

                    setTimeout(() => {
                        newItemRow.style.backgroundColor = '';
                    }, 2000);

                    showNotification(TypeMessage.success, `Новый элемент создан и выделен (ID: ${newItemId})`);
                } else {
                    showNotification(TypeMessage.warning, 'Новый элемент создан, но не отображается в текущем виде. Сбросьте фильтры для просмотра.');
                }
            }, 100);
        }

    </script>
    
    <script type="module" src="/js/templates/expandableSection.js"></script>
    <script type="module" src="/js/templates/entityActionTemplate.js"></script>
    <script type="module" src="/js/modalTypes.js"></script>
    <script type="module" src="/js/updateFunctions.js"></script>
    <script type="module" src="/js/modals/modalLoader.js"></script>
    <script type="module" src="/js/modals/cardItemModal.js"></script>
    <script type="module" src="/js/modals/distributeModal.js"></script>
    <script type="module" src="/js/modals/workModal.js"></script>
    <script src="/js/modals/confirmModal.js"></script>
    <script src="/js/modals/confirmRepairModal.js"></script>
    <script type="module" src="/js/modals/serviceModal.js"></script>
    <script type="module" src="/js/writeOffFunctions.js"></script>

    <div id="modalContainer"></div>


</body>

</html>