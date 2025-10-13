<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/work_modal.log');
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Repositories/GenericRepository.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';

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
</style>


<script>
    window.cardItemData = {
        brandId: <?= $inventoryItem->IDBrandTMC ?? 0 ?>,
        modelId: <?= $inventoryItem->IDModel ?? 0 ?>,
        statusItem: "<?= htmlspecialchars($_POST['statusItem'] ?? '') ?>"
    };
</script>

<script src="/src/constants/properties.js"></script>
<script src="/src/constants/actions.js"></script>

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
                            case Action::EDIT:
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
                                    <button type="button" class="btn btn-outline-primary" id="addTypeTMCBtn"
                                        onclick="openPropertyView(PropertyTMC.TYPE_TMC)">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960"
                                            width="20" fill="currentColor">
                                            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Группа Бренд -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Бренд</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select" aria-label="Выберите бренд" id="brandSelect"
                                        name="idBrand" disabled>
                                        <option value="0"></option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addBrandBtn" disabled
                                        onclick="openPropertyView(PropertyTMC.BRAND)">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960"
                                            width="20" fill="currentColor">
                                            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Группа Модель -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Модель</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select" id="modelSelect" aria-label="Выберите модель"
                                        name="idModel" disabled>
                                        <option value="0"></option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addModelBtn" disabled
                                        onclick="openPropertyView(PropertyTMC.MODEL)">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960"
                                            width="20" fill="currentColor">
                                            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z" />
                                        </svg>
                                    </button>
                                </div>
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
                                        name="checkSerialNum" onchange="toggleClass()" <?php
                                        if (isset($_POST['statusItem']) && $_POST['statusItem'] === Action::EDIT) {
                                            echo empty($inventoryItem->SerialNumber) ? 'checked' : '';
                                        }
                                        ?>>
                                    <label class="form-check-label" for="checkSerialNum">
                                        Серийный номер отсутствует
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="close" id="propertyContainer" style="grid-area: property-part">
                        </div>
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
            document.getElementById("addBrandBtn").disabled = +this.value === 0;
            document.getElementById("brandSelect").disabled = +this.value === 0;
        });

        document.getElementById("brandSelect").addEventListener("change", function () {
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
    function toggleClass() {
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
    }

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

<?php
include __DIR__ . '/message_modal.php';
?>