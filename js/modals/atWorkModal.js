import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "./setting.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { ServiceStatus } from "../../src/constants/statusService.js";

<<<<<<< HEAD
=======
function getServiceFormRow(tmcId) {
  return document.getElementById(`serviceForm-${tmcId}`)?.closest("tr");
}

function getWriteOffFormRow(tmcId) {
  return document.getElementById(`writeOffForm-${tmcId}`)?.closest("tr");
}

function removeAtWorkItemFromModal(tmcId) {
  const atWorkModal = document.getElementById("atWorkModal");
  if (!atWorkModal) return;

  const itemRow = atWorkModal.querySelector(`tr.row-container1[data-id="${tmcId}"]`);
  if (!itemRow) return;

  const formRow = itemRow.nextElementSibling;
  const brigadeGroup = itemRow.closest(".brigade-group");

  itemRow.remove();
  if (formRow?.classList.contains("service-form-row")) {
    formRow.remove();
  }

  if (brigadeGroup) {
    const countEl = brigadeGroup.querySelector(".items-count");
    if (countEl) {
      const newCount = Math.max(0, parseInt(countEl.textContent, 10) - 1);
      countEl.textContent = newCount;
    }
  }

  const remainingRows = atWorkModal.querySelectorAll("tr.row-container1");
  if (remainingRows.length === 0) {
    const modalInstance = bootstrap.Modal.getInstance(atWorkModal);
    modalInstance?.hide();
  }
}

>>>>>>> source/feature/local-updates-2026-08
/**
 * Отправить в сервис
 * @param {*} tmcId
 */
async function sendServiceForm(tmcId) {
<<<<<<< HEAD
  const items = [];
  const formRow = document
    .querySelector(`form[data-tmc-id="${tmcId}"]`)
    ?.closest("tr");
  const reason = formRow.querySelector('textarea[name="reason"]').value.trim();
=======
  const form = document.querySelector(`form.service-form[data-tmc-id="${tmcId}"]`);
  if (!form) return;

  const reason = form.querySelector('textarea[name="reason"]').value.trim();
>>>>>>> source/feature/local-updates-2026-08
  if (reason === "") {
    showNotification(TypeMessage.error, `Поле "Причина ремонта" обязательно для заполнения`);
    return;
  }
<<<<<<< HEAD
  items.push({ id: tmcId, reason: reason });

=======

  const items = [{ id: tmcId, reason: reason }];
>>>>>>> source/feature/local-updates-2026-08
  const statusService = ServiceStatus.sendService;

  const response = await fetch(
    "/src/BusinessLogic/ActionsTMC/processSendToService.php",
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        items: items,
        statusService: statusService,
      }),
    },
  );

  const data = await response.json();
  if (data.success) {
<<<<<<< HEAD

    if (formRow) {
      /* console.log(
         `Отправляем тмц в ремонт его ид="${tmcId}", причина -  ${reason}`,
       );*/

      const tableBody = formRow.closest("tbody");
      //console.log(tableBody);

      // Находим и удаляем строку по data-id
      const rowToDelete = tableBody.querySelector(`tr[data-id="${tmcId}"]`);
      if (rowToDelete) {
        rowToDelete.remove();
        hideServiceForm(tmcId);
      } else {
        console.error(`Строка с data-id ${tmcId} не найдена`);
      }

      updateInventoryStatus([tmcId], StatusItem.ConfirmRepairTMC);
      updateCounters({ brigadesToItemsCount: -1 });
    }
  }
}

