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

    <style>
        .property-management {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .list-group-container {
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-top: 1rem;
        }

        .list-group {
            margin-bottom: 0;
        }

        .list-group-item {
            border-left: none;
            border-right: none;
            border-bottom: 1px solid #dee2e6;
        }

        .list-group-item:first-child {
            border-top: none;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .input-group {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="property-management">
        <input type="hidden" id="typeProperty" value="<?= $typeProperty ?? '' ?>">
        <input type="hidden" id="propertyId" value="<?= $propertyId ?? 0 ?>">

        <h4 class="mb-3">Добавить <?= ucfirst($typeName) ?></h4>

        <div class="input-group mb-3">
            <input type="text" id="propertyName" class="form-control" placeholder="Введите <?= $typeName ?>">
            <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); saveProperty()">Добавить</button>
        </div>

        <div class="list-group-container">
            <div class="list-group">
                <?php foreach ($items as $item): ?>
                    <div class="list-group-item"><?= htmlspecialchars($item->getName()) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>



        // Автоматическая подстройка высоты контейнера при загрузке
        document.addEventListener('DOMContentLoaded', function () {
            const listGroupContainer = document.querySelector('.list-group-container');
            if (listGroupContainer) {
                // Устанавливаем максимальную высоту в зависимости от доступного пространства
                const availableHeight = window.innerHeight * 0.4; // 40% от высоты viewport
                listGroupContainer.style.maxHeight = Math.min(300, availableHeight) + 'px';
            }
        });
    </script>
    <script type="module" src="/js/modals/propertyItemModal.js"></script>


</body>

</html>