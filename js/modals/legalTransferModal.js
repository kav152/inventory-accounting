import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { Action } from "../../src/constants/actions.js";
import { executeEntityAction } from "../templates/entityActionTemplate.js";
import { updateInventoryAfterTransfer } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";

function optionLegal(selectEl) {
  const opt = selectEl?.selectedOptions?.[0];
  return {
    id: selectEl?.value || "",
    name: opt?.getAttribute("data-name") || opt?.textContent?.trim() || "",
    legal: (opt?.getAttribute("data-legal") || "").trim(),
  };
}

function updateLegalPreviews() {
  const from = optionLegal(document.getElementById("legalFromLocation"));
  const to = optionLegal(document.getElementById("legalToLocation"));
  const fromText = document.getElementById("legalFromText");
  const toText = document.getElementById("legalToText");
  const banner = document.getElementById("legalCrossBanner");
  const sourceName = document.getElementById("legalItemsSourceName");

  if (fromText) {
    fromText.textContent = from.legal || "не указано";
    fromText.classList.toggle("is-empty", !from.legal);
  }
  if (toText) {
    toText.textContent = to.legal || "не указано";
    toText.classList.toggle("is-empty", !to.legal);
  }
  if (sourceName) {
    sourceName.textContent = from.name || "—";
  }

  const isCross =
    !!from.legal &&
    !!to.legal &&
    from.legal.toLowerCase() !== to.legal.toLowerCase();
  if (banner) {
    banner.hidden = !isCross;
  }
}

async function loadItemsForFromLocation() {
  const from = optionLegal(document.getElementById("legalFromLocation"));
  const tbody = document.getElementById("legalItemsBody");
  const countEl = document.getElementById("legalItemsCount");
  const checkAll = document.getElementById("legalCheckAll");

  if (!tbody) return;

  if (!from.id) {
    tbody.innerHTML =
      '<tr class="legal-empty-row"><td colspan="7" class="text-center text-muted py-4">Сначала выберите объект «откуда»</td></tr>';
    if (countEl) countEl.textContent = "0";
    if (checkAll) checkAll.checked = false;
    return;
  }

  tbody.innerHTML =
    '<tr class="legal-empty-row"><td colspan="7" class="text-center text-muted py-4">Загрузка…</td></tr>';

  try {
    const response = await fetch(
      `/src/BusinessLogic/ActionsTMC/processGetItemsByLocation.php?locationId=${encodeURIComponent(from.id)}`,
    );
    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || "Ошибка загрузки ТМЦ");
    }

    const items = data.items || [];
    if (countEl) countEl.textContent = String(items.length);

    if (items.length === 0) {
      tbody.innerHTML =
        '<tr class="legal-empty-row"><td colspan="7" class="text-center text-muted py-4">На этом объекте нет ТМЦ для передачи</td></tr>';
      if (checkAll) checkAll.checked = false;
      return;
    }

    tbody.innerHTML = items
      .map((item) => {
        const legal = item.legal || "не указано";
        const legalClass = item.legal ? "" : "is-empty";
        return `
          <tr data-id="${item.id}">
            <td><input type="checkbox" class="legal-item-check" value="${item.id}"></td>
            <td><span class="id-chip">${item.id}</span></td>
            <td class="fw-semibold">${escapeHtml(item.name)}</td>
            <td>${escapeHtml(item.serial || "—")}</td>
            <td>${escapeHtml(item.brand || "—")}</td>
            <td>${escapeHtml(item.statusText || "—")}</td>
            <td><span class="meta-chip meta-chip-legal ${legalClass}"><i class="bi bi-building"></i>${escapeHtml(legal)}</span></td>
          </tr>`;
      })
      .join("");

    if (checkAll) checkAll.checked = false;
  } catch (error) {
    console.error(error);
    tbody.innerHTML = `<tr class="legal-empty-row"><td colspan="7" class="text-center text-danger py-4">${escapeHtml(String(error.message || error))}</td></tr>`;
    if (countEl) countEl.textContent = "0";
    showNotification(TypeMessage.error, String(error.message || error));
  }
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function getSelectedTmcIds() {
  return Array.from(document.querySelectorAll("#legalItemsBody .legal-item-check:checked")).map(
    (el) => parseInt(el.value, 10),
  );
}

