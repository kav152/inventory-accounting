import {
  executeEntityAction,
  getCollectFormData
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "./setting.js";

/**
 * Обработчик модального окна архива ремонтов
 */
export function initConfirmRepairModalHandlers(modalElement) {
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
  });

  modalElement.querySelectorAll(".itemRepair-row").forEach((row) => {
    row.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const formRow = document.getElementById(`repairForm${id}`);
      document.querySelectorAll(".repair-form").forEach((r) => {
        r.style.display = "none";
      });
      if (formRow) formRow.style.display = "table-row";
    });
  });
}

function decrementRepairArchiveCounter() {
  const badge = document.getElementById("confirmRepairBadge");
  const notification = document.getElementById("confirmRepairNotification");
  const countText = document.getElementById("confirmRepairCountText");
  const count = Math.max(
    0,
    (parseInt(badge?.textContent || countText?.textContent || "1", 10) || 1) - 1,
  );
  if (badge) {
    badge.textContent = String(count);
    badge.style.display = count > 0 ? "block" : "none";
  }
  if (countText) {
    countText.textContent = String(count);
  }
  if (notification) {
    notification.classList.toggle("is-empty", count === 0);
    notification.style.display = count > 0 ? "block" : "none";
    if (count === 0) {
      notification.innerHTML = `Согласование ремонта <span id="confirmRepairCountText">0</span> ТМЦ`;
    }
  }
}

function removeRepairArchiveRows(idTMC) {
  document.querySelector(`tr.itemRepair-row[data-id="${idTMC}"]`)?.remove();
  document.getElementById(`repairForm${idTMC}`)?.remove();

  if (document.querySelectorAll("tr.itemRepair-row").length === 0) {
    bootstrap.Modal.getInstance(document.getElementById("confirmRepairModal"))?.hide();
  }
}

(function () {
  async function sendForRepair(idTMC, action) {
    const repairFormContainer = document.getElementById(`repairForm${idTMC}`);
    const form = repairFormContainer?.querySelector("form.repair-data-form");
    if (!form) {
      showNotification(TypeMessage.error, "Форма не найдена");
      return;
    }

    const repairId = parseInt(
      form.elements.ID_Repair?.value || form.dataset.repairId || "0",
      10,
    );

    if (action === "reject") {
      if (!confirm("Отказать в согласовании ремонта и вернуть ТМЦ на объект?")) {
        return;
      }
      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processRepairApproval.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "reject",
              tmcId: parseInt(idTMC, 10),
              repairId: repairId,
              reason: "Отказ в согласовании ремонта",
            }),
          },
        );
        const data = await response.json();
        if (!data.success) {
          throw new Error(data.message || "Не удалось отклонить ремонт");
        }
        showNotification(TypeMessage.success, data.message || "Отказано");
        removeRepairArchiveRows(idTMC);
        updateInventoryStatus([idTMC], StatusItem.Released);
        decrementRepairArchiveCounter();
      } catch (error) {
        showNotification(TypeMessage.error, error.message || "Ошибка отказа");
      }
      return;
    }

    if (action === "writeOff") {
      const requiredFields = {
        IDLocation: "Организация",
        InvoiceNumber: "№ счета",
        RepairDescription: "Описание ремонта",
      };
      for (const [fieldName, fieldLabel] of Object.entries(requiredFields)) {
        const field = form.elements[fieldName];
        if (!field || field.value === "0" || String(field.value).trim() === "") {
          showNotification(TypeMessage.error, `Поле "${fieldLabel}" обязательно для заполнения`);
          field?.focus();
          return;
        }
      }

      const formData1 = getCollectFormData(form, window.statusEntity, { action });
      try {
        const result = await executeEntityAction({
          action: window.statusEntity,
          formData: formData1,
          url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
          successMessage: "ТМЦ списано",
        });
        if (result.resultEntity) {
          removeRepairArchiveRows(idTMC);
          updateInventoryStatus([idTMC], StatusItem.WrittenOff);
          decrementRepairArchiveCounter();
        }
      } catch (error) {
        showNotification(TypeMessage.error, `Ошибка при списании: ${error.message}`);
      }
      return;
    }

    // Архив: счёт к уже отправленному в сервис ремонту
    if (repairId > 0 || parseInt(idTMC, 10) > 0) {
      const invoice = (form.elements.InvoiceNumber?.value || "").trim();
      if (!invoice) {
        showNotification(TypeMessage.error, 'Укажите № счета');
        form.elements.InvoiceNumber?.focus();
        return;
      }

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processRepairApproval.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "approve",
              tmcId: parseInt(idTMC, 10),
              repairId: repairId,
              invoiceNumber: invoice,
              updNumber: form.elements.UPD?.value || "",
              repairCost: form.elements.RepairCost?.value || "0",
              repairDescription: form.elements.RepairDescription?.value || "",
              locationId: parseInt(form.elements.IDLocation?.value || "0", 10),
            }),
          },
        );
        const data = await response.json();
        if (!data.success) {
          throw new Error(data.message || "Не удалось согласовать ремонт");
        }
        showNotification(TypeMessage.success, data.message || "Ремонт согласован");
        removeRepairArchiveRows(idTMC);
        updateInventoryStatus([idTMC], StatusItem.Repair);
        decrementRepairArchiveCounter();
      } catch (error) {
        showNotification(TypeMessage.error, error.message || "Ошибка согласования");
      }
      return;
    }

    // Устаревшие ТМЦ в статусе «Подтвердить ремонт» (до обновления логики)
    const legacyRequired = {
      IDLocation: "Организация",
      InvoiceNumber: "№ счета",
      RepairDescription: "Описание ремонта",
    };
    for (const [fieldName, fieldLabel] of Object.entries(legacyRequired)) {
      const field = form.elements[fieldName];
      if (!field || field.value === "0" || String(field.value).trim() === "") {
        showNotification(TypeMessage.error, `Поле "${fieldLabel}" обязательно для заполнения`);
        field?.focus();
        return;
      }
    }

    const formData1 = getCollectFormData(form, window.statusEntity, { action: "repair" });
    try {
      const result = await executeEntityAction({
        action: window.statusEntity,
        formData: formData1,
        url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
        successMessage: "Ремонт ТМЦ подтвержден",
      });

      if (result.resultEntity) {
        removeRepairArchiveRows(idTMC);
        updateInventoryStatus([idTMC], StatusItem.Repair);
        decrementRepairArchiveCounter();
      }
    } catch (error) {
      showNotification(TypeMessage.error, `Ошибка: ${error.message}`);
    }
  }

  function openConfirmRepairModal() {
    openEntityModal(Action.CREATE, "confirmRepairModal");
  }

  window.sendForRepair = sendForRepair;
  window.openConfirmRepairModal = openConfirmRepairModal;
})();
