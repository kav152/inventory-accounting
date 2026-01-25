<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/cardItem_modal.log');

?>

<style>
    .main-container {
        display: grid;
        grid-template-columns: 1fr 0fr;
        grid-template-areas: "main-part property-part";
        gap: 1em;
        transition: all 0.3s ease;
        width: auto;
    }

    .main-container.expanded {
        grid-template-columns: 1fr 1fr;
    }

    #propertyContainer {
        background: #f8f9fa;
        border-left: 1px solid #dee2e6;
        padding: 20px;
        overflow-y: auto;
        min-width: 400px;
        height: 100%;
        transition: all 0.6s ease;
        position: relative;
    }

    #propertyContainer.show {
        display: block;
        width: 50%;
        opacity: 1;
        visibility: visible;
    }

    #propertyContainer.close {
        display: none;
        width: 0;
        opacity: 0;
        visibility: hidden;
    }

    .auto-expand {
        resize: none;
        overflow: hidden;
        min-height: 38px;
    }

    /*
    .expandable-section {
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .expandable-section.collapsed {
        max-height: 0;
        opacity: 0;
        margin: 0;
        padding: 0;
    }

    .expandable-section.expanded {
        max-height: 500px;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
    }*/
</style>


<script>
    window.cardItemData = {
        brandId: <?= $inventoryItem->IDBrandTMC ?? 0 ?>,
        modelId: <?= $inventoryItem->IDModel ?? 0 ?>,
        statusItem: "<?= htmlspecialchars($_POST['statusItem'] ?? '') ?>"
    };
</script>

<script src="/src/constants/properties.js"></script>
<script type="module" src="/src/constants/actions.js"></script>

<?php
include_once __DIR__ . '/../Templates/expandable_section.php';
?>

<div class="modal fade" id="cardItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Форма для отправки данных -->
            <form id="cardItemForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="cardItemModalLabel">
                        <?php
                        switch ($statusItem ?? '') {
                            case Action::CREATE:
                                echo "Создание ТМЦ";
                                break;
                            case Action::CREATE_ANALOG:
                                echo "Создание ТМЦ по аналогии";
                                break;
                            case Action::UPDATE:
                                echo "Редактирование ТМЦ";
                                break;
                            default:
                                echo "Карточка ТМЦ";
                        }
                        ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentID" name="id" value="<?= $currentID ?>">
                    <input type="hidden" id="inventoryId"
                        value="<?= htmlspecialchars($inventoryItem->ID_TMC ?? '') ?> ">
                    <input type="hidden" id="idstatusItem" name="statusItem"
                        value="<?= htmlspecialchars($statusItem ?? '') ?>">

                    <div class="main-container" id="mainContainer">
                        <div class="form-container" id="cardContainer" style="grid-area: main-part">

                            <!-- Группа Тип ТМЦ -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Тип ТМЦ</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select" aria-label="Выберите тип ТМС" name="idTypeTMC"
                                        id="typeTMCSelect">
                                        <option value="0"></option>
                                        <?php foreach ($typeTMCs as $value): ?>
                                            <option value="<?= $value->IDTypesTMC ?>"
                                                <?= $value->IDTypesTMC == $inventoryItem->IDTypesTMC ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($value->NameTypesTMC) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary toggle-section-btn"
                                        data-section-id="typeTMCSection" data-select-id="typeTMCSelect">
                                        <i class="bi bi-plus toggle-section-icon" data-section-id="typeTMCSection"></i>
                                    </button>
                                </div>

                                <?php renderExpandableSection(
                                    'typeTMCSection',
                                    'typeTMCSelect',
                                    [['name' => 'NameTypesTMC', 'label' => 'Тип ТМЦ', 'type' => 'text']],
                                    'тип ТМЦ',
                                    'Добавить тип ТМЦ'
                                ); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Бренд</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select" aria-label="Выберите бренд" id="brandSelect"
                                        name="idBrand" disabled>
                                        <option value="0"></option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary toggle-section-btn"
                                        id="addBrandBtn" disabled data-section-id="brandTMCSection"
                                        data-select-id="brandSelect">
                                        <i class="bi bi-plus toggle-section-icon" data-section-id="brandTMCSection"></i>
                                    </button>
                                </div>

                                <?php renderExpandableSection(
                                    'brandTMCSection',
                                    'brandSelect',
                                    [['name' => 'NameBrand', 'label' => 'Бренд', 'type' => 'text']],
                                    'бренд',
                                    'Добавить бренд'
                                ); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Модель</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select" id="modelSelect" aria-label="Выберите модель"
                                        name="idModel" disabled>
                                        <option value="0"></option>
                                    </select>

                                    <button type="button" class="btn btn-outline-primary toggle-section-btn"
                                        id="addModelBtn" disabled data-section-id="modelTMCSection"
                                        data-select-id="modelSelect">
                                        <i class="bi bi-plus toggle-section-icon" data-section-id="modelTMCSection"></i>
                                    </button>

                                </div>

                                <?php renderExpandableSection(
                                    'modelTMCSection',
                                    'modelSelect',
                                    [['name' => 'NameModel', 'label' => 'Модель', 'type' => 'text']],
                                    'модель',
                                    'Добавить модель'
                                ); ?>

                            </div>

                            <!-- Группа Наименование -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Наименование</label>
                                <textarea class="form-control auto-expand" id="txtNameTMC" name="nameTMC"
                                    placeholder="Укажите наименование" rows="2" aria-label="Наименование"
                                    oninput="autoResize(this)"><?= htmlspecialchars($inventoryItem->NameTMC ?? '') ?></textarea>
                            </div>

                            <!-- Группа Серийный номер -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Серийный номер</label>
                                <input type="text" class="form-control" id="txtSerialNum" name="serialNumber"
                                    placeholder="Укажите серийный номер" aria-label="Серийный номер"
                                    value="<?= $inventoryItem->SerialNumber ?? '' ?>">
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkSerialNum"
                                        name="checkSerialNum" onchange="toggleClass()"
                                        <?= empty($inventoryItem->SerialNumber) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="checkSerialNum">
                                        Серийный номер отсутствует
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!--div class="close" id="propertyContainer" style="grid-area: property-part">
                        </div-->
                    </div>
                </div>

                <!-- Футер модального окна -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
                <!--button type="button" onclick="test()">test</!--button-->
            </form>
        </div>
    </div>
