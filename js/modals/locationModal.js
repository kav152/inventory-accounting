import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
//import { openModalAction } from "./modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";

(function () {})();

/**
 * Обработчик работы модального окна location
 * @param {HTMLElement} modalElement
 */
export function initLocationModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleLocationFormSubmit(modalElement);
  });

  // 2. Инициализация динамических элементов (если нужны)
  initDynamicElements(modalElement);
  // 3. Обработка изменения состояния чекбокса
  const checkbox = modalElement.querySelector("#isMainWarehouseCheckbox");
  if (checkbox) {
    checkbox.addEventListener("change", function () {
      const hiddenField = modalElement.querySelector("#isMainWarehouse");
      hiddenField.value = this.checked ? "1" : "0";
    });
  }
}

/**
 * Инициализация динамических элементов
 */
function initDynamicElements(modalElement) {
  // Пример: обновление заголовка модального окна
  const modalTitle = modalElement.querySelector("#locationModalTitle");
  if (window.statusEntity === Action.UPDATE) {
    modalTitle.textContent = "Редактировать локацию";
  } else {
    modalTitle.textContent = "Добавить локацию";
  }
}

/**
 * Обработчик отправки формы
 */
async function handleLocationFormSubmit(modalElement) {
  const form = modalElement.querySelector("#locationForm");

  // Обновляем значение скрытого поля перед отправкой
  const checkbox = modalElement.querySelector('#isMainWarehouseCheckbox');
  const hiddenField = modalElement.querySelector('#isMainWarehouse');
  if (checkbox && hiddenField) {
    hiddenField.value = checkbox.checked ? '1' : '0';
  }

  //console.log(hiddenField.value);
  const isMainWarehouseCheckbox = modalElement.querySelector('#isMainWarehouseCheckbox');

  const locationData = getCollectFormData(form, window.statusEntity);

  //console.log(isMainWarehouseCheckbox);

  try {
    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: locationData,
      url: "/src/BusinessLogic/Actions/processCUDLocation.php",
      successMessage:
        "Локация успешна " +
        (window.statusEntity === Action.CREATE ? "добавлена" : "обновлена"),
    });

    executeActionForCUD(
      window.statusEntity,
      result.resultEntity,
      "locationTableContainer",
      result.fields,
      "row-location",
      "id"
    );

    needFullReload = true;
    hideGlobalLoader();
    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}
