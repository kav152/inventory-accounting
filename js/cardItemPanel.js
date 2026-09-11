import { showNotification } from "./modals/setting.js";
import { TypeMessage } from "../src/constants/typeMessage.js";

async function parseJsonResponse(response) {
  const text = await response.text();
  try {
    return JSON.parse(text);
  } catch {
    const preview = text.replace(/\s+/g, " ").trim().slice(0, 160);
    if (preview.startsWith("<")) {
      throw new Error("Сервер вернул HTML вместо JSON. Проверьте лог на сервере.");
    }
    throw new Error(preview || "Ответ сервера не является JSON");
  }
}

function updateMainTableLegal(id, legalEntity, locationName) {
  const row = document.querySelector(`#inventoryTable tr.row-container[data-id="${id}"]`);
  if (!row) return;

  row.setAttribute("data-legal", legalEntity);
  const legalCell = row.querySelector(".legal-cell");
  if (legalCell) {
    const text = legalEntity || "не указано";
    legalCell.textContent = text;
    legalCell.title = legalEntity
      ? legalEntity
      : "Заполните юр. лицо в Админка → Локации";
    legalCell.classList.toggle("is-empty", !legalEntity);
  }

  if (locationName && row.cells?.[6]) {
    row.cells[6].textContent = locationName;
  }

  if (typeof window.refreshRowSearchBlob === "function") {
    window.refreshRowSearchBlob(row);
  }
}

export function initCardItemPanel(root = document) {
  const scope = root?.querySelector ? root : document;
  const card = scope.querySelector("#cardContainer");
  if (!card || card.dataset.panelInit === "1") return;
  card.dataset.panelInit = "1";

  const locationSelect = scope.querySelector("#cardLocationSelect");
  const legalInput = scope.querySelector("#cardLegalEntity");
  const saveBtn = scope.querySelector("#btnSaveCardLegal");
  const statusEl = scope.querySelector("#cardLegalSaveStatus");

  if (locationSelect && legalInput && !locationSelect.dataset.legalBound) {
    locationSelect.dataset.legalBound = "1";
    locationSelect.addEventListener("change", function () {
      const selected = this.options[this.selectedIndex];
      legalInput.value = selected?.getAttribute("data-legal") || "";
    });
  }

  if (!saveBtn) {
    return;
  }

  saveBtn.addEventListener("click", async function () {
    const id = card.getAttribute("data-id");
    const locationId = locationSelect?.value || "0";
    const legalEntity = (legalInput?.value || "").trim();

    if (!id) {
      if (statusEl) statusEl.textContent = "Нет ID ТМЦ";
      return;
    }
    if (!locationId || locationId === "0") {
      showNotification(TypeMessage.notification, "Выберите локацию");
      if (statusEl) statusEl.textContent = "Выберите локацию";
      locationSelect?.focus();
      return;
    }

    saveBtn.disabled = true;
    if (statusEl) statusEl.textContent = "Сохранение…";

    try {
      const response = await fetch(
        "/src/BusinessLogic/Actions/processSaveLocationLegal.php",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            tmcId: parseInt(id, 10),
            locationId: parseInt(locationId, 10),
            legalEntity,
          }),
        }
      );
      const data = await parseJsonResponse(response);
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Ошибка сохранения");
      }

      const selected = locationSelect.options[locationSelect.selectedIndex];
      if (selected) {
        selected.setAttribute("data-legal", legalEntity);
      }

      updateMainTableLegal(id, legalEntity, data.locationName || selected?.textContent?.trim());

      if (statusEl) statusEl.textContent = "Сохранено";
      showNotification(TypeMessage.success, data.message || "Юр. лицо сохранено");
    } catch (error) {
      if (statusEl) statusEl.textContent = error.message || "Ошибка";
      showNotification(TypeMessage.error, error.message || "Ошибка сохранения");
    } finally {
      saveBtn.disabled = false;
      setTimeout(() => {
        if (statusEl && statusEl.textContent === "Сохранено") {
          statusEl.textContent = "";
        }
      }, 2500);
    }
  });
}

window.initCardItemPanel = initCardItemPanel;
