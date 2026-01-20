<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/cardItem.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../BusinessLogic/HistoryOperationsController.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {

    $startTime = microtime(true);

    $currentID = $_GET['id'] ?? null;
    if (!$currentID) {
        // Если ID не передан, показываем пустую карточку
        echo '<div class="alert alert-info">Выберите элемент из списка</div>';
        exit;
    }
    $currentID = (int) $currentID;
    DatabaseFactory::setConfig();
    $propertyController = new PropertyController();
    $typeTMCs = $propertyController->getTypeTMC();


    $itemController = new ItemController();
    $inventoryItem = $itemController->getInventoryItem($currentID);

    $brandTMCs = $propertyController->getBrandsByTypeTMC($inventoryItem->IDTypesTMC);
    $modelTMCs = $propertyController->getModelsByBrand($inventoryItem->IDBrandTMC);
    $historyController = new HistoryOperationsController();
    $historyOperations = $historyController->getHistoryOperations($inventoryItem->ID_TMC);

    /*$endTime = microtime(true);
    $loadTime = $endTime - $startTime;
    error_log("Время загрузки cardItem.php: " . $loadTime . " секунд. Загружено объектов: ");*/



} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="\..\..\styles\cardStyle.css" rel="stylesheet">
    <title>CardDocument</title>
</head>

<body>
    <div class="form-container" id="cardContainer">
        <!-- Статус ТМЦ-->
        <h3>Статус ТМЦ - <?= (new StatusItem())->getDescription($inventoryItem->Status) ?> </h3>
        <!-- Группа Тип ТМЦ -->
        <div class="form-group">
            <label class="lb" id="selectTypeTMC">Тип ТМЦ:</label>
            <select class="form-select" aria-label="Default select example" id="idTypeTMC">
                <option value="0"></option>
                <?php foreach ($typeTMCs as $value): ?>
                    <option value="<?= $value->IDTypesTMC ?>" <?= $value->IDTypesTMC == $inventoryItem->IDTypesTMC ? 'selected' : '' ?>>
                        <?= htmlspecialchars($value->NameTypesTMC) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Группа тип бренда -->
        <div class="form-group">
            <label class="lb">Бренд:</label>
            <select class="form-select" aria-label="Default select example">
                <option value="0"></option>
                <?php foreach ($brandTMCs as $value): ?>
                    <option value="<?= $value->IDBrandTMC ?>" <?= $value->IDBrandTMC == $inventoryItem->IDBrandTMC ? 'selected' : '' ?>>
                        <?= htmlspecialchars($value->BrandTMC->NameBrand) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Группа тип модели -->
        <div class="form-group">
            <label class="lb">Модель:</label>
            <select class="form-select" aria-label="Default select example">
                <option value="0"></option>
                <?php foreach ($modelTMCs as $value): ?>
                    <option value="<?= $value->IDModel ?>" <?= $value->IDModel == $inventoryItem->IDModel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($value->ModelTMC->NameModel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Группа Наименование -->
        <div class="form-group">
            <label class="lb">Наименование:</label>
            <textarea class="form-control auto-expand" id="txtNameTMC" name="nameTMC" placeholder="Укажите наименование"
                rows="1" aria-label="Наименование"
                oninput="autoResize(this)"><?= htmlspecialchars($inventoryItem->NameTMC ?? '') ?></textarea>
        </div>
        <!-- Группа Серийный номер -->
        <div class="form-group">
            <label class="lb">Серийный номер:</label>
            <input type="text" class="form-control" id="txtSerialNum!" placeholder="Укажите серийный номер"
                aria-label="Username" aria-describedby="basic-addon1"
                value="<?= $inventoryItem->SerialNumber ? $inventoryItem->SerialNumber : 'Серийный номер отсутствует' ?>">
        </div>
        <!-- Таблица с историей -->
        <div class="box" id="historyBox" style="grid-area: box-4">
            <div class="card-body">
                <h5 class="card-title" style="margin: 5px;">Последнии операции:</h5>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Дата </th>
                                <th>Операция </th>
                                <th>Отв. </th>
                            </tr>
                        </thead>
                    </table>
                    <div class="scroll-table-body">
                        <table>
                            <tbody id="tableBody">
                                <?php foreach ($historyOperations as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date_create($item->HistoryData)->format('d.m.y')) ?>
                                        </td>
                                        <td><?= htmlspecialchars($item->CommentsHistory->ValueComment) ?></td>
                                        <td><?= htmlspecialchars($item->User->FIO) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>