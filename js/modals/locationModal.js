import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { modalRegistry } from "../modalTypes.js";
import { syncLocationTableRow } from "./locationTableRows.js";
import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";

/**
 * Аннулировать выбранную локацию / сервисный центр
 * @param {string|number} id
 * @param {string} modalType locationModal | locationServiceModal
 */
export async function deleteLocationById(id, modalType = "locationModal") {
  const numericId = Number(id);
  if (!numericId) {
    showNotification(TypeMessage.notification, "Не выбран элемент для аннулирования");
    return false;
  }

  try {
    const result = await executeEntityAction({
      action: Action.DELETE,
      formData: { id: numericId },
      url: "/src/BusinessLogic/Actions/processCUDLocation.php",
      successMessage: "Запись аннулирована",
    });

    const config = modalRegistry.getByModalType(modalType);
    syncLocationTableRow(
      Action.DELETE,
      result.resultEntity || { id: numericId },
      config?.tableContainerId || "locationTableContainer",
      config?.rowClass || "row-location",
      { showMainBadge: modalType === "locationModal" },
    );

    return true;
  } catch (error) {
    console.error("Ошибка аннулирования локации:", error);
    return false;
  }
}

window.deleteLocationById = deleteLocationById;

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

function getLocationTableTarget() {
  const modalType = window.currentModalType || "locationModal";
  const config = modalRegistry.getByModalType(modalType);
  return {
    tableId: config?.tableContainerId || "locationTableContainer",
    rowClass: config?.rowClass || "row-location",
    showMainBadge: modalType === "locationModal",
  };
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

    const { tableId, rowClass, showMainBadge } = getLocationTableTarget();
    syncLocationTableRow(
      window.statusEntity,
      result.resultEntity,
      tableId,
      rowClass,
      { showMainBadge },
    );

    if (typeof window.hideGlobalLoader === "function") {
      window.hideGlobalLoader();
    }

    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}
