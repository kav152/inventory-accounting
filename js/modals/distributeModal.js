import { showNotification } from "../modals/setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { Action } from "../../src/constants/actions.js";
import { executeEntityAction, getCollectFormData, } from "../templates/entityActionTemplate.js";
import { updateInventoryStatus, updateInventoryAfterTransfer } from "../updateFunctions.js";


// Обработчик клика на "Передать ТМЦ"
(function () {
  let distributeModalInstance = null;

  function openDistributeModal(StatusUser) {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );
    //const selectedRows = rowSelectionManager.getSelectedRows("inventoryTable");

    if (selectedRows.length === 0) {
      showNotification(TypeMessage.notification, "Выберите ТМЦ для передачи");
      return;
    }
    let validStatuses = [StatusItem.Released, StatusItem.Repair];
    if (StatusUser == 0) {
      validStatuses = [StatusItem.Released, StatusItem.NotDistributed, StatusItem.Repair];
    }

    //console.log(`selectedRows - ${selectedRows}`);
    window.openModalAction("distributeModal", selectedRows, validStatuses);
    //window.removingSelection();
  }


  window.openDistributeModal = openDistributeModal;
  //window.initDistributeHandlers = initDistributeHandlers;
})();
/*
export function initDistributeHandlers(modalElement) {
    const form = document.getElementById("distributeForm");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append("tmc_ids", JSON.stringify(window.selectedTMCIds));

       try {
         const response = await fetch(
           "/src/BusinessLogic/ActionsTMC/processDistributeTMC.php",
           {
             method: "POST",
             body: formData,
           }
         );
 
         const data = await response.json();
 
         if (data.success) {
           const modal = bootstrap.Modal.getInstance(modalElement);
           modal.hide();
 
           showNotification(TypeMessage.success, data.message);
 
           // Обновляем статусы в таблице
           if (typeof updateInventoryStatus === "function") {
             updateInventoryStatus(
               window.selectedTMCIds,
               StatusItem.ConfirmItem
             );
           }
         } else {
           showNotification(TypeMessage.error, data.message);
         }
       } catch (error) {
         console.error("Ошибка отправки:", error);
         showNotification(TypeMessage.error, "Ошибка сети");
       }
    };
  }*/

/**
* Обработчик работы модального окна распределения
* @param {HTMLElement} modalElement 
*/
export function initDistributeModalHandlers(modalElement) {

  if (!modalElement) {
    console.error("Modal element is null or undefined");
    return;
  }

  const form = modalElement.querySelector("#distributeForm");
  if (!form) {
    console.error("Form element not found in modal");
    return;
  }
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleDistributeFormSubmit(modalElement);
  });

  const locationSelect = modalElement.querySelector("#distributeLocationSelect");
  if (locationSelect && !locationSelect.dataset.legalBound) {
    locationSelect.dataset.legalBound = "1";
    locationSelect.addEventListener("change", () => updateDistributeLegalPanel(modalElement));
  }

  // Обновляем блок юр. лиц после открытия / заполнения таблицы
  setTimeout(() => updateDistributeLegalPanel(modalElement), 50);
}

function collectSourceLegalEntities(modalElement) {
  const values = new Set();
  modalElement.querySelectorAll("#selectedItemsTable tr[data-legal]").forEach((row) => {
    const legal = (row.getAttribute("data-legal") || "").trim();
    if (legal) values.add(legal);
  });
  return Array.from(values);
}

