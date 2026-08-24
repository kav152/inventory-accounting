import {
    executeEntityAction,
    getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";

// Импортируем константы для работы с селектами
import { PropertyTMC, PropertySelectID } from "../../src/constants/properties.js";

/**
 * ГЛАВНЫЙ ЭКСПОРТИРУЕМЫЙ ОБРАБОТЧИК
 * Инициализирует все события для модального окна карточки ТМЦ.
 * @param {HTMLElement} modalElement - DOM-элемент модального окна (#cardItemModal)
 */
export function initCardTMCModalHandlers(modalElement) {

    // 1. Обработчик отправки формы (сохранение ТМЦ)
    modalElement.addEventListener("submit", async function (e) {
        e.preventDefault();
        await handleFormSubmit(modalElement);
    });

    // 2. Обработчик чекбокса "Серийный номер отсутствует"
    initSerialNumberToggle(modalElement);

    // 3. Обработчики каскадных селектов (Тип -> Бренд -> Модель)
    initCascadeSelectHandlers(modalElement);
}

/**
 * Обрабатывает отправку формы карточки ТМЦ.
 * Собирает данные, валидирует и отправляет на сервер.
 */
async function handleFormSubmit(modalElement) {
    const form = document.getElementById("cardItemForm");
    if (!form) {
        showNotification(TypeMessage.error, "Форма не найдена");
        return;
    }

    // Валидация обязательных полей
    const requiredFields = {
        idTypeTMC: "Тип ТМЦ",
        idBrand: "Бренд",
        idModel: "Модель",
        nameTMC: "Наименование",
    };

    let isValid = true;
    for (const [fieldName, fieldLabel] of Object.entries(requiredFields)) {
        const field = form.elements[fieldName];
        if (!field || field.value === "0" || field.value.trim() === "") {
            showNotification(TypeMessage.error, `Поле "${fieldLabel}" обязательно для заполнения`);
            field?.focus();
            isValid = false;
            break;
        }
    }
    if (!isValid) return;

    // Подготовка данных
    const formData = getCollectFormData(form, window.statusEntity);

    try {
        // Отправка на сервер
        const result = await executeEntityAction({
            action: window.statusEntity,
            formData: formData,
            url: "/src/BusinessLogic/Actions/processCUDInventoryItem.php",
            successMessage: "ТМЦ успешно сохранен",
        });

        // Обновление таблицы на главной странице
        executeActionForCUD(
            window.statusEntity,
            result.resultEntity,
            "inventoryTable",
            result.fields,
            "row-container",
            "id"
        );

        // Закрытие модального окна
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        modalInstance.hide();

    } catch (error) {
        console.error("Ошибка сохранения ТМЦ:", error);
        showNotification(TypeMessage.error, "Ошибка при сохранении ТМЦ");
    }
}

/**
 * Инициализирует переключение состояния поля "Серийный номер".
 */
function initSerialNumberToggle(modalElement) {
    const checkbox = modalElement.querySelector("#checkSerialNum");
    const serialInput = modalElement.querySelector("#txtSerialNum");

    if (!checkbox || !serialInput) return;

    const toggleHandler = () => {
        if (checkbox.checked) {
            serialInput.disabled = true;
            serialInput.placeholder = "Серийный номер отсутствует";
            serialInput.value = "";
        } else {
            serialInput.disabled = false;
            serialInput.placeholder = "Укажите серийный номер";
        }
    };

    checkbox.addEventListener("change", toggleHandler);
    // Инициализируем начальное состояние
    toggleHandler();
}

/**
 * Инициализирует связанные (каскадные) выпадающие списки.
 * При изменении Типа загружаются Бренды, при изменении Бренда — Модели.
 */
function initCascadeSelectHandlers(modalElement) {

    const typeSelect = modalElement.querySelector(`#${PropertySelectID[PropertyTMC.TYPE_TMC]}`);
    const brandSelect = modalElement.querySelector(`#${PropertySelectID[PropertyTMC.BRAND]}`);

    if (typeSelect) {
        typeSelect.addEventListener("change", (e) => handleSelectChange(e, PropertyTMC.TYPE_TMC, PropertyTMC.BRAND));
    }
    if (brandSelect) {
        brandSelect.addEventListener("change", (e) => handleSelectChange(e, PropertyTMC.BRAND, PropertyTMC.MODEL));
    }
}

/**
 * Обработчик изменения каскадного селекта.
 * Загружает данные для следующего зависимого селекта.
 * @param {Event} event - Событие change
 * @param {string} currentType - Текущий измененный тип (PropertyTMC.TYPE_TMC/BRAND)
 * @param {string} nextType - Следующий зависимый тип (PropertyTMC.BRAND/MODEL)
 */
async function handleSelectChange(event, currentType, nextType) {
    const selectedValue = Number(event.target.value);
    const nextSelect = document.getElementById(PropertySelectID[nextType]);

    if (!nextSelect) return;

    // Сброс следующего селекта, если текущий не выбран
    if (selectedValue === 0) {
        nextSelect.disabled = true;
        nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;

        // Если сбросили Бренд, сбросим и Модель
        if (nextType === PropertyTMC.BRAND) {
            const modelSelect = document.getElementById(PropertySelectID[PropertyTMC.MODEL]);
            if (modelSelect) {
                modelSelect.disabled = true;
                modelSelect.innerHTML = `<option value="0"></option>`;
            }
        }
        return;
    }

    nextSelect.disabled = false;

    // Формирование URL для загрузки данных
    let url = "";
    switch (currentType) {
        case PropertyTMC.TYPE_TMC:
            url = `/src/BusinessLogic/getBrands.php?type_id=${selectedValue}`;
            break;
        case PropertyTMC.BRAND:
            url = `/src/BusinessLogic/getModels.php?type_id=${selectedValue}`;
            break;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        // Очистка и заполнение следующего селекта
        nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;
        data.forEach((item) => {
            nextSelect.add(new Option(item.Name, item.ID));
        });

    } catch (error) {
        console.error(`Ошибка загрузки ${nextType}:`, error);
        nextSelect.innerHTML = `<option value="0">Ошибка загрузки</option>`;
    }
}

window.handleSelectChange = handleSelectChange;

/**
 * Инициализирует селекты на основе данных, переданных из PHP.
 * Вызывается из cardItem_modal.php после загрузки окна.
 */
window.initializeSelects = async function () {
    const typeSelect = document.getElementById(PropertySelectID[PropertyTMC.TYPE_TMC]);
    const brandSelect = document.getElementById(PropertySelectID[PropertyTMC.BRAND]);
    const modelSelect = document.getElementById(PropertySelectID[PropertyTMC.MODEL]);

    const typeId = typeSelect?.value || "0";
    const brandId = window.cardItemData?.brandId || "0";
    const modelId = window.cardItemData?.modelId || "0";



    // Если выбран тип, загружаем бренды
    if (typeId !== "0") {
        await handleSelectChange({ target: typeSelect }, PropertyTMC.TYPE_TMC, PropertyTMC.BRAND);

        // Если в данных есть brandId, выбираем его и загружаем модели
        if (brandId !== "0" && brandSelect) {
            brandSelect.value = brandId;
            await handleSelectChange({ target: brandSelect }, PropertyTMC.BRAND, PropertyTMC.MODEL);

            // Если в данных есть modelId, выбираем его
            if (modelId !== "0" && modelSelect) {
                modelSelect.value = modelId;
            }
        }
    }
};