function setAllChecked(checked) {
  document.querySelectorAll("#legalItemsBody .legal-item-check").forEach((el) => {
    el.checked = checked;
  });
  const checkAll = document.getElementById("legalCheckAll");
  if (checkAll) checkAll.checked = checked;
}

async function submitLegalTransfer() {
  const from = optionLegal(document.getElementById("legalFromLocation"));
  const to = optionLegal(document.getElementById("legalToLocation"));
  const userId = document.getElementById("legalTransferUser")?.value || "";
  const tmcIds = getSelectedTmcIds();

  if (!from.id) {
    showNotification(TypeMessage.notification, "Выберите объект «откуда»");
    return;
  }
  if (!to.id) {
    showNotification(TypeMessage.notification, "Выберите объект «куда»");
    return;
  }
  if (from.id === to.id) {
    showNotification(TypeMessage.notification, "Объекты «откуда» и «куда» должны отличаться");
    return;
  }
  if (!userId) {
    showNotification(TypeMessage.notification, "Выберите ответственного");
    return;
  }
  if (tmcIds.length === 0) {
    showNotification(TypeMessage.notification, "Отметьте ТМЦ для передачи");
    return;
  }

  const cross =
    from.legal && to.legal && from.legal.toLowerCase() !== to.legal.toLowerCase();
  const confirmText = cross
    ? `Передать ${tmcIds.length} ТМЦ с «${from.name}» (${from.legal}) на «${to.name}» (${to.legal})?`
    : `Передать ${tmcIds.length} ТМЦ с «${from.name}» на «${to.name}»?`;

  if (!confirm(confirmText)) return;

  try {
    await executeEntityAction({
      action: Action.UPDATE,
      formData: {
        location: to.id,
        user: userId,
        tmc_ids: JSON.stringify(tmcIds),
      },
      url: "/src/BusinessLogic/Actions/processCUDDistribute.php",
      successMessage: `Передано ${tmcIds.length} ТМЦ`,
    });

    const userSelect = document.getElementById("legalTransferUser");
    const responsible = (userSelect?.selectedOptions?.[0]?.textContent || "").trim();
    updateInventoryAfterTransfer(tmcIds, {
      status: StatusItem.ConfirmItem,
      location: to.name,
      legal: to.legal,
      responsible,
    });

    const isAdmin = Number(window.currentUserStatus) === 0;
    const isDestUser = String(userId) === String(window.currentUserId || "");
    if ((isAdmin || isDestUser) && typeof window.updateCounters === "function") {
      window.updateCounters({ confirmCount: tmcIds.length });
    }

    const modalEl = document.getElementById("legalTransferModal");
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    await loadItemsForFromLocation();
  } catch (error) {
    console.error(error);
    showNotification(TypeMessage.error, `Ошибка передачи: ${error.message || error}`);
  }
}

export function openLegalTransferModal(preselectFromId = null) {
  const modalEl = document.getElementById("legalTransferModal");
  if (!modalEl) {
    showNotification(TypeMessage.error, "Модальное окно передачи не найдено");
    return;
  }

  const fromSelect = document.getElementById("legalFromLocation");
  if (preselectFromId && fromSelect) {
    fromSelect.value = String(preselectFromId);
  }

  updateLegalPreviews();
  loadItemsForFromLocation();

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

export function initLegalTransferModalHandlers() {
  const fromSelect = document.getElementById("legalFromLocation");
  const toSelect = document.getElementById("legalToLocation");
  const checkAll = document.getElementById("legalCheckAll");
  const selectAllBtn = document.getElementById("legalSelectAllBtn");
  const submitBtn = document.getElementById("legalTransferSubmitBtn");

  fromSelect?.addEventListener("change", () => {
    updateLegalPreviews();
    loadItemsForFromLocation();
  });
  toSelect?.addEventListener("change", updateLegalPreviews);

  checkAll?.addEventListener("change", () => setAllChecked(!!checkAll.checked));
  selectAllBtn?.addEventListener("click", () => setAllChecked(true));
  submitBtn?.addEventListener("click", submitLegalTransfer);
}

(function () {
  window.openLegalTransferModal = function () {
    const selected = document.querySelector(
      "#locationTableContainer tbody tr.row-location.selected",
    );
    const fromId = selected?.getAttribute("data-id") || null;
    openLegalTransferModal(fromId);
  };

  document.addEventListener("DOMContentLoaded", () => {
    initLegalTransferModalHandlers();
  });
})();
