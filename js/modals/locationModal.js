import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";

(function () {})();

/**
 * Обработчик работы модального окна location
 * @param {HTMLElement} modalElement
 */
export function initLocationModalHandlers(modalElement) {
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleLocationFormSubmit(modalElement);
  });

  initDynamicElements(modalElement);

  const checkbox = modalElement.querySelector("#isMainWarehouseCheckbox");
  if (checkbox) {
    checkbox.addEventListener("change", function () {
      const hiddenField = modalElement.querySelector("#isMainWarehouse");
      if (hiddenField) {
        hiddenField.value = this.checked ? "1" : "0";
      }
    });
  }

  const citySelect = modalElement.querySelector("#citySelect");
  const cityAddressPreview = modalElement.querySelector("#CityAddressPreview");
  if (citySelect && cityAddressPreview) {
    const syncCityAddress = () => {
      const option = citySelect.options[citySelect.selectedIndex];
      cityAddressPreview.value = option?.dataset?.address || "";
    };
    citySelect.addEventListener("change", syncCityAddress);
    syncCityAddress();
  }
}

function initDynamicElements(modalElement) {
  const modalTitle = modalElement.querySelector("#locationModalTitle");
  if (!modalTitle) return;
  modalTitle.textContent =
    window.statusEntity === Action.UPDATE
      ? "Редактировать локацию"
      : "Добавить локацию";
}

async function handleLocationFormSubmit(modalElement) {
  const form = modalElement.querySelector("#locationForm");

  const checkbox = modalElement.querySelector("#isMainWarehouseCheckbox");
  const hiddenField = modalElement.querySelector("#isMainWarehouse");
  if (checkbox && hiddenField) {
    hiddenField.value = checkbox.checked ? "1" : "0";
  }

  const locationData = getCollectFormData(form, window.statusEntity);

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
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}
