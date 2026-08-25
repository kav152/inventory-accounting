import { showNotification } from './modals/setting.js';
import { TypeMessage } from '../src/constants/typeMessage.js';
import { Action } from '../src/constants/actions.js';
<<<<<<< HEAD
=======
import { StatusItem } from '../src/constants/statusItem.js';
>>>>>>> feature/local-updates-2026-08
import {
  executeEntityAction,
  getCollectFormData,
} from "./templates/entityActionTemplate.js";


(function () {
  async function deleteRow(id) {
    if (confirm("Вы уверены, что хотите переместить в корзину?")) {
      const row = document.querySelector(`.main-row[data-id="${id}"]`);
      const detailsRow = document.getElementById(`details-${id}`);

<<<<<<< HEAD
     /* if (row) row.style.display = "none";
      if (detailsRow) detailsRow.style.display = "none";

      // Получаем все данные из data-атрибутов
      const ID_TMC = row.getAttribute("data-id");
      const ID_Repair = row.getAttribute("data-id_Repair");

      const repairItemData = {
        ID_TMC: parseInt(ID_TMC),
        ID_Repair: parseInt(ID_Repair),
      };



      try {
        // Отправка на сервер
        const result = await executeEntityAction({
          action: Action.UPDATE,
          formData: formData,
          url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
          successMessage: "ТМЦ успешно сохранен",
        });

        if(result)
        {
          row.remove();
          if (detailsRow) detailsRow.remove();

        }


      }
      catch (error) {
        console.error("Ошибка сохранения ТМЦ:", error);
        showNotification(TypeMessage.error, "Ошибка при перемещении в корзину: ". error);
      }*/

=======
>>>>>>> feature/local-updates-2026-08
      try {
        const formData = new FormData();
        formData.append("ID_TMC", id);
        formData.append("NameTMC", row.dataset.name);
<<<<<<< HEAD
        //console.log(id);
        //console.log(row.dataset.name);
=======
>>>>>>> feature/local-updates-2026-08
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processRepairInBasket.php",
          {
            method: "POST",
            body: formData,
          }
        );
        const data = await response.json();
        if (data.success) {

          row.remove();
          if (detailsRow) detailsRow.remove();

          showNotification(TypeMessage.success, data.message);
          let sum = row.dataset.name;
          updateTotalSum(sum);


        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, error);
      }

      // Пересчитываем общую сумму
      applyFilters();
    }
  }
<<<<<<< HEAD
  

  async function returnToWorkTMC() {
    if (!selectedRow) {
      showNotification(TypeMessage.notification, "Пожалуйста, выберите запись для редактирования.");
      return;
    }

    const status = selectedRow.getAttribute('data-status');
    const id = selectedRow.getAttribute('data-id');
    console.log(status);
    if (status != StatusItem.WrittenOff) {
      showNotification(TypeMessage.notification, "Выбирите списанные ТМЦ");
      return;
    }

    if (confirm("Вы уверены, что хотите вернуть ТМЦ в работу?")) {
      try {

        let action = 'cancelWriteOff';
        const response = await fetch(
          `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${id}&action=${action}`
        );
        const data = await response.json();
        if (data.success) {
          showNotification(TypeMessage.success, 'Списаное ТМЦ возвращено на склад');
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, error);
      }
=======

  async function deleteRepairLine(repairId, tmcId) {
    if (!confirm("Удалить эту запись ремонта (в корзину)?")) {
      return;
    }

    const line = document.querySelector(`.repair-line[data-repair-id="${repairId}"]`);
    const detailsRow = document.getElementById(`details-${tmcId}`);
    const mainRow = document.querySelector(`.main-row[data-id="${tmcId}"]`);

    try {
      const formData = new FormData();
      formData.append("ID_Repair", repairId);
      formData.append("ID_TMC", tmcId);
      if (mainRow) {
        formData.append("NameTMC", mainRow.dataset.name || "");
      }

      const response = await fetch(
        "/src/BusinessLogic/ActionsTMC/processRepairInBasket.php",
        {
          method: "POST",
          body: formData,
        }
      );
      const data = await response.json();
      if (!data.success) {
        showNotification(TypeMessage.error, data.message || "Ошибка удаления");
        return;
      }

      if (line) line.remove();

      const remaining = detailsRow
        ? detailsRow.querySelectorAll(".repair-line").length
        : 0;
      const countEl = detailsRow?.querySelector(".details-count");
      if (countEl) {
        countEl.textContent = `${remaining} записей`;
      }

      // Если записей не осталось — убираем всю строку ТМЦ
      if (remaining === 0) {
        mainRow?.remove();
        detailsRow?.remove();
      }

      showNotification(TypeMessage.success, data.message);
      if (typeof applyFilters === "function") {
        applyFilters();
      }
    } catch (error) {
      console.error("Error:", error);
      showNotification(TypeMessage.error, String(error));
    }
  }

  async function cancelWriteOffById(id) {
    const response = await fetch(
      `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${encodeURIComponent(id)}&action=cancelWriteOff`
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Не удалось вернуть ТМЦ");
    }
    return data;
  }

  async function returnToWorkTMC(idFromBtn = null) {
    const id = idFromBtn || (typeof selectedRow !== "undefined" && selectedRow
      ? selectedRow.getAttribute("data-id")
      : null);
    const row = id
      ? document.querySelector(`.main-row[data-id="${id}"]`)
      : (typeof selectedRow !== "undefined" ? selectedRow : null);

    if (!id) {
      showNotification(TypeMessage.notification, "Пожалуйста, выберите списанную запись.");
      return;
    }

    const statusAttr = row?.getAttribute("data-status");
    if (statusAttr != null && parseInt(statusAttr, 10) !== StatusItem.WrittenOff) {
      showNotification(TypeMessage.notification, "Выберите списанные ТМЦ");
      return;
    }

    if (!confirm("Вернуть ТМЦ из списания на склад?")) {
      return;
    }

    try {
      await cancelWriteOffById(id);

      const detailsRow = document.getElementById(`details-${id}`);
      row?.remove();
      detailsRow?.remove();

      const miniRow = document.querySelector(`#writtenOffMiniBody tr[data-id="${id}"]`);
      miniRow?.remove();
      const countEl = document.getElementById("writtenOffMiniCount");
      if (countEl && document.getElementById("writtenOffMiniBody")) {
        const left = document.querySelectorAll("#writtenOffMiniBody tr[data-id]").length;
        countEl.textContent = left + " записей";
      }

      showNotification(TypeMessage.success, "Списанное ТМЦ возвращено на склад");
      if (typeof applyFilters === "function") {
        applyFilters();
      }
      if (typeof updateInventoryStatus === "function") {
        updateInventoryStatus([id], StatusItem.NotDistributed);
      } else if (typeof window.updateInventoryStatus === "function") {
        window.updateInventoryStatus([id], StatusItem.NotDistributed);
      }
    } catch (error) {
      console.error("Error:", error);
      showNotification(TypeMessage.error, error.message || String(error));
>>>>>>> feature/local-updates-2026-08
    }
  }

  window.deleteRow = deleteRow;
<<<<<<< HEAD
  window.returnToWorkTMC = returnToWorkTMC;
  //window.initCardWriteOffHandlers = initCardWriteOffHandlers;
})();

=======
  window.deleteRepairLine = deleteRepairLine;
  window.returnToWorkTMC = returnToWorkTMC;
  window.cancelWriteOffById = cancelWriteOffById;
})();

