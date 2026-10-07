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
  const dateToService = (row.querySelector(".repair-date-to-input")?.value || "").trim();
  const dateReturnService = (row.querySelector(".repair-date-return-input")?.value || "").trim();

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
      dateToService,
      dateReturnService,
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

/** Сохранить даты строки истории ремонта (дд.мм.гггг) */
export async function saveRepairLineDates(row) {
  if (!row || row.dataset.savingDates === "1") return;
  const repairId = parseInt(row.dataset.repairId || "0", 10);
  const tmcId = parseInt(row.dataset.tmcId || "0", 10);
  if (!repairId) return;

  const dateToInput = row.querySelector(".repair-date-to-input");
  const dateRetInput = row.querySelector(".repair-date-return-input");
  const dateTo = (dateToInput?.value || "").trim();
  const dateRet = (dateRetInput?.value || "").trim();
  const prev = `${row.dataset.savedDateTo || ""}|${row.dataset.savedDateReturn || ""}`;
  const next = `${dateTo}|${dateRet}`;
  if (prev === next) return;

  // неполная дата при вводе — ждём окончательный ввод
  const dateRe = /^\d{1,2}\.\d{1,2}\.(\d{2}|\d{4})$/;
  if (dateTo !== "" && !dateRe.test(dateTo)) return;
  if (dateRet !== "" && !dateRe.test(dateRet)) return;

  const locationId = parseInt(row.dataset.locationId || "0", 10);

  const formData = new FormData();
  formData.append("repairs[0][ID_Repair]", String(repairId));
  formData.append("repairs[0][ID_TMC]", String(tmcId));
  formData.append("repairs[0][IDLocation]", String(locationId));
  if (dateTo !== "") {
    formData.append("repairs[0][DateToService]", dateTo);
  }
  formData.append("repairs[0][DateReturnService]", dateRet);

  row.dataset.savingDates = "1";
  try {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processUpdateRepairs.php",
      { method: "POST", body: formData },
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Не удалось сохранить даты");
    }
    row.dataset.savedDateTo = dateTo;
    row.dataset.savedDateReturn = dateRet;
    showNotification(TypeMessage.success, "Даты сохранены");
  } catch (error) {
    showNotification(TypeMessage.error, error.message || String(error));
  } finally {
    row.dataset.savingDates = "0";
  }
}

window.saveRepairLineDates = saveRepairLineDates;

/** Сохранить счёт / УПД / стоимость строки истории (после blur) */
export async function saveRepairLineFields(row) {
  if (!row) return;
  const repairId = parseInt(row.dataset.repairId || "0", 10);
  const tmcId = parseInt(row.dataset.tmcId || "0", 10);
  if (!repairId) return;

  const invoice = (row.querySelector(".repair-invoice-input")?.value || "").trim();
  const upd = (row.querySelector(".repair-upd-input")?.value || "").trim();
  const cost = String(row.querySelector(".repair-cost-input")?.value ?? "0").trim();
  const prev = `${row.dataset.savedInvoice || ""}|${row.dataset.savedUpd || ""}|${row.dataset.savedCost || ""}`;
  const next = `${invoice}|${upd}|${cost}`;
  if (prev === next) return;

  const locationId = parseInt(row.dataset.locationId || "0", 10);
  const formData = new FormData();
  formData.append("repairs[0][ID_Repair]", String(repairId));
  formData.append("repairs[0][ID_TMC]", String(tmcId));
  formData.append("repairs[0][IDLocation]", String(locationId));
  formData.append("repairs[0][InvoiceNumber]", invoice);
  formData.append("repairs[0][UPD]", upd);
  formData.append("repairs[0][RepairCost]", cost);

  try {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processUpdateRepairs.php",
      { method: "POST", body: formData },
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Не удалось сохранить");
    }
    row.dataset.savedInvoice = invoice;
    row.dataset.savedUpd = upd;
    row.dataset.savedCost = cost;
    showNotification(TypeMessage.success, "Данные сохранены");
  } catch (error) {
    showNotification(TypeMessage.error, error.message || String(error));
  }
}

