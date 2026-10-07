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

function updateMainTableRow(id, { legalEntity, locationName, nameTMC, serialNumber, brandName }) {
  const row = document.querySelector(`#inventoryTable tr.row-container[data-id="${id}"]`);
  if (!row) return;

  if (typeof legalEntity === "string") {
    row.setAttribute("data-legal", legalEntity);
    const legalCell = row.querySelector(".legal-cell");
    if (legalCell) {
      const text = legalEntity || "не указано";
      legalCell.textContent = text;
      legalCell.title = legalEntity
        ? legalEntity
        : "Заполните юр. лицо в Админка → Юр. лица";
      legalCell.classList.toggle("is-empty", !legalEntity);
    }
  }

  if (nameTMC != null && row.cells?.[1]) {
    row.cells[1].textContent = nameTMC;
  }
  if (serialNumber != null && row.cells?.[2]) {
    row.cells[2].textContent = serialNumber;
  }
  if (brandName != null && row.cells?.[3]) {
    row.cells[3].textContent = brandName;
  }
  if (locationName && row.cells?.[6]) {
    row.cells[6].textContent = locationName;
  }

  if (typeof window.refreshRowSearchBlob === "function") {
    window.refreshRowSearchBlob(row);
  }
}

async function fillDependentSelect(selectEl, url, placeholder, selectedId = 0) {
  if (!selectEl) return;
  selectEl.innerHTML = `<option value="0">${placeholder}</option>`;
  try {
    const response = await fetch(url);
    const data = await response.json();
    if (!Array.isArray(data)) {
      throw new Error(data?.error || "Ошибка загрузки");
    }
    data.forEach((item) => {
      selectEl.add(new Option(item.Name, item.ID, false, Number(item.ID) === Number(selectedId)));
    });
  } catch (error) {
    console.error(error);
    selectEl.innerHTML = `<option value="0">Ошибка загрузки</option>`;
  }
}

function bindCascadeSelects(scope) {
  const typeSelect = scope.querySelector("#idTypeTMC");
  const brandSelect = scope.querySelector("#idBrandTMC");
  const modelSelect = scope.querySelector("#idModelTMC");
  if (!typeSelect || !brandSelect || !modelSelect) return;
  if (typeSelect.dataset.cascadeBound === "1") return;
  typeSelect.dataset.cascadeBound = "1";

  typeSelect.addEventListener("change", async function () {
    const typeId = Number(this.value) || 0;
    modelSelect.innerHTML = `<option value="0"></option>`;
    if (!typeId) {
      brandSelect.innerHTML = `<option value="0"></option>`;
      return;
    }
    await fillDependentSelect(
      brandSelect,
      `/src/BusinessLogic/getBrands.php?type_id=${typeId}`,
      ""
    );
  });

  brandSelect.addEventListener("change", async function () {
    const brandId = Number(this.value) || 0;
    if (!brandId) {
      modelSelect.innerHTML = `<option value="0"></option>`;
      return;
    }
    await fillDependentSelect(
      modelSelect,
      `/src/BusinessLogic/getModels.php?type_id=${brandId}`,
      ""
    );
  });
}

export function initCardItemPanel(root = document) {
  const scope = root?.querySelector ? root : document;
  const card = scope.querySelector("#cardContainer");
  if (!card || card.dataset.panelInit === "1") return;
  card.dataset.panelInit = "1";

  const typeSelect = scope.querySelector("#idTypeTMC");
  const brandSelect = scope.querySelector("#idBrandTMC");
  const modelSelect = scope.querySelector("#idModelTMC");
  const nameInput = scope.querySelector("#txtNameTMC");
  const serialInput = scope.querySelector("#txtSerialNum");
  const locationSelect = scope.querySelector("#cardLocationSelect");
  const legalInput = scope.querySelector("#cardLegalEntity");
  const saveBtn = scope.querySelector("#btnSaveCardLegal");
  const statusEl = scope.querySelector("#cardLegalSaveStatus");

  bindCascadeSelects(scope);

  if (locationSelect && legalInput && !locationSelect.dataset.legalBound) {
    locationSelect.dataset.legalBound = "1";
    locationSelect.addEventListener("change", function () {
      const selected = this.options[this.selectedIndex];
      const legal = (selected?.getAttribute("data-legal") || "").trim();
      if (!legal) {
        legalInput.value = "";
        return;
      }
      let opt = Array.from(legalInput.options || []).find((o) => o.value === legal);
      if (!opt && legalInput.tagName === "SELECT") {
        opt = new Option(legal, legal, true, true);
        legalInput.add(opt);
      }
      legalInput.value = legal;
    });
  }

  if (!saveBtn) {
    return;
  }

  saveBtn.addEventListener("click", async function () {
    const id = card.getAttribute("data-id");
    const locationId = locationSelect?.value || "0";
    const legalEntity = (legalInput?.value || "").trim();
    const typeId = Number(typeSelect?.value || 0);
    const brandId = Number(brandSelect?.value || 0);
    const modelId = Number(modelSelect?.value || 0);
    const nameTMC = (nameInput?.value || "").trim();
    let serialNumber = (serialInput?.value || "").trim();
    if (serialNumber.toLowerCase() === "серийный номер отсутствует") {
      serialNumber = "";
    }
    const brandName =
      brandSelect?.options?.[brandSelect.selectedIndex]?.textContent?.trim() || "";

    if (!id) {
      if (statusEl) statusEl.textContent = "Нет ID ТМЦ";
      return;
    }
    if (!typeId) {
      showNotification(TypeMessage.notification, "Выберите тип ТМЦ");
      typeSelect?.focus();
      return;
    }
    if (!brandId) {
      showNotification(TypeMessage.notification, "Выберите бренд");
      brandSelect?.focus();
      return;
    }
    if (!nameTMC) {
      showNotification(TypeMessage.notification, "Укажите наименование");
      nameInput?.focus();
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
            typeId,
            brandId,
            modelId,
            nameTMC,
            serialNumber,
            brandName,
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

      card.setAttribute("data-type", String(typeId));
      card.setAttribute("data-brand", String(brandId));
      card.setAttribute("data-model", String(modelId));
      card.setAttribute("data-name", nameTMC);
      card.setAttribute("data-serial", serialNumber);

      updateMainTableRow(id, {
        legalEntity,
        locationName: data.locationName || selected?.textContent?.trim(),
        nameTMC,
        serialNumber,
        brandName: data.brandName || brandName,
      });

      if (statusEl) statusEl.textContent = "Сохранено";
      showNotification(TypeMessage.success, data.message || "Карточка сохранена");
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