async function writeOffToolDirect() {
  const rows = document.querySelectorAll("#inventoryTable tr.row-container.selected");
  if (!rows.length) {
    showNotification(TypeMessage.notification, "Выберите инструмент в таблице");
    return;
  }

  const blocked = [
    StatusItem.WrittenOff,
    StatusItem.Repair,
    StatusItem.ConfirmRepairTMC,
  ];
  const ids = [];
  const skipped = [];

  rows.forEach((row) => {
    const id = row.getAttribute("data-id");
    const status = parseInt(row.getAttribute("data-status"), 10);
    if (!id) return;
    if (blocked.includes(status)) {
      skipped.push(id);
      return;
    }
    ids.push(id);
  });

  if (ids.length === 0) {
    showNotification(
      TypeMessage.notification,
      "Среди выбранных нет ТМЦ для списания без сервиса (уже списаны или в ремонте)",
    );
    return;
  }

  const confirmText =
    ids.length === 1
      ? `Списать инструмент №${ids[0]} без отправки в сервис?`
      : `Списать ${ids.length} инструмент(ов) без отправки в сервис?`;
  if (!confirm(confirmText)) {
    return;
  }

  const reason =
    prompt("Причина списания", "Списание без отправки в сервис") ||
    "Списание без отправки в сервис";

  try {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processDirectWriteOff.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ tmc_ids: ids, reason: reason.trim() }),
      },
    );
    const data = await response.json();
    if (!data.success) {
      showNotification(TypeMessage.error, data.message || "Ошибка списания");
      return;
    }

    showNotification(TypeMessage.success, data.message);
    const written = data.written || ids;
    if (typeof updateInventoryStatus === "function") {
      updateInventoryStatus(written, StatusItem.WrittenOff);
    } else if (typeof window.updateInventoryStatus === "function") {
      window.updateInventoryStatus(written, StatusItem.WrittenOff);
    }

    const atWork = Number(data.atWorkCount || 0);
    if (atWork && typeof updateCounters === "function") {
      updateCounters({ brigadesToItemsCount: -atWork });
    } else if (atWork && typeof window.updateCounters === "function") {
      window.updateCounters({ brigadesToItemsCount: -atWork });
    }

    if (typeof window.removingSelection === "function") {
      window.removingSelection();
    }
    if (skipped.length) {
      showNotification(
        TypeMessage.notification,
        `Пропущено (уже в сервисе/списано): ${skipped.join(", ")}`,
      );
    }
  } catch (error) {
    console.error(error);
    showNotification(TypeMessage.error, error.message || String(error));
  }
}

