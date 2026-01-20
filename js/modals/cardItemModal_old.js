import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
//import { openModalAction } from './modalLoader.js';
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";

let currentAction = null;

(function () {

  let statusItem = null;
  let selectedId = null;
  function openCardTMC(action, id = 0) {
    const url = ActionUrls[action];
    statusItem = action;

    /* const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );*/

    const selectedRows = rowSelectionManager.getSelectedRows("inventoryTable");

    let validStatuses = [];
    switch (action) {
      case Action.CREATE:
        currentAction = Action.CREATE;
        break;
      case Action.EDIT:
        validStatuses = [StatusItem.Released];
        if (selectedRows.length === 0) {
          showNotification(
            TypeMessage.notification,
            "Выберите ТМЦ для редактирования"
          );
          return;
        }
        selectedId =
          rowSelectionManager.getSelectedRows("inventoryTable")[0].id;
        currentAction = Action.UPDATE;
        break;
      case Action.CREATE_ANALOG:
        if (selectedRows.length === 0) {
          showNotification(
            TypeMessage.notification,
            "Выберите ТМЦ в качестве аналога"
          );
          return;
        }
        selectedId =
          rowSelectionManager.getSelectedRows("inventoryTable")[0].id;

        currentAction = Action.CREATE;

        break;
      default:
        return;
    }

    openModalAction('cardItemModal', null, validStatuses, {
      statusItem: action,
      id: selectedId,
    });
    rowSelectionManager.clearSelection("row-container");
  }

  window.openCardTMC = openCardTMC;



  /*function openPropertyView(propertyTMC) {
    const propType = propertyTMC;
    let nameIdSelect;
    let previousSelect;

    switch (propertyTMC) {
      case PropertyTMC.TYPE_TMC:
        nameIdSelect = PropertySelectID[PropertyTMC.TYPE_TMC];
        break;
      case PropertyTMC.BRAND:
        nameIdSelect = PropertySelectID[PropertyTMC.TYPE_TMC];
        previousSelect = document.getElementById(
          PropertySelectID[PropertyTMC.TYPE_TMC]
        );
        break;
      case PropertyTMC.MODEL:
        nameIdSelect = PropertySelectID[PropertyTMC.BRAND];
        previousSelect = document.getElementById(
          PropertySelectID[PropertyTMC.BRAND]
        );
        break;
    }

    const propertyContainer = document.getElementById("propertyContainer");
    const mainContainer = document.getElementById("mainContainer");

    if (propertyContainer?.classList.contains("show")) {
      propertyContainer?.classList.remove("show");
      mainContainer?.classList.remove("expanded");
      propertyContainer?.classList.toggle("close");
      if (previousSelect != null) previousSelect.disabled = false;
    } else {
      if (previousSelect != null) previousSelect.disabled = true;
      propertyContainer?.classList.remove("close");
      propertyContainer?.classList.toggle("show");
      mainContainer?.classList.toggle("expanded");
      let url =
        "/src/View/" +
        `propertyTMC.php?type=${encodeURIComponent(propType)}&property_id=${
          document.getElementById(nameIdSelect).value
        }`;

      fetch(url)
        .then((response) => response.text())
        .then((html) => {
          propertyContainer.innerHTML = html;

          const scripts = propertyContainer.querySelectorAll("script");
          scripts.forEach((script) => {
            const newScript = document.createElement("script");

            // Копируем атрибуты (src, async и т.д.)
            Array.from(script.attributes).forEach((attr) => {
              newScript.setAttribute(attr.name, attr.value);
            });

            // Копируем содержимое скрипта
            newScript.textContent = script.textContent;

            // Вставляем в DOM для выполнения
            document.body.appendChild(newScript).remove();
          });
        });
    }
  }*/

  function toggleClass() {
    const checkbox = document.getElementById("checkSerialNum");
    const serialInput = document.getElementById("txtSerialNum");

    console.log(checkbox);
    //console.log(serialInput);

    if (checkbox.checked) {
      serialInput.disabled = true;
      serialInput.placeholder = "Серийный номер отсутствует";
      serialInput.value = "";
    } else {
      serialInput.disabled = false;
      serialInput.placeholder = "Укажите серийный номер";
    }
  }

  async function handleSelectChange(event, currentType, nextType) {
    if (event == null) {
      console.log("event - пустой");
      return;
    } else {
      //console.log(`Значение ${event.target.value}`);
    }
    //console.log(event.target);

    const selectedValue = Number(event.target.value);
    const nextSelect = document.getElementById(PropertySelectID[nextType]);

    // Сбрасываем все зависимые селекты при изменении текущего
    //resetDependentSelects(currentType);

    if (selectedValue === 0) {
      console.log(`selectedValue === 0`);

      nextSelect.disabled = true;
      nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;
      return;
    }

    nextSelect.disabled = false;

    let url = "";
    switch (currentType) {
      case PropertyTMC.TYPE_TMC:
        url = `/src/BusinessLogic/getBrands.php?type_id=${selectedValue}`;

        const modelSelect = document.getElementById(
          PropertySelectID[PropertyTMC.MODEL]
        );
        modelSelect.disabled = true;
        modelSelect.innerHTML = `<option value="0"></option>`;
        document.getElementById("addModelBtn").disabled = true;

        break;
      case PropertyTMC.BRAND:
        url = `/src/BusinessLogic/getModels.php?type_id=${selectedValue}`;
        document.getElementById("addModelBtn").disabled = false;
        break;
    }

    try {
      const response = await fetch(url);
      const data = await response.json();
      nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;

      data.forEach((item) => {
        nextSelect.add(new Option(item.Name, item.ID));
      });

      // Разблокируем кнопку добавления для следующего типа
      /* const nextButton = document.getElementById(PropertyButtonID[nextType]);
      if (nextButton) {
        nextButton.disabled = false;
      }*/
    } catch (error) {
      console.error(`Ошибка загрузки ${nextType}:`, error);
      nextSelect.innerHTML = `<option value="0">Ошибка загрузки</option>`;
    }
  }

  async function initializeSelects() {
    const typeSelect = document.getElementById(
      PropertySelectID[PropertyTMC.TYPE_TMC]
    );
    const brandSelect = document.getElementById(
      PropertySelectID[PropertyTMC.BRAND]
    );
    const modelSelect = document.getElementById(
      PropertySelectID[PropertyTMC.MODEL]
    );

    const typeId = typeSelect.value;
    // Получаем данные из глобального объекта
    const brandId = window.cardItemData.brandId;
    const modelId = window.cardItemData.modelId;
    //const statusItem = window.cardItemData.statusItem;
    //console.log("Статус действия: " + statusItem);
    //console.log("Ид типа - " + typeId + ", бренда - " + brandId + ", модели - " + modelId);

    // Сначала сбрасываем все зависимые селекты
    //resetDependentSelects(PropertyTMC.TYPE_TMC);

    if (typeId !== "0") {
      // Загружаем бренды для выбранного типа
      await handleSelectChange(
        { target: typeSelect },
        PropertyTMC.TYPE_TMC,
        PropertyTMC.BRAND
      );
      if (brandId !== "0") {
        brandSelect.value = brandId;
        document.getElementById("addBrandBtn").disabled = +this.value === 0;
        // Загружаем модели для выбранного бренда
        await handleSelectChange(
          { target: brandSelect },
          PropertyTMC.BRAND,
          PropertyTMC.MODEL
        );
        if (modelId !== "0") {
          modelSelect.value = modelId;
          document.getElementById("addModelBtn").disabled = +this.value === 0;
        }
      }
    }
  }

  /*function addPropertySelect(typeProperty, newItem) {
    // Находим соответствующий select на странице
    const selectElement = document.getElementById(
      PropertySelectID[typeProperty]
    );
  
    if (!selectElement) {
      console.error("Элемент не найден для типа: ", typeProperty);
      return;
    }
  
    // Создаем новый option
    const newOption = document.createElement("option");
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
  }*/


  //window.openPropertyView = openPropertyView;
  window.initializeSelects = initializeSelects;
  //window.initCardTMCModalHandlers = initCardTMCModalHandlers;
  window.toggleClass = toggleClass;

  window.handleSelectChange = handleSelectChange;
  //window.addPropertySelect = addPropertySelect;
})();


