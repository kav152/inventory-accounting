<?php
error_reporting(E_ALL);
<<<<<<< HEAD
ini_set('display_errors', 1);
=======
ini_set('display_errors', 0);
>>>>>>> source/feature/local-updates-2026-08
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/cardItem.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../BusinessLogic/HistoryOperationsController.php';
<<<<<<< HEAD
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {

    $startTime = microtime(true);

    $currentID = $_GET['id'] ?? null;
    if (!$currentID) {
        // Если ID не передан, показываем пустую карточку
=======
require_once __DIR__ . '/../BusinessLogic/StatusItem.php';
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $currentID = $_GET['id'] ?? null;
    if (!$currentID) {
>>>>>>> source/feature/local-updates-2026-08
        echo '<div class="alert alert-info">Выберите элемент из списка</div>';
        exit;
    }
    $currentID = (int) $currentID;
    DatabaseFactory::setConfig();
    $propertyController = new PropertyController();
    $typeTMCs = $propertyController->getTypeTMC();

<<<<<<< HEAD

    $itemController = new ItemController();
    $inventoryItem = $itemController->getInventoryItem($currentID);
=======
    $itemController = new ItemController();
    $inventoryItem = $itemController->getInventoryItem($currentID);
    $locations = $itemController->getLocations(false) ?? [];

    if ($inventoryItem && (int) ($inventoryItem->IDLocation ?? 0) > 0 && empty($inventoryItem->Location)) {
        foreach ($locations as $loc) {
            if ((int) $loc->IDLocation === (int) $inventoryItem->IDLocation) {
                $inventoryItem->Location = $loc;
                break;
            }
        }
    }
>>>>>>> source/feature/local-updates-2026-08

    $brandTMCs = $propertyController->getBrandsByTypeTMC($inventoryItem->IDTypesTMC);
    $modelTMCs = $propertyController->getModelsByBrand($inventoryItem->IDBrandTMC);
    $historyController = new HistoryOperationsController();
    $historyOperations = $historyController->getHistoryOperations($inventoryItem->ID_TMC);

<<<<<<< HEAD
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
=======
    $currentLocationId = (int) ($inventoryItem->IDLocation ?? 0);
    $currentLegal = trim((string) ($inventoryItem->Location->FormsJointStockCompanies ?? ''));
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Ошибка загрузки: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<link href="/styles/cardStyle.css" rel="stylesheet">

<div class="form-container" id="cardContainer"
    data-id="<?= (int) $inventoryItem->ID_TMC ?>"
    data-type="<?= (int) ($inventoryItem->IDTypesTMC ?? 0) ?>"
    data-brand="<?= (int) ($inventoryItem->IDBrandTMC ?? 0) ?>"
    data-model="<?= (int) ($inventoryItem->IDModel ?? 0) ?>"
    data-name="<?= htmlspecialchars($inventoryItem->NameTMC ?? '', ENT_QUOTES) ?>"
    data-serial="<?= htmlspecialchars((string) ($inventoryItem->SerialNumber ?? ''), ENT_QUOTES) ?>">
    <h3>Статус ТМЦ - <?= htmlspecialchars((new StatusItem())->getDescription($inventoryItem->Status) ?? '') ?></h3>

    <div class="form-group">
        <label class="lb" id="selectTypeTMC">Тип ТМЦ:</label>
        <select class="form-select" aria-label="Тип ТМЦ" id="idTypeTMC" disabled>
            <option value="0"></option>
            <?php foreach ($typeTMCs as $value): ?>
                <option value="<?= $value->IDTypesTMC ?>" <?= $value->IDTypesTMC == $inventoryItem->IDTypesTMC ? 'selected' : '' ?>>
                    <?= htmlspecialchars($value->NameTypesTMC) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="lb">Бренд:</label>
        <select class="form-select" aria-label="Бренд" id="idBrandTMC" disabled>
            <option value="0"></option>
            <?php foreach ($brandTMCs as $value): ?>
                <?php
                $brandId = (int) ($value->IDBrandTMC ?? $value->BrandTMC->IDBrandTMC ?? 0);
                $brandName = $value->NameBrand ?? $value->BrandTMC->NameBrand ?? '';
                ?>
                <option value="<?= $brandId ?>" <?= $brandId === (int) $inventoryItem->IDBrandTMC ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brandName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="lb">Модель:</label>
        <select class="form-select" aria-label="Модель" id="idModelTMC" disabled>
            <option value="0"></option>
            <?php foreach ($modelTMCs as $value): ?>
                <?php
                $modelId = (int) ($value->IDModel ?? $value->ModelTMC->IDModel ?? 0);
                $modelName = $value->NameModel ?? $value->ModelTMC->NameModel ?? '';
                ?>
                <option value="<?= $modelId ?>" <?= $modelId === (int) $inventoryItem->IDModel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($modelName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="lb">Наименование:</label>
        <textarea class="form-control auto-expand" id="txtNameTMC" name="nameTMC" placeholder="Укажите наименование"
            rows="1" aria-label="Наименование" readonly><?= htmlspecialchars($inventoryItem->NameTMC ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label class="lb">Серийный номер:</label>
        <input type="text" class="form-control" id="txtSerialNum" placeholder="Укажите серийный номер"
            aria-label="Серийный номер" readonly
            value="<?= htmlspecialchars($inventoryItem->SerialNumber ?: 'Серийный номер отсутствует') ?>">
    </div>

    <div class="form-group">
        <label class="lb" for="cardLocationSelect">Локация:</label>
        <select class="form-select" id="cardLocationSelect" name="idLocation">
            <option value="0">Не выбрана</option>
            <?php foreach ($locations as $loc): ?>
                <?php
                $locId = (int) ($loc->IDLocation ?? 0);
                $locLegal = trim((string) ($loc->FormsJointStockCompanies ?? ''));
                ?>
                <option value="<?= $locId ?>"
                    data-legal="<?= htmlspecialchars($locLegal, ENT_QUOTES) ?>"
                    <?= $currentLocationId === $locId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($loc->NameLocation ?? '') ?>
                    <?= $locLegal !== '' ? ' — ' . htmlspecialchars($locLegal) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="lb" for="cardLegalEntity">Юр. лицо:</label>
        <input type="text" class="form-control" id="cardLegalEntity" name="legalEntity"
            placeholder="Укажите юр. лицо локации"
            value="<?= htmlspecialchars($currentLegal) ?>">
        <div class="form-text" style="font-size:12px;color:#64748b;margin-top:4px;">
            Привязано к локации. Можно изменить и сохранить.
        </div>
    </div>

    <div class="form-group" style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveCardLegal">
            Сохранить юр. лицо
        </button>
        <span id="cardLegalSaveStatus" style="font-size:12px;color:#64748b;"></span>
    </div>

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
                            <?php if ($historyOperations): ?>
                                <?php foreach ($historyOperations as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date_create($item->HistoryData)->format('d.m.y')) ?></td>
                                        <td><?= htmlspecialchars($item->CommentsHistory->ValueComment ?? '—') ?></td>
                                        <td><?= htmlspecialchars($item->User->FIO ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
>>>>>>> source/feature/local-updates-2026-08
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
</body>

</html>
=======
</div>

<script>
(function () {
    const locationSelect = document.getElementById('cardLocationSelect');
    const legalInput = document.getElementById('cardLegalEntity');
    const saveBtn = document.getElementById('btnSaveCardLegal');
    const statusEl = document.getElementById('cardLegalSaveStatus');
    const card = document.getElementById('cardContainer');

    if (locationSelect && legalInput && !locationSelect.dataset.legalBound) {
        locationSelect.dataset.legalBound = '1';
        locationSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            legalInput.value = selected?.getAttribute('data-legal') || '';
        });
    }

    if (!saveBtn || !card) return;

    saveBtn.addEventListener('click', async function () {
        const id = card.getAttribute('data-id');
        const locationId = locationSelect?.value || '0';
        const legalEntity = (legalInput?.value || '').trim();

        if (!id) {
            if (statusEl) statusEl.textContent = 'Нет ID ТМЦ';
            return;
        }
        if (!locationId || locationId === '0') {
            if (typeof showNotification === 'function') {
                showNotification(TypeMessage.notification, 'Выберите локацию');
            }
            if (statusEl) statusEl.textContent = 'Выберите локацию';
            locationSelect?.focus();
            return;
        }

        saveBtn.disabled = true;
        if (statusEl) statusEl.textContent = 'Сохранение…';

        const serialRaw = card.getAttribute('data-serial') || '';
        const serialNumber = serialRaw === 'Серийный номер отсутствует' ? '' : serialRaw;

        const payload = {
            statusEntity: 'update',
            id: id,
            nameTMC: card.getAttribute('data-name') || document.getElementById('txtNameTMC')?.value || '',
            idTypeTMC: card.getAttribute('data-type') || '0',
            idBrand: card.getAttribute('data-brand') || '0',
            idModel: card.getAttribute('data-model') || '0',
            serialNumber: serialNumber,
            idLocation: locationId,
            legalEntity: legalEntity,
        };

        try {
            const response = await fetch('/src/BusinessLogic/Actions/processCUDInventoryItem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Ошибка сохранения');
            }

            // Обновляем data-legal у выбранной опции
            const selected = locationSelect.options[locationSelect.selectedIndex];
            if (selected) {
                selected.setAttribute('data-legal', legalEntity);
                const baseName = (selected.textContent || '').split(' — ')[0].trim();
                selected.textContent = legalEntity
                    ? `${baseName} — ${legalEntity}`
                    : baseName;
            }

            // Обновляем колонку «Юр. лицо» в главной таблице
            const row = document.querySelector(`#inventoryTable tr.row-container[data-id="${id}"]`);
            if (row) {
                row.setAttribute('data-legal', legalEntity);
                const legalCell = row.querySelector('.legal-cell');
                if (legalCell) {
                    const text = legalEntity || 'не указано';
                    legalCell.textContent = text;
                    legalCell.title = legalEntity
                        ? legalEntity
                        : 'Заполните юр. лицо в Админка → Локации';
                    legalCell.classList.toggle('is-empty', !legalEntity);
                }
                const locationCell = row.cells?.[6];
                if (locationCell && selected) {
                    locationCell.textContent = (selected.textContent || '').split(' — ')[0].trim();
                }
            }

            if (statusEl) statusEl.textContent = 'Сохранено';
            if (typeof showNotification === 'function') {
                showNotification(TypeMessage.success, 'Юр. лицо сохранено');
            }
        } catch (error) {
            if (statusEl) statusEl.textContent = error.message || 'Ошибка';
            if (typeof showNotification === 'function') {
                showNotification(TypeMessage.error, error.message || 'Ошибка сохранения');
            }
        } finally {
            saveBtn.disabled = false;
            setTimeout(() => {
                if (statusEl && statusEl.textContent === 'Сохранено') statusEl.textContent = '';
            }, 2500);
        }
    });
})();
</script>
>>>>>>> source/feature/local-updates-2026-08
