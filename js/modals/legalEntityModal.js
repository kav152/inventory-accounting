import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";

export function initLegalEntityModalHandlers(modalElement) {
  const title = modalElement.querySelector("#legalEntityModalTitle");
  if (title) {
    title.textContent =
      window.statusEntity === Action.UPDATE
        ? "Редактировать юр. лицо"
        : "Добавить юр. лицо";
  }

  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleLegalEntitySubmit(modalElement);
  });
}

async function handleLegalEntitySubmit(modalElement) {
  const form = modalElement.querySelector("#legalEntityForm");
  if (!form) return;

  const checkbox = form.querySelector("#isActive");
  if (checkbox && !checkbox.checked) {
    // getCollectFormData may skip unchecked checkbox; ensure 0 is sent
    let hidden = form.querySelector('input[type="hidden"][name="isActive"]');
    if (!hidden) {
      hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "isActive";
      form.appendChild(hidden);
    }
    hidden.value = "0";
  }

  const data = getCollectFormData(form, window.statusEntity);
  if (!(data.NameLegalEntity || "").trim()) {
    alert("Укажите наименование юр. лица");
    return;
  }

  try {
    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: data,
      url: "/src/BusinessLogic/Actions/processCUDLegalEntity.php",
      successMessage:
        "Юр. лицо успешно " +
        (window.statusEntity === Action.CREATE ? "добавлено" : "обновлено"),
    });

    syncLegalEntityTableRow(window.statusEntity, result.resultEntity);

    if (typeof window.hideGlobalLoader === "function") {
      window.hideGlobalLoader();
    }
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance?.hide();
  } catch (error) {
    console.error(error);
  }
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function syncLegalEntityTableRow(action, entity) {
  const table = document.getElementById("legalEntityTableContainer");
  if (!table || !entity) return;
  const tbody = table.querySelector("tbody");
  if (!tbody) return;

  const id = String(entity.id ?? entity.IDLegalEntity ?? "");
  const name = String(entity.NameLegalEntity ?? "").trim();
  const active = Number(entity.isActive) === 1;

  let row = tbody.querySelector(`.row-legal-entity[data-id="${id}"]`);
  const cellsHtml = `
    <td><span class="id-chip">${escapeHtml(id)}</span></td>
    <td>
      <span class="meta-chip meta-chip-legal" title="${escapeHtml(name)}">
        <i class="bi bi-building"></i>${escapeHtml(name)}
      </span>
    </td>
    <td>
      ${
        active
          ? '<span class="badge bg-success">Да</span>'
          : '<span class="badge bg-secondary">Нет</span>'
      }
    </td>`;

  if (action === Action.CREATE || !row) {
    // remove empty placeholder
    const empty = tbody.querySelector("td[colspan]");
    if (empty) empty.closest("tr")?.remove();

    row = document.createElement("tr");
    row.className = "row-legal-entity";
    row.setAttribute("data-id", id);
    row.innerHTML = cellsHtml;
    tbody.appendChild(row);
  } else {
    row.innerHTML = cellsHtml;
  }

  const countEl = document.querySelector(
    '#legalEntities .toolbar-count, [data-bs-target="#legalEntities"]',
  );
  const countBadge = document.querySelector("#legalEntities .toolbar-count");
  if (countBadge) {
    const n = tbody.querySelectorAll(".row-legal-entity").length;
    countBadge.textContent = `${n} записей`;
  }
}
