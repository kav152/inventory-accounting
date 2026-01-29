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

/**
 * Отправить в сервис
 * @param {*} tmcId
 */
async function sendServiceForm(tmcId) {
  const items = [];
  const formRow = document
    .querySelector(`form[data-tmc-id="${tmcId}"]`)
    ?.closest("tr");
  const reason = formRow.querySelector('textarea[name="reason"]').value.trim();
  if (reason === "") {
    showNotification(TypeMessage.error, `Поле "Причина ремонта" обязательно для заполнения`);
    return;
  }
  items.push({ id: tmcId, reason: reason });

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
    }
  }
}

// Скрывает форму отправки в сервис
function hideServiceForm(tmcId) {
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
  }
}

/**
 * Скрывает все открытые формы
 */
function hideAllServiceForms() {
  document
    .querySelectorAll(".service-form-section.expanded")
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
    .addEventListener("click", function () {
      const selectedRows = modalElement.querySelectorAll(
        ".row-container1.selected",
      );
      if (selectedRows.length === 0) {
        alert("Выберите ТМЦ для возврата");
        return;
      }

      const tmcIds = Array.from(selectedRows).map((row) =>
        row.getAttribute("data-id"),
      );

      // Получаем brigade_id из первой выбранной строки
      const brigadeId = selectedRows[0].getAttribute("data-brigade");

      const data = {
        statusEntity: statusEntity,
        tmc_ids: JSON.stringify(tmcIds),
        brigade_id: JSON.stringify(brigadeId),
      };

      try {
        const result = executeEntityAction({
          action: Action.UPDATE,
          formData: data,
          url: "/src/BusinessLogic/Actions/processCUDReturnFromWork.php",
          successMessage: "ТМЦ успешно переданы на склад",
        });

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
          }
        });

        updateInventoryStatus(tmcIds, StatusItem.Released);
        updateCounters({
          brigadesToItemsCount: brigadesToItemsCount,
        });
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
