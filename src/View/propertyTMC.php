<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/cardItem_modal.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/BrandTMCRepository.php';
require_once __DIR__ . '/../Repositories/ModelTMCRepository.php';
require_once __DIR__ . '/../Repositories/LinkBrandToModelRepository.php';
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';
require_once __DIR__ . '/../Repositories/BaseEntity.php';
require_once __DIR__ . '/../BusinessLogic/ActionProperty.php';

DatabaseFactory::setConfig();
$controller = new PropertyController();
$typeProperty = $_GET['type'] ?? '';
$propertyId = (int) ($_GET['property_id'] ?? -1);

//$items = new Collection("",[]);

if (!ActionProperty::isValid($typeProperty)) {
    die("Недопустимое свойство ТМЦ!");
}
switch ($typeProperty) {
    case ActionProperty::TYPE_TMC:
        $typeName = "тип ТМЦ";
        $items = $controller->getTypeTMC();
        //print_r($items);
        break;
    case ActionProperty::BRAND:
        $typeName = "бренд";
        $items = $controller->getBrandsByTypeTMC($propertyId);
        break;
    case ActionProperty::MODEL:
        $typeName = "модель";
        $items = $controller->getModelsByBrand($propertyId);
        break;
}

//print_r($items);
//echo $typeProperty;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--script type="text/javascript" src="\..\..\app_modal.js" defer></script-->
    <title>propertyTMC</title>
</head>

<body>
    <div class="property-management">
        <input type="hidden" id="typeProperty" value="<?= $typeProperty ?? '' ?>">
        <input type="hidden" id="propertyId" value="<?= $propertyId ?? 0 ?>">

        <h4 class="mb-4">Добавить <?= ucfirst($typeName) ?></h4>

        <div class="input-group">
            <input type="text" id="propertyName" class="form-control" placeholder="Введите <?= $typeName ?>">
            <button class="btn btn-primary py-2" onclick="saveProperty()">Добавить</button>
        </div>

        <div class="list-group">
            <?php foreach ($items as $item): ?>
                <div class="list-group-item"><?= htmlspecialchars($item->getName()) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>

        async function saveProperty() {
            const propertyContainer = document.getElementById('propertyContainer');
            if (!propertyContainer) return;
            const input = propertyContainer.querySelector('#propertyName');
            const panel = propertyContainer.querySelector('.property-management');

            if (!input || !panel) return;

            const valueProp = input.value.trim();
            if (!valueProp) {
                alert('Введите название свойства');
                return;
            }

            const type = document.getElementById('typeProperty').value.toUpperCase();
            const propertyId = document.getElementById('propertyId').value;

            const formData = new FormData();
            formData.append('typeProperty', PropertyTMC[type]);
            formData.append('valueProp', valueProp);
            formData.append('property_id', propertyId);

            const response = await fetch('/src/BusinessLogic/addProperty.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            console.log(data);

            data.forEach(element => {
                console.log(`Тип элемента: ${element.Name}, ID: ${element.ID}`)
                console.log(`typeProperty: ${PropertyTMC[type]}`)
                addPropertySelect(PropertyTMC[type], element);
            });
        }

        function addPropertySelect(typeProperty, newItem) {
            // Находим соответствующий select на странице
            const selectElement = document.getElementById(PropertySelectID[typeProperty]);

            if (!selectElement) {
                console.error('Элемент не найден для типа: ', typeProperty);
                return;
            }

            // Создаем новый option
            const newOption = document.createElement('option');
            newOption.value = newItem.ID; // Используйте актуальное свойство с ID
            newOption.textContent = newItem.Name; // Используйте актуальное свойство с именем
            newOption.selected = true;

            // Добавляем новую опцию в select
            selectElement.appendChild(newOption);

            // Обновляем данные в объекте cardItemData, если это необходимо
            switch (typeProperty) {
                case PropertyTMC.BRAND:
                    window.cardItemData.brandId = newItem.ID;
                    break;
                case PropertyTMC.MODEL:
                    window.cardItemData.modelId = newItem.ID;
                    break;
            }

            //console.log(`Добавлен новый элемент в ${PropertySelectID[typeProperty]}:`, newItem);
        }

        function closeProperty() {

        }
    </script>
</body>

</html>