</div>

<script>
    // Функция автоматического изменения высоты textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Инициализация высоты textarea при загрузке
    document.addEventListener('DOMContentLoaded', function () {
        const textarea = document.getElementById('txtNameTMC');
        if (textarea) {
            autoResize(textarea);
        }
    });

    // Обработчики событий для модального окна
    document.addEventListener("shown.bs.modal", function () {
        // Включение/выключение кнопок добавления
        document.getElementById("typeTMCSelect").addEventListener("change", function () {
            //console.log('typeTMCSelect');
            document.getElementById("addBrandBtn").disabled = +this.value === 0;
            document.getElementById("brandSelect").disabled = +this.value === 0;
        });

        document.getElementById("brandSelect").addEventListener("change", function () {
            //console.log('brandSelect');
            document.getElementById("addModelBtn").disabled = +this.value === 0;
            document.getElementById("modelSelect").disabled = +this.value === 0;
        });

        // Обработчики изменений в селектах
        document.getElementById(PropertySelectID[PropertyTMC.TYPE_TMC])?.addEventListener("change", (e) => {
            //forceResetModelSelect();
            handleSelectChange(e, PropertyTMC.TYPE_TMC, PropertyTMC.BRAND);
        });

        document.getElementById(PropertySelectID[PropertyTMC.BRAND])?.addEventListener("change", (e) => {
            handleSelectChange(e, PropertyTMC.BRAND, PropertyTMC.MODEL);
        });

        // Инициализация селектов после загрузки
        setTimeout(initializeSelects, 50);
    });

    // Функция переключения состояния поля серийного номера
    /* function toggleClass() {
         const checkbox = document.getElementById("checkSerialNum");
         const serialInput = document.getElementById("txtSerialNum");
 
         if (checkbox.checked) {
             serialInput.disabled = true;
             serialInput.placeholder = "Серийный номер отсутствует";
             serialInput.value = "";
         } else {
             serialInput.disabled = false;
             serialInput.placeholder = "Укажите серийный номер";
         }
     }*/

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function () {
        toggleClass(); // Установить начальное состояние
    });

    function test() {
        // Способ 1: через FormData
        const formData = new FormData(document.getElementById('cardItemForm'));
        console.log('FormData nameTMC:', formData.get('nameTMC'));
        console.log('FormData serialNumber:', formData.get('serialNumber'));
        console.log('FormData statusItem:', formData.get('statusItem')); // Исправлено: 'statusItem' вместо 'idstatusItem'
        console.log('FormData currentID:', formData.get('inventoryId'));
        console.log('FormData id:', formData.get('id'));

        // Способ 2: через querySelector
        console.log('QuerySelector nameTMC:', document.querySelector('[name="nameTMC"]').value);
        console.log('QuerySelector serialNumber:', document.querySelector('[name="serialNumber"]').value);
        console.log('QuerySelector id:', document.querySelector('[name="id"]').value);

        // Способ 3: через ID
        console.log('ID nameTMC:', document.getElementById('txtNameTMC').value);
        console.log('ID serialNumber:', document.getElementById('txtSerialNum').value);
        console.log('ID statusItem:', document.getElementById('idstatusItem').value);
        console.log('ID currentID:', document.getElementById('currentID').value);
    }
</script>

<!--script-- src="/js/modals/propertyItemModal.js"></!--script-->

<?php
include __DIR__ . '/message_modal.php';
?>