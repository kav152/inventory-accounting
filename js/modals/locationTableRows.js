import { Action } from "../../src/constants/actions.js";

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function trim(value) {
  return String(value ?? "").trim();
}

function emptyCell() {
  return '<span class="empty-cell">—</span>';
}

function metaChip(className, icon, text) {
  const value = trim(text);
  if (!value) return emptyCell();
  const safe = escapeHtml(value);
  return `<span class="meta-chip ${className}" title="${safe}"><i class="bi ${icon}"></i>${safe}</span>`;
}

function textOrEmpty(value) {
  const safe = trim(value);
  return safe ? escapeHtml(safe) : emptyCell();
}

/**
 * Разметка строки как в locations_tab.php / serviceCenters_tab.php
 */
export function buildLocationRow(entity, rowClass, showMainBadge = true) {
  const row = document.createElement("tr");
  row.className = rowClass;
  row.setAttribute("data-id", String(entity.id ?? ""));

  const legal = trim(entity.FormsJointStockCompanies);
  const phone = trim(entity.Phone);
  const contacts = trim(entity.Contacts);
  const email = trim(entity.Email);
  const address = trim(entity.Address);
  const location2 = trim(entity.Location2);
  const cityName = trim(entity.City?.NameCity);
  const cityAddress = trim(entity.City?.Address);
  const isMain = Number(entity.isMainWarehouse) === 1;

  const mainBadge =
    showMainBadge && isMain
      ? '<span class="badge-main">Основной склад</span>'
      : "";

  const cityHtml = cityName
    ? `<span class="city-name">${escapeHtml(cityName)}</span>${
        cityAddress
          ? `<span class="city-address">${escapeHtml(cityAddress)}</span>`
          : ""
      }`
    : emptyCell();

  row.innerHTML = `
    <td><span class="id-chip">${escapeHtml(entity.id)}</span></td>
    <td>
      <div class="name-cell">
        <span>${escapeHtml(entity.NameLocation || "")}</span>
        ${mainBadge}
      </div>
    </td>
    <td>${metaChip("meta-chip-legal", "bi-building", legal)}</td>
    <td class="address-cell">${textOrEmpty(address)}</td>
    <td>${textOrEmpty(location2)}</td>
    <td><div class="city-cell">${cityHtml}</div></td>
    <td>${metaChip("meta-chip-phone", "bi-telephone", phone)}</td>
    <td>${metaChip("meta-chip-contact", "bi-person", contacts)}</td>
    <td>${metaChip("meta-chip-email", "bi-envelope", email)}</td>
  `;

  return row;
}

function refreshToolbarCount(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr[data-id]").length;
  const countEl = table.closest(".admin-locations")?.querySelector(".toolbar-count");
  if (countEl) {
    countEl.textContent = `${rows} записей`;
  }
}

export function syncLocationTableRow(
  action,
  entity,
  tableId,
  rowClass,
  { showMainBadge = true } = {},
) {
  const tbody = document.querySelector(`#${tableId} tbody`);
  if (!tbody || !entity?.id) {
    console.warn(`Таблица #${tableId} не найдена или нет id локации`);
    return;
  }

  const row = buildLocationRow(entity, rowClass, showMainBadge);

  if (action === Action.CREATE) {
    tbody.insertBefore(row, tbody.firstChild);
  } else if (action === Action.UPDATE) {
    const existing = tbody.querySelector(`tr[data-id="${entity.id}"]`);
    if (existing) {
      existing.replaceWith(row);
    } else {
      tbody.insertBefore(row, tbody.firstChild);
    }
  } else if (action === Action.DELETE) {
    tbody.querySelector(`tr[data-id="${entity.id}"]`)?.remove();
  }

  refreshToolbarCount(tableId);
}
