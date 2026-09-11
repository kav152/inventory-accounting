import { showNotification } from './modals/setting.js';
import { TypeMessage } from '../src/constants/typeMessage.js';
import { Action } from '../src/constants/actions.js';
import { StatusItem } from '../src/constants/statusItem.js';
import { ServiceStatus } from '../src/constants/statusService.js';
import {
  executeEntityAction,
  getCollectFormData,
} from "./templates/entityActionTemplate.js";

function getWriteOffSelection(idFromBtn = null) {
  if (idFromBtn != null && idFromBtn !== "") {
    const row = document.querySelector(`.main-row[data-id="${idFromBtn}"]`);
    return { id: String(idFromBtn), row };
  }

  const row =
    window.selectedRow ||
    document.querySelector("#writeOffTable tbody tr.main-row.selected");

  if (!row) {
    return { id: null, row: null };
  }

  return { id: row.getAttribute("data-id"), row };
}

function removeWriteOffRow(id) {
  const row = document.querySelector(`.main-row[data-id="${id}"]`);
  const detailsRow = document.getElementById(`details-${id}`);
  row?.remove();
  detailsRow?.remove();
  if (window.selectedRow?.getAttribute("data-id") === String(id)) {
    window.selectedRow = null;
  }
}


(function () {
  async function deleteRow(id) {
    if (confirm("Вы уверены, что хотите переместить в корзину?")) {
      const row = document.querySelector(`.main-row[data-id="${id}"]`);
      const detailsRow = document.getElementById(`details-${id}`);

      try {
        const formData = new FormData();
        formData.append("ID_TMC", id);
        formData.append("NameTMC", row.dataset.name);
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

  async function returnFromRepairById(id, note = "Возврат из архива ремонта") {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processSendToService.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          statusService: ServiceStatus.returnService,
          items: [{ id: parseInt(id, 10), reason: note }],
        }),
      }
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Не удалось вернуть ТМЦ из ремонта");
    }
    return data;
  }

  async function returnToWorkTMC(idFromBtn = null) {
    const { id, row } = getWriteOffSelection(idFromBtn);

    if (!id) {
      showNotification(TypeMessage.notification, "Выберите запись в таблице");
      return;
    }

    const status = parseInt(row?.getAttribute("data-status"), 10);

    if (status === StatusItem.WrittenOff) {
      if (!confirm("Вернуть ТМЦ из списания на склад?")) {
        return;
      }

      try {
        await cancelWriteOffById(id);
        removeWriteOffRow(id);

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
      }
      return;
    }

    if (
      status === StatusItem.Repair ||
      status === StatusItem.ConfirmRepairTMC
    ) {
      if (!confirm("Вернуть ТМЦ из ремонта в работу?")) {
        return;
      }

      try {
        await returnFromRepairById(id);
        removeWriteOffRow(id);

        showNotification(TypeMessage.success, "ТМЦ возвращено из ремонта");
        if (typeof applyFilters === "function") {
          applyFilters();
        }
        if (typeof updateInventoryStatus === "function") {
          updateInventoryStatus([id], StatusItem.Released);
        } else if (typeof window.updateInventoryStatus === "function") {
          window.updateInventoryStatus([id], StatusItem.Released);
        }
        if (typeof window.updateCounters === "function") {
          window.updateCounters({ confirmRepairCount: -1 });
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, error.message || String(error));
      }
      return;
    }

    showNotification(
      TypeMessage.notification,
      "Возврат доступен только для списанных или находящихся в ремонте ТМЦ"
    );
  }

  window.deleteRow = deleteRow;
  window.deleteRepairLine = deleteRepairLine;
  window.returnToWorkTMC = returnToWorkTMC;
  window.cancelWriteOffById = cancelWriteOffById;
})();

async function postRepairApproval(payload) {
  const response = await fetch(
    "/src/BusinessLogic/ActionsTMC/processRepairApproval.php",
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    },
  );
  const data = await response.json();
  if (!data.success) {
    throw new Error(data.message || "Ошибка операции");
  }
  return data;
}

export async function approveRepairLine(button) {
  const row = button.closest(".repair-line");
  if (!row) return;

  const tmcId = parseInt(button.getAttribute("data-tmc-id") || row.dataset.tmcId, 10);
  const repairId = parseInt(button.getAttribute("data-repair-id") || row.dataset.repairId, 10);
  const invoice = (row.querySelector(".repair-invoice-input")?.value || "").trim();
  const upd = (row.querySelector(".repair-upd-input")?.value || "").trim();
  const repairCost = row.querySelector(".repair-cost-input")?.value || "0";
  const locationId = parseInt(row.dataset.locationId || "0", 10);

  if (!invoice) {
    showNotification(TypeMessage.notification, "Укажите № счёта");
    row.querySelector(".repair-invoice-input")?.focus();
    return;
  }

  try {
    await postRepairApproval({
      action: "approve",
      tmcId,
      repairId,
      invoiceNumber: invoice,
      updNumber: upd,
      repairCost,
      locationId,
    });
    showNotification(TypeMessage.success, "Ремонт согласован");
    window.location.reload();
  } catch (error) {
    showNotification(TypeMessage.error, error.message || "Ошибка согласования");
  }
}

export async function rejectRepairLine(button) {
  const row = button.closest(".repair-line");
  if (!row) return;

  const tmcId = parseInt(button.getAttribute("data-tmc-id") || row.dataset.tmcId, 10);
  const repairId = parseInt(button.getAttribute("data-repair-id") || row.dataset.repairId, 10);

  if (!confirm("Отказать в согласовании ремонта и вернуть ТМЦ на объект?")) {
    return;
  }

  try {
    await postRepairApproval({
      action: "reject",
      tmcId,
      repairId,
      reason: "Отказ в согласовании ремонта",
    });
    showNotification(TypeMessage.success, "В согласовании отказано");
    window.location.reload();
  } catch (error) {
    showNotification(TypeMessage.error, error.message || "Ошибка отказа");
  }
}

window.approveRepairLine = approveRepairLine;
window.rejectRepairLine = rejectRepairLine;

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


export function initCardWriteOffHandlers(modalElement) {
    const form = document.getElementById("edit_write_off");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

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
        // UPD убрали — только счёт
        formData.append(
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