function updateDistributeLegalPanel(modalElement) {
  const panel = modalElement.querySelector("#legalTransferPanel");
  const fromEl = modalElement.querySelector("#legalFromValue");
  const toEl = modalElement.querySelector("#legalToValue");
  const hint = modalElement.querySelector("#legalCrossHint");
  const cardFrom = modalElement.querySelector("#legalCardFrom");
  const cardTo = modalElement.querySelector("#legalCardTo");
  if (!panel || !fromEl || !toEl) return;

  const fromList = collectSourceLegalEntities(modalElement);
  const locationSelect = modalElement.querySelector("#distributeLocationSelect");
  const selectedOption = locationSelect?.selectedOptions?.[0];
  const toLegal = (selectedOption?.getAttribute("data-legal") || "").trim();

  const fromText = fromList.length ? fromList.join(", ") : "—";
  fromEl.textContent = fromText;
  fromEl.classList.toggle("is-empty", fromList.length === 0);

  toEl.textContent = toLegal || "—";
  toEl.classList.toggle("is-empty", !toLegal);

  // Обновляем столбец «Юр. лицо куда» в таблице
  modalElement.querySelectorAll("#selectedItemsTable .legal-to-chip").forEach((chip) => {
    if (toLegal) {
      chip.textContent = toLegal;
      chip.classList.remove("empty");
    } else {
      chip.textContent = "—";
      chip.classList.add("empty");
    }
  });

  const hasFrom = fromList.length > 0;
  const hasTo = !!toLegal;
  const isCross =
    hasFrom &&
    hasTo &&
    fromList.some((fromLegal) => fromLegal.toLowerCase() !== toLegal.toLowerCase());

  // Показываем панель, если есть хотя бы одно юр. лицо
  panel.classList.toggle("is-visible", hasFrom || hasTo);
  hint?.classList.toggle("is-visible", isCross);
  cardFrom?.classList.toggle("legal-card-cross", isCross);
  cardTo?.classList.toggle("legal-card-cross", isCross);

  // Всегда показываем оба столбца юр. лиц в таблице передачи
  modalElement.classList.add("has-dual-legal");
}




/**
 * Обработчик отправки формы распределения
 */
async function handleDistributeFormSubmit(modalElement) {
  const form = modalElement.querySelector("#distributeForm");
  const formData = getCollectFormData(form, Action.UPDATE);
  let tmc_ids = window.selectedTMCIds;
  formData['tmc_ids'] = JSON.stringify(tmc_ids);

  const fromRepair = (tmc_ids || []).some((id) => {
    const row = document.querySelector(`#inventoryTable tr.row-container[data-id="${id}"]`);
    return Number(row?.getAttribute("data-status")) === StatusItem.Repair;
  });
  const upd = (form.querySelector('[name="upd"]')?.value || "").trim();
  if (fromRepair && !upd) {
    showNotification(TypeMessage.notification, "Укажите № УПД для отправки из сервиса на объект");
    form.querySelector('[name="upd"]')?.focus();
    return;
  }

  try {
    const result = await executeEntityAction({
      action: Action.UPDATE,
      formData: formData,
      url: "/src/BusinessLogic/Actions/processCUDDistribute.php",
      successMessage: "ТМЦ успешно переданы",
    });

    // Обновляем статусы / локацию / юр. лицо в таблице ТМЦ
    if (tmc_ids) {
      const locationSelect = form.querySelector("#distributeLocationSelect");
      const userSelect = form.querySelector('select[name="user"]');
      const locationOpt = locationSelect?.selectedOptions?.[0];
      const userOpt = userSelect?.selectedOptions?.[0];

      const locationName = (
        locationOpt?.getAttribute("data-name") ||
        (locationOpt?.textContent || "").split("(")[0]
      ).trim();
      const legal = (locationOpt?.getAttribute("data-legal") || "").trim();
      const responsible = (userOpt?.textContent || "").trim();

      updateInventoryAfterTransfer(tmc_ids, {
        status: StatusItem.ConfirmItem,
        location: locationName,
        legal: legal,
        responsible: responsible,
      });

      const destUserId = userSelect?.value || "";
      const isAdmin = Number(window.currentUserStatus) === 0;
      const isDestUser = String(destUserId) === String(window.currentUserId || "");
      if (isAdmin || isDestUser) {
        if (typeof updateCounters === "function") {
          updateCounters({ confirmCount: tmc_ids.length });
        } else if (typeof window.updateCounters === "function") {
          window.updateCounters({ confirmCount: tmc_ids.length });
        }
      }

      window.removingSelection();
    }

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();



  } catch (error) {
    console.error("Ошибка при передаче ТМЦ:", error);
    showNotification(TypeMessage.error, "Ошибка при передаче ТМЦ: " + error.message);
  }
}