// Показывает форму отправки в сервис для конкретного ТМЦ
function showServiceForm(button, tmcId) {
  // Предотвращаем выделение строки
  event.stopPropagation();

  // Скрываем все открытые формы
  hideAllServiceForms();

  // Находим строку с формой
  const formRow = button.closest("tr").nextElementSibling;
  if (formRow) {
    formRow.style.display = "table-row";
    const formSection = document.getElementById(`serviceForm-${tmcId}`);
    if (formSection) {
      formSection.classList.remove("collapsed");
      formSection.classList.add("expanded");
      // Фокус на поле ввода
      const textarea = formSection.querySelector("textarea");
      if (textarea) {
        setTimeout(() => textarea.focus(), 100);
      }
=======
    removeAtWorkItemFromModal(tmcId);
    updateInventoryStatus([tmcId], StatusItem.ConfirmRepairTMC);
    updateCounters({ brigadesToItemsCount: -1, confirmRepairCount: 1 });
  } else {
    showNotification(TypeMessage.error, data.message || "Ошибка при отправке в сервис");
  }
}

async function sendWriteOffForm(tmcId) {
  const form = document.querySelector(`form.writeoff-form[data-tmc-id="${tmcId}"]`);
  if (!form) return;

  const locationField = form.elements.IDLocation;
  let locationId = locationField?.value || "";
  if (!locationId || locationId === "0") {
    showNotification(TypeMessage.error, 'Укажите организацию или локацию для списания');
    locationField?.focus();
    return;
  }

  const invoice = (form.elements.InvoiceNumber?.value || "").trim() || "Без счета";
  const reason = (form.elements.RepairDescription?.value || "").trim() || "Списание без отправки в сервис";
  const cost = form.elements.RepairCost?.value || "0";

  const formData = {
    statusEntity: Action.CREATE,
    action: "writeOff",
    ID_TMC: tmcId,
    IDLocation: locationId,
    InvoiceNumber: invoice,
    UPD: form.elements.UPD?.value || "",
    RepairCost: cost,
    RepairDescription: reason,
  };

  try {
    const result = await executeEntityAction({
      action: Action.CREATE,
      formData: formData,
      url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
      successMessage: "ТМЦ списано",
    });

    if (result.resultEntity) {
      removeAtWorkItemFromModal(tmcId);
      updateInventoryStatus([tmcId], StatusItem.WrittenOff);
      updateCounters({ brigadesToItemsCount: -1 });
      refreshCardHistoryPanel(tmcId);
    }
  } catch (error) {
    console.error("Ошибка списания:", error);
    showNotification(TypeMessage.error, `Ошибка при списании: ${error.message}`);
  }
}

async function quickWriteOff(button, tmcId) {
  if (!confirm("Списать ТМЦ полностью? При необходимости его можно вернуть из списка списанных.")) {
    return;
  }

  let locationId = button?.getAttribute("data-location-id") || "";
  const form = document.querySelector(`form.writeoff-form[data-tmc-id="${tmcId}"]`);
  if ((!locationId || locationId === "0") && form?.elements?.IDLocation) {
    locationId = form.elements.IDLocation.value;
  }
  if (!locationId || locationId === "0") {
    const firstOpt = form?.elements?.IDLocation?.querySelector('option[value]:not([value=""]):not([value="0"])');
    locationId = firstOpt?.value || "";
  }
  if (!locationId || locationId === "0") {
    showNotification(TypeMessage.error, "Не удалось определить локацию для списания");
    return;
  }

  const formData = {
    statusEntity: Action.CREATE,
    action: "writeOff",
    ID_TMC: tmcId,
    IDLocation: locationId,
    InvoiceNumber: "Без счета",
    UPD: "",
    RepairCost: 0,
    RepairDescription: "Списание без отправки в сервис",
  };

  try {
    const result = await executeEntityAction({
      action: Action.CREATE,
      formData: formData,
      url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
      successMessage: "ТМЦ списано",
    });

    if (result.resultEntity) {
      removeAtWorkItemFromModal(tmcId);
      updateInventoryStatus([tmcId], StatusItem.WrittenOff);
      updateCounters({ brigadesToItemsCount: -1 });
      refreshCardHistoryPanel(tmcId);
    }
  } catch (error) {
    console.error("Ошибка списания:", error);
    showNotification(TypeMessage.error, `Ошибка при списании: ${error.message}`);
  }
}

function refreshCardHistoryPanel(tmcId) {
  const resultContainer = document.getElementById("resultContainer");
  if (!resultContainer || !tmcId) return;
  fetch(`/src/View/cardItem.php?id=${encodeURIComponent(tmcId)}`)
    .then((response) => response.text())
    .then((html) => {
      resultContainer.innerHTML = html;
    })
    .catch(() => {});
}

// Показывает форму отправки в сервис для конкретного ТМЦ
function showServiceForm(button, tmcId) {
  event.stopPropagation();
  hideAllItemActionForms();

  const formRow = button.closest("tr").nextElementSibling;
  if (!formRow) return;

  formRow.style.display = "table-row";

  const serviceSection = document.getElementById(`serviceForm-${tmcId}`);
  const writeOffSection = document.getElementById(`writeOffForm-${tmcId}`);

  if (writeOffSection) {
    writeOffSection.classList.remove("expanded");
    writeOffSection.classList.add("collapsed");
  }

  if (serviceSection) {
    serviceSection.classList.remove("collapsed");
    serviceSection.classList.add("expanded");
    const textarea = serviceSection.querySelector("textarea");
    if (textarea) {
      setTimeout(() => textarea.focus(), 100);
    }
  }
}

function showWriteOffForm(button, tmcId) {
  event.stopPropagation();
  hideAllItemActionForms();

  const formRow = button.closest("tr").nextElementSibling;
  if (!formRow) return;

  formRow.style.display = "table-row";

  const serviceSection = document.getElementById(`serviceForm-${tmcId}`);
  const writeOffSection = document.getElementById(`writeOffForm-${tmcId}`);

  if (serviceSection) {
    serviceSection.classList.remove("expanded");
    serviceSection.classList.add("collapsed");
  }

  if (writeOffSection) {
    writeOffSection.classList.remove("collapsed");
    writeOffSection.classList.add("expanded");
    const textarea = writeOffSection.querySelector('textarea[name="RepairDescription"]');
    if (textarea) {
      setTimeout(() => textarea.focus(), 100);
>>>>>>> source/feature/local-updates-2026-08
    }
  }
}

// Скрывает форму отправки в сервис
function hideServiceForm(tmcId) {
<<<<<<< HEAD
  const formRow = document
    .querySelector(`form[data-tmc-id="${tmcId}"]`)
    ?.closest("tr");
  if (formRow) {
    const formSection = formRow.querySelector(".service-form-section");
    //const reason = formRow.querySelector('textarea[name="reason"]').value.trim();
    //console.log(reason);

    if (formSection) {
      formSection.classList.remove("expanded");
      formSection.classList.add("collapsed");
      setTimeout(() => {
        formRow.style.display = "none";
        // Очищаем форму
        const form = formSection.querySelector("form");
        if (form) form.reset();
      }, 300);
    }
=======
  const formRow = getServiceFormRow(tmcId);
  if (!formRow) return;

  const formSection = formRow.querySelector(".service-form-section");
  if (formSection) {
    formSection.classList.remove("expanded");
    formSection.classList.add("collapsed");
    setTimeout(() => {
      formRow.style.display = "none";
      const form = formSection.querySelector("form");
      if (form) form.reset();
    }, 300);
  }
}

function hideWriteOffForm(tmcId) {
  const formRow = getWriteOffFormRow(tmcId);
  if (!formRow) return;

  const formSection = formRow.querySelector(".writeoff-form-section");
  if (formSection) {
    formSection.classList.remove("expanded");
    formSection.classList.add("collapsed");
    setTimeout(() => {
      formRow.style.display = "none";
      const form = formSection.querySelector("form");
      if (form) form.reset();
    }, 300);
>>>>>>> source/feature/local-updates-2026-08
  }
}

/**
 * Скрывает все открытые формы
 */
<<<<<<< HEAD
function hideAllServiceForms() {
  document
    .querySelectorAll(".service-form-section.expanded")
=======
function hideAllItemActionForms() {
  document
    .querySelectorAll(".service-form-section.expanded, .writeoff-form-section.expanded")
>>>>>>> source/feature/local-updates-2026-08
    .forEach((section) => {
      section.classList.remove("expanded");
      section.classList.add("collapsed");
      const formRow = section.closest("tr");
      setTimeout(() => {
        if (formRow) {
          formRow.style.display = "none";
          const form = section.querySelector("form");
          if (form) form.reset();
        }
      }, 300);
    });
}

<<<<<<< HEAD
=======
function hideAllServiceForms() {
  hideAllItemActionForms();
}

>>>>>>> source/feature/local-updates-2026-08
(function () {
  /**
   * Открыть модальное окно AtWorkModal
   * @param {Action} action - действие (CREATE, UPDATE, DELETE)
   */
  function openAtWorkModalModal() {
    openEntityModal(Action.CREATE, "atWorkModal");
  }

  // window.openAtWorkModalModal = openAtWorkModalModal;
  window.sendServiceForm = sendServiceForm;
  window.showServiceForm = showServiceForm;
  window.hideServiceForm = hideServiceForm;
<<<<<<< HEAD
=======
  window.sendWriteOffForm = sendWriteOffForm;
  window.quickWriteOff = quickWriteOff;
  window.showWriteOffForm = showWriteOffForm;
  window.hideWriteOffForm = hideWriteOffForm;
>>>>>>> source/feature/local-updates-2026-08
})();

let lastSelectedRow = null;

/**
 * Обработчик работы модального окна atWorkModal
 * @param {HTMLElement} modalElement
 */
export function initAtWorkModalModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleAtWorkModalFormSubmit(modalElement);
  });

  // Обработчик выделения строк
  modalElement.addEventListener("click", function (e) {
    const row = e.target.closest(".row-container1");
    if (!row) return;

    if (e.ctrlKey) {
      row.classList.toggle("selected");
      lastSelectedRow = row;
      return;
    }

    const tbody = row.closest("tbody");
    const rows = tbody.querySelectorAll(".row-container1");

    if (e.shiftKey && lastSelectedRow) {
      const startIdx = Array.from(rows).indexOf(lastSelectedRow);
      const endIdx = Array.from(rows).indexOf(row);
      const [start, end] = [
        Math.min(startIdx, endIdx),
        Math.max(startIdx, endIdx),
      ];

      rows.forEach((r) => r.classList.remove("selected"));
      for (let i = start; i <= end; i++) {
        rows[i].classList.add("selected");
      }
    } else {
      rows.forEach((r) => r.classList.remove("selected"));
      row.classList.add("selected");
      lastSelectedRow = row;
    }
  });

  initDynamicElements(modalElement);
}