/**
 * Обработчик работы модального окна inventoryItem
 * @param {HTMLElement} modalElement
 */
export function initInventoryItemModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleInventoryItemFormSubmit(modalElement);
  });

  //toggleClass1();

  // 2. Инициализация динамических элементов (если нужны)
  initDynamicElements(modalElement);
}

function toggleClass1() {
  const checkbox = document.getElementById("checkSerialNum");
  const serialInput = document.getElementById("txtSerialNum");

  console.log(checkbox);
  //console.log(serialInput);

  if (checkbox.checked) {
    serialInput.disabled = true;
    serialInput.placeholder = "Серийный номер отсутствует";
    serialInput.value = "";
  } else {
    serialInput.disabled = false;
    serialInput.placeholder = "Укажите серийный номер";
  }
}

/**
 * Инициализация динамических элементов
 */
function initDynamicElements(modalElement) {

  console.log('Vs в initDynamicElements');

  // Пример: обновление заголовка модального окна
  const modalTitle = modalElement.querySelector("#inventoryItemModalTitle");
  if (statusEntity === Action.UPDATE) {
    modalTitle.textContent = "Редактировать [entityName]";
  } else {
    modalTitle.textContent = "Добавить [entityName]";
  }


  const checkbox = document.getElementById("checkSerialNum");
  const serialInput = document.getElementById("txtSerialNum");

  console.log(checkbox);
  //console.log(serialInput);

  if (checkbox.checked) {
    serialInput.disabled = true;
    serialInput.placeholder = "Серийный номер отсутствует";
    serialInput.value = "";
  } else {
    serialInput.disabled = false;
    serialInput.placeholder = "Укажите серийный номер";
  }

}