window.saveRepairLineFields = saveRepairLineFields;

async function decideProposeWriteOff(action, button) {
  const tmcId = parseInt(button.getAttribute("data-tmc-id") || "0", 10);
  const repairId = parseInt(button.getAttribute("data-repair-id") || "0", 10);
  if (!tmcId) return;

  if (action === "approve") {
    if (!confirm(`Утвердить списание ТМЦ №${tmcId}?`)) return;
  } else {
    if (!confirm(`Отклонить предложение списания ТМЦ №${tmcId}?`)) return;
  }

  const reason =
    action === "reject"
      ? prompt("Причина отклонения", "Отклонено предложение списания") ||
        "Отклонено предложение списания"
      : "";

  try {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processProposeWriteOffDecision.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action, tmcId, repairId, reason }),
      },
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Ошибка");
    }
    showNotification(TypeMessage.success, data.message);
    window.location.reload();
  } catch (error) {
    showNotification(TypeMessage.error, error.message || String(error));
  }
}

window.approveProposeWriteOff = (btn) => decideProposeWriteOff("approve", btn);
window.rejectProposeWriteOff = (btn) => decideProposeWriteOff("reject", btn);

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
  const proposeIds = [];
  const skipped = [];

  rows.forEach((row) => {
    const id = row.getAttribute("data-id");
    const status = parseInt(row.getAttribute("data-status"), 10);
    if (!id) return;
    if (blocked.includes(status)) {
      skipped.push(id);
      return;
    }
    if (status === StatusItem.ProposeWriteOff) {
      proposeIds.push(id);
    }
    ids.push(id);
  });

  if (ids.length === 0) {
    showNotification(
      TypeMessage.notification,
      "Среди выбранных нет ТМЦ для списания (уже списаны или в ремонте)",
    );
    return;
  }

  const hasPropose = proposeIds.length > 0;
  const confirmText =
    ids.length === 1
      ? hasPropose
        ? `Утвердить предложение и списать инструмент №${ids[0]}?`
        : `Списать инструмент №${ids[0]} без отправки в сервис?`
      : hasPropose
        ? `Списать ${ids.length} инструмент(ов)? (в т.ч. утвердить предложения: ${proposeIds.length})`
        : `Списать ${ids.length} инструмент(ов) без отправки в сервис?`;
  if (!confirm(confirmText)) {
    return;
  }

  const reason =
    prompt(
      hasPropose ? "Комментарий к списанию" : "Причина списания",
      hasPropose
        ? "Утверждено списание по предложению кладовщика"
        : "Списание без отправки в сервис",
    ) ||
    (hasPropose
      ? "Утверждено списание по предложению кладовщика"
      : "Списание без отправки в сервис");

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

    if (proposeIds.length && typeof window.updateCounters === "function") {
      window.updateCounters({ proposeWriteOffCount: -proposeIds.length });
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

async function proposeWriteOff() {
  const rows = document.querySelectorAll("#inventoryTable tr.row-container.selected");
  if (!rows.length) {
    showNotification(TypeMessage.notification, "Выберите инструмент в таблице");
    return;
  }

  const blocked = [
    StatusItem.WrittenOff,
    StatusItem.Repair,
    StatusItem.ConfirmRepairTMC,
    StatusItem.ProposeWriteOff,
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
      "Среди выбранных нет ТМЦ для предложения списания",
    );
    return;
  }

  const confirmText =
    ids.length === 1
      ? `Отправить админу предложение списать инструмент №${ids[0]}?`
      : `Отправить админу предложение списать ${ids.length} инструмент(ов)?`;
  if (!confirm(confirmText)) {
    return;
  }

  const reason =
    prompt("Комментарий для админа (причина списания)", "Предложение списания") ||
    "Предложение списания";

  try {
    const response = await fetch(
      "/src/BusinessLogic/ActionsTMC/processProposeWriteOff.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ tmc_ids: ids, reason: reason.trim() }),
      },
    );
    const data = await response.json();
    if (!data.success) {
      showNotification(TypeMessage.error, data.message || "Ошибка отправки");
      return;
    }

    showNotification(TypeMessage.success, data.message);
    const proposed = data.proposed || ids;
    if (typeof updateInventoryStatus === "function") {
      updateInventoryStatus(proposed, StatusItem.ProposeWriteOff);
    } else if (typeof window.updateInventoryStatus === "function") {
      window.updateInventoryStatus(proposed, StatusItem.ProposeWriteOff);
    }

    if (typeof window.removingSelection === "function") {
      window.removingSelection();
    }
    if (skipped.length) {
      showNotification(
        TypeMessage.notification,
        `Пропущено: ${skipped.join(", ")}`,
      );
    }
  } catch (error) {
    console.error(error);
    showNotification(TypeMessage.error, error.message || String(error));
  }
}