/**
 * Инициализация динамических элементов
 */
function initDynamicElements(modalElement) {
  // Обработчик возврата ТМЦ
  let element = modalElement.querySelector("#btnReturnTMC");

  modalElement
    .querySelector("#btnReturnTMC")
<<<<<<< HEAD
    .addEventListener("click", function () {
=======
    .addEventListener("click", async function () {
>>>>>>> source/feature/local-updates-2026-08
      const selectedRows = modalElement.querySelectorAll(
        ".row-container1.selected",
      );
      if (selectedRows.length === 0) {
<<<<<<< HEAD
        alert("Выберите ТМЦ для возврата");
=======
        showNotification(TypeMessage.notification, "Выберите ТМЦ для возврата");
>>>>>>> source/feature/local-updates-2026-08
        return;
      }

      const tmcIds = Array.from(selectedRows).map((row) =>
        row.getAttribute("data-id"),
      );
<<<<<<< HEAD

      // Получаем brigade_id из первой выбранной строки
=======
>>>>>>> source/feature/local-updates-2026-08
      const brigadeId = selectedRows[0].getAttribute("data-brigade");

      const data = {
        statusEntity: statusEntity,
        tmc_ids: JSON.stringify(tmcIds),
        brigade_id: JSON.stringify(brigadeId),
      };

      try {
<<<<<<< HEAD
        const result = executeEntityAction({
=======
        await executeEntityAction({
>>>>>>> source/feature/local-updates-2026-08
          action: Action.UPDATE,
          formData: data,
          url: "/src/BusinessLogic/Actions/processCUDReturnFromWork.php",
          successMessage: "ТМЦ успешно переданы на склад",
        });

<<<<<<< HEAD
        let brigadesToItemsCount = 0;
        tmcIds.forEach((id) => {
          // Находим и удаляем строку по data-id
          const rowToDelete = modalElement.querySelector(`tr.row-container1[data-id="${id}"]`);
          //const rowToDelete = tableBody.querySelector(`tr[data-id="${id}"]`);
          if (rowToDelete) {
            rowToDelete.remove();
            brigadesToItemsCount = -1;
          } else {
            console.error(`Строка с data-id ${id} не найдена`);
=======
        let removed = 0;
        tmcIds.forEach((id) => {
          const rowToDelete = modalElement.querySelector(
            `tr.row-container1[data-id="${id}"]`,
          );
          if (rowToDelete) {
            rowToDelete.remove();
            removed += 1;
>>>>>>> source/feature/local-updates-2026-08
          }
        });

        updateInventoryStatus(tmcIds, StatusItem.Released);
<<<<<<< HEAD
        updateCounters({
          brigadesToItemsCount: brigadesToItemsCount,
        });
=======
        if (typeof window.updateCounters === "function") {
          window.updateCounters({ brigadesToItemsCount: -removed });
        } else if (typeof updateCounters === "function") {
          updateCounters({ brigadesToItemsCount: -removed });
        }
>>>>>>> source/feature/local-updates-2026-08
      } catch (error) {
        console.error("Error:", error);
        showNotification(
          TypeMessage.error,
          "Произошла ошибка при передаче ТМЦ на склад",
        );
      }
    });
}

/**
 * Обработчик отправки формы
 */
async function handleAtWorkModalFormSubmit(modalElement) {
  const form = modalElement.querySelector("#atWorkModalForm");
  const atWorkModalData = getCollectFormData(form, window.statusEntity);

  try {
    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: atWorkModalData,
      url: "/src/BusinessLogic/Actions/processCUDAtWorkModal.php",
      successMessage:
        "[EntityName] успешно " +
        (window.statusEntity === Action.CREATE ? "добавлен" : "обновлен"),
    });

    executeActionForCUD(
      window.statusEntity,
      result.resultEntity,
      "atWorkModalTableContainer",
      result.fields,
      "row-atWorkModal",
      "id",
    );

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}