/**
 * Обработчик отправки формы
 */
async function handleInventoryItemFormSubmit(modalElement) {
  const form = modalElement.querySelector("#inventoryItemForm");
  const inventoryItemData = getCollectFormData(form, window.statusEntity);

  try {
    const result = await executeEntityAction({
      action: statusEntity,
      formData: inventoryItemData,
      url: "/src/BusinessLogic/Actions/processCUDInventoryItem.php",
      successMessage:
        "[EntityName] успешно " +
        (statusEntity === Action.CREATE ? "добавлен" : "обновлен"),
    });

    executeActionForCUD(
      statusEntity,
      result.resultEntity,
      "inventoryItemTableContainer",
      result.fields,
      "row-inventoryItem",
      "id"
    );

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}


export function initCardTMCModalHandlers(modalElement) {
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();

    const form = document.getElementById("cardItemForm");
    if (!form) {
      showNotification(TypeMessage.error, "Форма не найдена");
      return;
    }

    // Используем FormData для сбора всех данных формы
    const formData = new FormData(form);

    // Вывод всех данных формы в консоль
    /*formData.forEach((value, key) => {
      console.log(`${key}: ${value}`);
    });*/

    //return;

    // Проверка обязательных полей через FormData
    const requiredFields = {
      idTypeTMC: "Тип ТМЦ",
      idBrand: "Бренд",
      idModel: "Модель",
      nameTMC: "Наименование",
    };

    let hasErrors = false;
    for (const [fieldName, fieldLabel] of Object.entries(requiredFields)) {
      if (!formData.get(fieldName) || formData.get(fieldName) === "0") {
        showNotification(
          TypeMessage.error,
          `Поле "${fieldLabel}" обязательно для заполнения`
        );
        const fieldElement = document.querySelector(`[name="${fieldName}"]`);
        if (fieldElement) fieldElement.focus();
        hasErrors = true;
        break;
      }
    }

    if (hasErrors) return;

    const tmcData = getCollectFormData(form, window.statusEntity);
    //console.log(tmcData);
    //console.log(`Cтатус в initCardTMCModalHandlers - ${window.statusEntity}`);
    //return;

    try {
      const result = await executeEntityAction({
        action: window.statusEntity,
        formData: tmcData,
        url: "/src/BusinessLogic/Actions/processCUDInventoryItem.php",
        successMessage: "ТМЦ добавлено успешно",
      });

      executeActionForCUD(
        window.statusEntity,
        result.resultEntity,
        "inventoryTable",
        result.fields,
        "row-container",
        "id"
      );

      // Закрываем модальное окно
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      modalInstance.hide();
    } catch (error) {
      console.error("Ошибка:", error);
    }

    //let url = "/src/BusinessLogic/ActionsTMC/processCreateItem.php";

    /*try {
      const response = await fetch(url, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        showNotification(TypeMessage.success, result.message);

        // Закрываем модальное окно
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        modalInstance.hide();

        if (
          result.statusItem === "create" ||
          result.statusItem === "create_analog"
        ) {
          // Сохраняем ID нового элемента для навигации после перезагрузки
          if (result.resultItem && result.resultItem.id) {
            sessionStorage.setItem("newItemId", result.resultItem.id);
            sessionStorage.setItem("scrollToNewItem", "true");
          }
          //window.needFullReload = true;
          await refreshTableAndNavigate(result.resultItem.id);
        }

        if (result.statusItem === "edit") {
          if (result.resultItem) {
            updateSingleInventoryItem(result.resultItem.id, {
              name: result.resultItem.name,
              serialNumber: result.resultItem.serialNumber,
              brand: result.resultItem.brand,
              responsible: result.resultItem.responsible,
              status: result.resultItem.status,
            });
          }
        }

        if (typeof handleSuccess === "function") {
          handleSuccess();
        } else if (typeof window.handleSuccess === "function") {
          window.handleSuccess();
        }

        // Очищаем форму после успешного сохранения
        setTimeout(() => {
          modalElement.querySelector("form").reset();
        }, 500);
      } else {
        showNotification(TypeMessage.error, result.message);
      }
    } catch (error) {
      console.error("Ошибка при сохранении ТМЦ:", error);
      showNotification(TypeMessage.error, "Ошибка сети при сохранении ТМЦ");
    }*/
  });

  // Обработчик закрытия модального окна
  modalElement.addEventListener("hidden.bs.modal", function () {
    // Дополнительные действия при закрытии окна
    //console.log("Модальное окно ТМЦ закрыто");
  });
}