window.proposeWriteOff = proposeWriteOff;

function escHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function formatMoneyRu(value) {
  return new Intl.NumberFormat("ru-RU", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value) || 0);
}

export async function openRepairHistory(tmcId) {
  const id = parseInt(tmcId, 10);
  if (!id) {
    showNotification(TypeMessage.notification, "Не указан ТМЦ");
    return;
  }

  const modalEl = document.getElementById("repairHistoryModal");
  const body = document.getElementById("repairHistoryModalBody");
  if (!modalEl || !body) {
    showNotification(TypeMessage.error, "Модалка истории не найдена");
    return;
  }

  body.innerHTML = '<div class="text-muted">Загрузка…</div>';
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  try {
    const response = await fetch(
      `/src/BusinessLogic/ActionsTMC/processGetRepairHistory.php?tmcId=${id}`,
    );
    const payload = await response.json();
    if (!payload.success) {
      throw new Error(payload.message || "Не удалось загрузить историю");
    }

    const data = payload.data || {};
    const tmc = data.tmc || {};
    const repairs = Array.isArray(data.repairs) ? data.repairs : [];
    const operations = Array.isArray(data.operations) ? data.operations : [];
    const total = data.totalCost || 0;

    const repairRows = repairs.length
      ? repairs
          .map(
            (r) => `<tr>
          <td>${escHtml(r.dateTo || "—")}</td>
          <td>${escHtml(r.dateReturn || "—")}</td>
          <td>${escHtml(r.service || "—")}</td>
          <td>${escHtml(r.invoice || "—")}</td>
          <td>${escHtml(r.upd || "—")}</td>
          <td class="text-end">${formatMoneyRu(r.cost)} ₽</td>
          <td>${escHtml(r.note || "—")}</td>
        </tr>`,
          )
          .join("")
      : '<tr><td colspan="7" class="text-center text-muted">Записей ремонта нет</td></tr>';

    const opRows = operations.length
      ? operations
          .map(
            (o) => `<tr>
          <td>${escHtml(o.date || "—")}</td>
          <td>${escHtml(o.comment || "—")}</td>
          <td>${escHtml(o.user || "—")}</td>
        </tr>`,
          )
          .join("")
      : '<tr><td colspan="3" class="text-center text-muted">Операций по ремонту/списанию нет</td></tr>';

    body.innerHTML = `
      <div class="mb-3">
        <div class="fw-semibold">№${escHtml(tmc.id)} — ${escHtml(tmc.name || "")}</div>
        <div class="text-muted small">
          ${escHtml(tmc.brand || "—")} · сер. ${escHtml(tmc.serial || "—")} · ${escHtml(tmc.location || "—")}
          · статус: ${escHtml(tmc.statusText || "—")}
        </div>
      </div>

      <div class="alert alert-light border d-flex justify-content-between align-items-center py-2">
        <span>Записей ремонта: <strong>${repairs.length}</strong></span>
        <span>Всего потрачено: <strong>${formatMoneyRu(total)} ₽</strong></span>
      </div>

      <h6 class="mt-3">Сдача / приём (ремонты)</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Отправка</th>
              <th>Возврат</th>
              <th>Сервис</th>
              <th>Счёт</th>
              <th>УПД</th>
              <th>Сумма</th>
              <th>Примечание</th>
            </tr>
          </thead>
          <tbody>${repairRows}</tbody>
        </table>
      </div>

      <h6>Операции (история решений)</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Дата</th>
              <th>Событие</th>
              <th>Ответственный</th>
            </tr>
          </thead>
          <tbody>${opRows}</tbody>
        </table>
      </div>
    `;
  } catch (error) {
    body.innerHTML = `<div class="alert alert-danger mb-0">${escHtml(error.message || String(error))}</div>`;
    showNotification(TypeMessage.error, error.message || String(error));
  }
}

