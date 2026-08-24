<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['IDUser'])) {
    http_response_code(403); // Запрещено
    exit('Доступ запрещен');
}

// Подключите те же файлы, что и в home.php
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php'; 
DatabaseFactory::setConfig();

// Создаем экземпляр контроллера и получаем обновленный список ТМЦ
$container = new ItemController();
$inventoryItems = $container->getInventoryItems($_SESSION["Status"], $_SESSION["IDUser"]);

// Генерируем и возвращаем HTML-строки таблицы
foreach ($inventoryItems as $inventoryItem) {
    echo '<tr class="row-container ' . ((new StatusItem())->getStatusClasses($inventoryItem->Status)) . '"';
    echo ' onclick="handleAction(event)"';
    echo ' data-id="' . $inventoryItem->ID_TMC . '"';
    echo ' data-status="' . $inventoryItem->Status . '">';
    echo '<td class="rowGrid1">' . $inventoryItem->ID_TMC . '</td>';
    echo '<td class="rowGrid1">' . $inventoryItem->NameTMC . '</td>';
    echo '<td class="rowGrid1">' . $inventoryItem->SerialNumber . '</td>';
    echo '<td class="rowGrid1">' . $inventoryItem->BrandTMC->NameBrand . '</td>';
    echo '<td class="rowGrid1">' . (new StatusItem())->getDescription($inventoryItem->Status) . '</td>';
    echo '<td class="rowGrid1">' . $inventoryItem->User->FIO . '</td>';
    echo '<td class="rowGrid1">' . $inventoryItem->Location->NameLocation . '</td>';
    echo '</tr>';
}
?>