window.writeOffToolDirect = writeOffToolDirect;

>>>>>>> feature/local-updates-2026-08

export function initCardWriteOffHandlers(modalElement) {
    const form = document.getElementById("edit_write_off");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

<<<<<<< HEAD
      //const form = modalElement.querySelector("#editWriteOffModal");
=======
>>>>>>> feature/local-updates-2026-08
      const repairs = modalElement.querySelectorAll(".repair-item");
      const formData = new FormData();
      repairs.forEach((repair, index) => {
        formData.append(
          `repairs[${index}][ID_Repair]`,
          repair.dataset.repairId
        );
        formData.append(
          `repairs[${index}][ID_TMC]`,
          repair.querySelector(".id-tmc").value
        );
        formData.append(
          `repairs[${index}][InvoiceNumber]`,
          repair.querySelector(".invoice-number").value
        );
        formData.append(
<<<<<<< HEAD
=======
          `repairs[${index}][UPD]`,
          repair.querySelector(".upd-number")?.value || ""
        );
        formData.append(
>>>>>>> feature/local-updates-2026-08
          `repairs[${index}][RepairCost]`,
          repair.querySelector(".repair-cost").value
        );
        formData.append(
          `repairs[${index}][DateToService]`,
          repair.querySelector(".date-to-service").value
        );
        formData.append(
          `repairs[${index}][DateReturnService]`,
          repair.querySelector(".date-return-service").value
        );
        formData.append(
          `repairs[${index}][RepairDescription]`,
          repair.querySelector(".repair-description").value
        );
        formData.append(
          `repairs[${index}][IDLocation]`,
          repair.querySelector(".idLocation").value
        );
        formData.append(`repairs[${index}][inBasket]`, "0");
      });

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processUpdateRepairs.php",
          {
            method: "POST",
            body: formData,
          }
        );
        const data = await response.json();

        if (data.success) {
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();
          window.needFullReload = true;

          showNotification(TypeMessage.success, data.message);

          if (typeof handleSuccess === "undefined") {
            console.warn(
              "handleSuccess не найдена. Ожидание загрузки updateFunctions.js"
            );
          }

          if (typeof handleSuccess === "function") {
            handleSuccess();
          } else if (typeof window.handleSuccess === "function") {
            console.error("Функция handleSuccess недоступна");
            window.handleSuccess();
          }
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Ошибка отправки:", error);
        showNotification(TypeMessage.error, "Ошибка сети");
      }
    };
  }
