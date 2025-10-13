<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['IDUser'])) {
    header('Location: index.php');
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/home.log');
require_once __DIR__ . '/../../vendor/autoload.php';
//require __DIR__ . '/../Repositories/InventoryItemRepository.php';
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();


$confirmCount = 0;
$confirmRepairCount = 0;
$brigadesToItemsCount = 0;

$statusUser = $_SESSION["Status"];
//print_r(APP_PATH);
$container = new ItemController();
$inventoryItems = $container->getInventoryItems($_SESSION["Status"], $_SESSION["IDUser"]);

$brigades = $container->getBrigades($_SESSION["IDUser"]);

if ($inventoryItems != null)
    $totalItems = count($inventoryItems);
else
    $totalItems = 0;

// Перечень ТМЦ для подтверждения принятия кладовщику
$confirmItems = $container->getConfirmItems($_SESSION["Status"], $_SESSION["IDUser"]);


if ($confirmItems != null) {
    $confirmCount = count($confirmItems);
}


// Количество для подтверждения ремонта
$confirmRepairItems = $container->getConfirmRepairItems($_SESSION["Status"], $_SESSION["IDUser"]);
if ($confirmRepairItems != null) {
    $confirmRepairCount = count($confirmRepairItems);
    $locationRepairs = $container->getLocations(true);
}
// Перечень ТМЦ который находиться в бригаде
$brigadesToItems = $container->getBrigadesToItems($_SESSION["Status"], $_SESSION["IDUser"]);
if ($brigadesToItems != null) {
    $brigadesToItemsCount = count($brigadesToItems);
    $atWorkGroups = $container->getAtWorkItemsGrouped($_SESSION["Status"], $_SESSION["IDUser"]);
    //print_r($atWorkGroups);
}

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

    <!--script type="text/javascript" src="\..\..\app.js" async></script-->
    <script src="/src/constants/actions.js"></script>
    <script src="/src/constants/properties.js"></script>
    <script src="/src/constants/statusItem.js"></script>
    <script src="/src/constants/statusService.js"></script>
    <script src="/src/constants/typeMessage.js"></script>
    <script src="/js/modals/setting.js"></script>
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
    </style>

</head>

<body class="main-body">

    <div id="rightModal" class="right-modal">
        <div class="modal-content">
            <div class="modal-Body" id="modalBody">
            </div>
        </div>
    </div>

    <?php
    //<!-- Модальное окно сообщений -->    
    include __DIR__ . '/Modal/message_modal.php';
    ?>

    <?php if ($_SESSION["Status"] == 0): ?>
        <div class="modal fade" id="adminPanelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Панель управления</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php include_once __DIR__ . '/admin_panel.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Всплывающее уведомление для ConfirmItem -->
    <?php if ($confirmCount > 0): ?>
        <div class="notification-alert" id="confirmNotification" data-bs-toggle="modal" data-bs-target="#confirmModal">
            Принять <?= $confirmCount ?> ТМЦ
        </div>
        <div class="notification-badge" id="confirmBadge" data-bs-toggle="modal" data-bs-target="#confirmModal">
            <?= $confirmCount ?>
        </div>
    <?php endif; ?>

    <!-- Всплывающее уведомление для ConfirmRepairTMC -->
    <?php if ($confirmRepairCount > 0): ?>
        <div class="notification-alert notification-repair-alert" id="confirmRepairNotification" data-bs-toggle="modal"
            data-bs-target="#confirmRepairModal">
            Подтвердить ремонт <?= $confirmRepairCount ?> ТМЦ
        </div>
        <div class="notification-badge notification-repair-badge" id="confirmRepairBadge" data-bs-toggle="modal"
            data-bs-target="#confirmRepairModal">
            <?= $confirmRepairCount ?>
        </div>
    <?php endif; ?>

    <!-- Всплывающее уведомление для AtWorkTMC -->
    <?php if ($brigadesToItemsCount > 0): ?>
        <div class="notification-alert notification-atwork-alert" id="atWorkNotification" data-bs-toggle="modal"
            data-bs-target="#atWorkModal">
            Выдано в работу <span id="atWorkCount"> <?= $brigadesToItemsCount ?> </span> ТМЦ
        </div>
        <div class="notification-badge notification-atwork-badge" id="atWorkBadge" data-bs-toggle="modal"
            data-bs-target="#atWorkModal">
            <?= $brigadesToItemsCount ?>
        </div>
    <?php endif; ?>

    <?php
    //include __DIR__.'/cardItem_modal.php';
    //<!-- Модальное окно для ТМЦ в работе (AtWorkTMC) -->    
    include __DIR__ . '/Modal/at_work_modal.php';
    //<!-- Модальное окно подтверждения ТМЦ (ConfirmItem) -->
    include __DIR__ . '/Modal/confirm_modal.php';
    //<!-- Модальное окно подтверждения ремонта (ConfirmRepairTMC) -->
    include __DIR__ . '/Modal/confirmRepair_modal.php';
    // Модально окно для передачи ТМЦ на другие объекты
    //include __DIR__ . '/Modal/distribute_modal.php';
    // Моадльное окно для передачи ТМЦ в бригаду
    // include __DIR__ . '/Modal/work_modal.php';
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
                    <a href="#" onclick="openCardTMC(Action.CREATE)" disabled="false">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z" />
                        </svg>
                        <span>Создать ТМЦ</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="openCardTMC(Action.CREATE_ANALOG)">
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
                <a href="#" onclick="openCardTMC(Action.EDIT)">
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

            <!--li>
                <a href="#" onclick="openModal(Action.RETURN_TMC)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M280-200v-80h284q63 0 109.5-40T720-420q0-60-46.5-100T564-560H312l104 104-56 56-200-200 200-200 56 56-104 104h252q97 0 166.5 63T800-420q0 94-69.5 157T564-200H280Z" />
                    </svg>
                    <span>Вернуть ТМЦ</span>
                </a>
            </li-->

            <li>
                <a href="#" onclick="sendToService('row-container', ServiceStatus.sendService)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M754-81q-8 0-15-2.5T726-92L522-296q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l85-85q6-6 13-8.5t15-2.5q8 0 15 2.5t13 8.5l204 204q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13l-85 85q-6 6-13 8.5T754-81Zm0-95 29-29-147-147-29 29 147 147ZM205-80q-8 0-15.5-3T176-92l-84-84q-6-6-9-13.5T80-205q0-8 3-15t9-13l212-212h85l34-34-165-165h-57L80-765l113-113 121 121v57l165 165 116-116-43-43 56-56H495l-28-28 142-142 28 28v113l56-56 142 142q17 17 26 38.5t9 45.5q0 24-9 46t-26 39l-85-85-56 56-42-42-207 207v84L233-92q-6 6-13 9t-15 3Zm0-96 170-170v-29h-29L176-205l29 29Zm0 0-29-29 15 14 14 15Zm549 0 29-29-29 29Z" />
                    </svg>
                    <span>Отправить в сервис</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="sendToService('row-container', ServiceStatus.returnService)">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#1f1f1f">
                        <path
                            d="M720-160v-120H600v-80h120v-120h80v120h120v80H800v120h-80Zm-600 40q-33 0-56.5-23.5T40-200v-560q0-33 23.5-56.5T120-840h560q33 0 56.5 23.5T760-760v200h-80v-80H120v440h520v80H120Zm0-600h560v-40H120v40Zm0 0v-40 40Z" />
                    </svg>
                    <span>Вернуть из сервиса</span>
                </a>
            </li>

            <!--li>
                    <button onclick=toggleSubMenu(this) class="dropdown-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z" />
                        </svg>
                        <span>Изменить настройки</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z" />
                        </svg>
                    </button>
                    <ul class="sub-menu">
                        <div>
                            <li><a href="#">Подключение к серверу</a></li>
                            <li><a href="#">Параметры доступа</a></li>
                            <li><a href="#">Список пользователей</a></li>
                        </div>
                    </ul>
                </li-->

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
                    <a href="#" onclick="openAdminPanel()">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#1f1f1f">
                            <path
                                d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z" />
                        </svg>
                        <span>Профиль пользователя</span>
                    </a>
                </li>
            <?php endif ?>

            <li class="active">
                <a href="index.php">
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
                            <th>Сер. номер</th>
                            <th>Бренд</th>
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
                                <td class="rowGrid1"><?= $inventoryItem->User->FIO ?></td>
                                <td class="rowGrid1"><?= $inventoryItem->Location->NameLocation ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <div class="container" id="resultContainer" style="grid-area: container2">
            </div>
            <div class="status-bar" style="grid-area: container3">
                <span id="row-counter">
                    Кол-во строк: <?= count($inventoryItems) ?> из <?= $totalItems ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            //console.log("Событие DOMContentLoaded вызвано!"); // Проверка срабатывания события
            setupModalHandlers();
        });

        
        // функция для обновления таблицы без перезагрузки
        async function refreshTableAndNavigate(newItemId) {
            showGlobalLoader("Обновление таблицы...");

            try {
                // Отправляем запрос к новому PHP-скрипту
                const response = await fetch("/src/BusinessLogic/getUpdatedTable.php");

                if (!response.ok) {
                    throw new Error(`Ошибка сервера: ${response.status}`);
                }

                const html = await response.text();

                // Заменяем содержимое тела таблицы
                const tableBody = document.querySelector("#inventoryTable tbody");
                if (tableBody) {
                    tableBody.innerHTML = html;

                    // Восстанавливаем обработчики событий для новых строк
                    reattachTableEventHandlers();

                    // Навигация к новому элементу
                    navigateToNewItemAJAX(newItemId);
                }
            } catch (error) {
                console.error("Ошибка при обновлении таблицы:", error);
                // Fallback: обычная перезагрузка в случае ошибки
                window.needFullReload = true;
                handleSuccess();
            } finally {
                hideGlobalLoader();
            }
        }

        // Функция для восстановления обработчиков событий
        function reattachTableEventHandlers() {
            const rows = document.querySelectorAll(".row-container");

            // Восстанавливаем обработчики клика для выделения строк
            rows.forEach((row) => {
                row.addEventListener("click", function (e) {
                    e.preventDefault();

                    // Копируем логику из home.php для выделения строк
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

                    // Если у вас есть другие обработчики для строк, добавьте их здесь
                    // handleAction(event); // Раскомментируйте, если нужно
                });
            });

            // Восстанавливаем обработчики для кнопок фильтрации, если они есть
            // initFilters(); // Раскомментируйте, если используете фильтры

            console.log(`Обработчики событий восстановлены для ${rows.length} строк`);
        }


        /* ================================================================================*/

        // Обработчик клика по строке
        let lastSelectedRow = null;
        document.querySelectorAll(".row-container").forEach((row) => {
            row.addEventListener("click", function (e) {
                e.preventDefault();

                // Если нажат Ctrl - добавляем/удаляем выделение текущей строки
                if (e.ctrlKey) {
                    this.classList.toggle("selected");
                    lastSelectedRow = this;
                    //return;
                }

                // Если нажат Shift и есть последняя выделенная строка
                if (e.shiftKey && lastSelectedRow) {
                    const rows = Array.from(document.querySelectorAll(".row-container"));
                    const startIndex = rows.indexOf(lastSelectedRow);
                    const endIndex = rows.indexOf(this);

                    // Определяем диапазон для выделения
                    const [start, end] = startIndex < endIndex ? [startIndex, endIndex] : [endIndex, startIndex];

                    // Снимаем выделение со всех строк
                    removingSelection();
                    /* document.querySelectorAll(".row-container").forEach(r =>
                         r.classList.remove("selected"));*/

                    // Выделяем строки в диапазоне
                    for (let i = start; i <= end; i++) {
                        rows[i].classList.add("selected");
                    }

                } else {
                    // Обычный клик без модификаторов
                    // Снимаем выделение со всех строк
                    /* document.querySelectorAll(".row-container").forEach(r =>
                         r.classList.remove("selected"));*/
                    removingSelection();

                    // Выделяем текущую строку
                    this.classList.add("selected");
                    lastSelectedRow = this;
                }

                // Получаем ID всех выделенных строк
                /*const selectedIds = Array.from(document.querySelectorAll(".row-container.selected"))
                    .map(row => row.getAttribute("data-id"));*/
                //console.log("Выделенные строки с ИД:", selectedIds);
            });
        });

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


        // Закрытие модального окна при клике вне его области
        document.addEventListener('click', function (event) {
            const modal = document.getElementById('adminPanelModal');
            if (modal && event.target === modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
            }
        });


        document.addEventListener('DOMContentLoaded', function () {

            navigateToNewItem();
            measureReloadTime();



            //console.log('Filter buttons found:', document.querySelectorAll('.filter-btn').length);
            /*  document.querySelectorAll('.filter-btn').forEach(btn => {
                  console.log('Button:', btn, 'Parent:', btn.closest('#cont1'));
              });*/
            // Состояние фильтров
            const filters = {};
            let currentDropdown = null;

            const totalRows = <?= $totalItems ?>;
            let visibleRows = <?= count($inventoryItems) ?>;
            const rowCounter = document.getElementById('row-counter');

            // Обновление счетчика строк
            function updateRowCounter(visible) {
                rowCounter.textContent = `Кол-во строк: ${visible} из ${visibleRows}`;
            }

            // Собираем уникальные значения для каждого столбца
            function getColumnValues(columnIndex) {
                const values = new Set();
                //const rows = document.querySelectorAll('#inventoryTable tbody tr');
                const rows = document.querySelectorAll('#cont1 #inventoryTable tbody tr');
                rows.forEach(row => {
                    const cell = row.cells[columnIndex];
                    if (cell) {
                        values.add(cell.textContent.trim());
                    }
                });

                return Array.from(values).sort();
            }

            // Создаем выпадающий фильтр для столбца
            function createFilterDropdown(columnIndex) {
                const dropdown = document.createElement('div');
                dropdown.className = 'filter-dropdown-content';
                //dropdown.className = 'dropdown-filter';
                dropdown.style.zIndex = "100";


                // Поле поиска
                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'search-input';
                searchInput.placeholder = 'Поиск...';
                dropdown.appendChild(searchInput);

                // Список значений
                const filterList = document.createElement('div');
                filterList.className = 'filter-list';
                dropdown.appendChild(filterList);

                // Кнопки
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

                // Заполняем список значений
                function populateList(values, filterText = '') {
                    filterList.innerHTML = '';

                    // "Выбрать все"
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

                    // Обработчик для "Выбрать все"
                    selectAllCheckbox.addEventListener('change', function () {
                        const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });

                    // Значения столбца
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

                        // Проверяем, выбрано ли это значение в фильтре
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

                    // Обновление состояния "Выбрать все"
                    updateSelectAllState();
                }

                // Обновление состояния "Выбрать все"
                function updateSelectAllState() {
                    const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                    const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
                    const selectAll = document.getElementById(`select-all-${columnIndex}`);
                    if (selectAll) {
                        selectAll.checked = allChecked;
                    }
                }

                // Получаем начальные значения
                const columnValues = getColumnValues(columnIndex);
                populateList(columnValues);

                // Поиск
                searchInput.addEventListener('input', function () {
                    populateList(columnValues, this.value);
                });

                // Применение фильтра
                applyBtn.addEventListener('click', function () {
                    const checkboxes = filterList.querySelectorAll('input[type="checkbox"]:not(#select-all-' + columnIndex + ')');
                    const selectedValues = [];

                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            selectedValues.push(checkbox.value);
                        }
                    });

                    // Сохраняем фильтр
                    filters[columnIndex] = selectedValues;

                    // Применяем фильтр
                    applyFilters();

                    // Закрываем выпадающее окно
                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);
                    dropdownContainer.classList.remove('show');
                    currentDropdown = null;
                });

                // Отмена
                cancelBtn.addEventListener('click', function () {
                    // Закрываем выпадающее окно
                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);
                    dropdownContainer.classList.remove('show');
                    currentDropdown = null;
                });

                return dropdown;
            }

            // Применяем все фильтры к таблице
            function applyFilters() {
                const rows = document.querySelectorAll('#inventoryTable tbody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    let visible = true;

                    // Проверяем все столбцы с фильтрами
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
                // Обновление счетчика
                updateRowCounter(visibleCount);
            }

            // Обработчики для кнопок фильтра
            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function (e) {

                    //console.log("ЗАпуск обрабтки фильтра");


                    e.stopPropagation();
                    const columnIndex = this.getAttribute('data-column');
                    const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);

                    // Закрываем предыдущее выпадающее окно
                    if (currentDropdown && currentDropdown !== dropdownContainer) {
                        currentDropdown.classList.remove('show');
                        currentDropdown.innerHTML = '';
                    }

                    // Переключаем текущее выпадающее окно
                    if (dropdownContainer.classList.contains('show')) {
                        dropdownContainer.classList.remove('show');
                        dropdownContainer.innerHTML = '';
                        currentDropdown = null;
                    } else {
                        // Создаем содержимое выпадающего окна
                        dropdownContainer.innerHTML = '';
                        const dropdownContent = createFilterDropdown(columnIndex);
                        dropdownContainer.appendChild(dropdownContent);
                        dropdownContainer.classList.add('show');
                        currentDropdown = dropdownContainer;
                    }
                    // Сохраняем текущее значение overflow и меняем на visible
                    const cont1 = document.getElementById('cont1');
                    cont1.dataset.originalOverflow = cont1.style.overflow;
                    cont1.style.overflow = 'visible';
                });
            });

            // Закрытие выпадающего окна при клике вне его
            document.addEventListener('click', function (e) {
                if (currentDropdown && !currentDropdown.contains(e.target)) {
                    currentDropdown.classList.remove('show');
                    currentDropdown.innerHTML = '';
                    currentDropdown = null;
                }
            });
        });

        //window.needFullReload = needFullReload;
        // Функция для навигации к новому элементу
        function navigateToNewItem() {
            const newItemId = sessionStorage.getItem('newItemId');
            const scrollToNewItem = sessionStorage.getItem('scrollToNewItem');

            if (newItemId && scrollToNewItem === 'true') {
                // Очищаем sessionStorage
                sessionStorage.removeItem('newItemId');
                sessionStorage.removeItem('scrollToNewItem');

                // Ждем полной загрузки DOM и отрисовки таблицы
                setTimeout(() => {
                    const newItemRow = document.querySelector(`tr.row-container[data-id="${newItemId}"]`);

                    if (newItemRow) {
                        // Снимаем выделение со всех строк
                        removingSelection();

                        // Выделяем новую строку
                        newItemRow.classList.add('selected');

                        // Прокручиваем к элементу
                        newItemRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'nearest'
                        });

                        // Добавляем временную анимацию выделения
                        newItemRow.style.transition = 'all 0.5s ease';
                        newItemRow.style.backgroundColor = '#e3f2fd';

                        setTimeout(() => {
                            newItemRow.style.backgroundColor = '';
                        }, 2000);

                        // Показываем уведомление о успешном создании
                        showNotification(TypeMessage.success, `Новый элемент создан и выделен (ID: ${newItemId})`);
                    } else {
                        // Если элемент не найден (фильтрация и т.д.)
                        showNotification(TypeMessage.warning, 'Новый элемент создан, но не отображается в текущем виде. Сбросьте фильтры для просмотра.');
                    }
                }, 500); // Увеличиваем задержку для полной загрузки таблицы
            }
        }

        function navigateToNewItemAJAX(newItemId) {
            // Небольшая задержка для гарантии отрисовки DOM
            setTimeout(() => {
                const newItemRow = document.querySelector(`tr.row-container[data-id="${newItemId}"]`);

                if (newItemRow) {
                    removingSelection();
                    newItemRow.classList.add('selected');
                    newItemRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Анимация выделения
                    newItemRow.style.transition = 'all 0.5s ease';
                    newItemRow.style.backgroundColor = '#e3f2fd';

                    setTimeout(() => {
                        newItemRow.style.backgroundColor = '';
                    }, 2000);

                    showNotification(TypeMessage.success, `Новый элемент создан и выделен (ID: ${newItemId})`);
                } else {
                    // Если элемент не найден (возможно, из-за активных фильтров)
                    showNotification(TypeMessage.warning, 'Новый элемент создан, но не отображается в текущем виде. Сбросьте фильтры для просмотра.');
                }
            }, 100);
        }
    </script>

    <script src="/js/updateFunctions.js"></script>
    <script src="/js/modals/cardItemModal.js"></script>
    <script src="/js/modals/modalLoader.js"></script>
    <script src="/js/modals/distributeModal.js"></script>
    <script src="/js/modals/workModal.js"></script>
    <script src="/js/modals/confirmModal.js"></script>
    <script src="/js/modals/confirmRepairModal.js"></script>
    <script src="/js/modals/serviceModal.js"></script>
    <script src="/js/writeOffFunctions.js"></script>

    <div id="modalContainer"></div>

</body>

</html>