window.openRepairHistory = openRepairHistory;

export function initCardWriteOffHandlers(modalElement) {
    const form =
      modalElement?.querySelector?.("#editWriteOffModal") ||
      document.getElementById("editWriteOffModal") ||
      document.getElementById("edit_write_off");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

      const repairs = (modalElement || document).querySelectorAll(".repair-item");
      const formData = new FormData();
      repairs.forEach((repair, index) => {
        const repairId = repair.dataset.repairId || "0";
        const dateTo = (repair.querySelector(".date-to-service")?.value || "").trim();
        const dateRet = (repair.querySelector(".date-return-service")?.value || "").trim();
        formData.append(`repairs[${index}][ID_Repair]`, repairId);
        formData.append(
          `repairs[${index}][ID_TMC]`,
          repair.querySelector(".id-tmc")?.value || "0"
        );
        formData.append(
          `repairs[${index}][InvoiceNumber]`,
          repair.querySelector(".invoice-number")?.value || ""
        );
        // UPD убрали — только счёт
        formData.append(
          `repairs[${index}][RepairCost]`,
          repair.querySelector(".repair-cost")?.value || "0"
        );
        if (dateTo !== "") {
          formData.append(`repairs[${index}][DateToService]`, dateTo);
        }
        formData.append(`repairs[${index}][DateReturnService]`, dateRet);
        formData.append(
          `repairs[${index}][RepairDescription]`,
          repair.querySelector(".repair-description")?.value || ""
        );
        const locId = parseInt(repair.querySelector(".idLocation")?.value || "0", 10);
        if (locId > 0) {
          formData.append(`repairs[${index}][IDLocation]`, String(locId));
        }
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
          // Сразу синхронизируем строку истории — без полной перезагрузки страницы
          repairs.forEach((repairEl) => {
            const repairId = repairEl.dataset.repairId || "";
            const row = document.querySelector(
              `tr.repair-line[data-repair-id="${repairId}"]`,
            );
            if (!row) return;
            const invoice =
              repairEl.querySelector(".invoice-number")?.value?.trim() || "";
            const upd =
              repairEl.querySelector(".upd-number")?.value?.trim() || "";
            const cost =
              repairEl.querySelector(".repair-cost")?.value?.trim() || "0";
            const dateTo =
              repairEl.querySelector(".date-to-service")?.value?.trim() || "";
            const dateRet =
              repairEl.querySelector(".date-return-service")?.value?.trim() ||
              "";
            const invoiceInput = row.querySelector(".repair-invoice-input");
            const updInput = row.querySelector(".repair-upd-input");
            const costInput = row.querySelector(".repair-cost-input");
            const dateToInput = row.querySelector(".repair-date-to-input");
            const dateRetInput = row.querySelector(".repair-date-return-input");
            if (invoiceInput) invoiceInput.value = invoice;
            if (updInput) updInput.value = upd;
            if (costInput) costInput.value = cost;
            if (dateToInput) dateToInput.value = dateTo;
            if (dateRetInput) dateRetInput.value = dateRet;
            row.dataset.savedInvoice = invoice;
            row.dataset.savedUpd = upd;
            row.dataset.savedCost = cost;
            row.dataset.savedDateTo = dateTo;
            row.dataset.savedDateReturn = dateRet;
          });

          const modal = bootstrap.Modal.getInstance(modalElement);
          modal?.hide();
          showNotification(TypeMessage.success, data.message);
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Ошибка отправки:", error);
        showNotification(TypeMessage.error, "Ошибка сети");
      }
    };
  }
