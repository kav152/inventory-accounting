import {
  executeEntityAction,
  getCollectFormData
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "./setting.js";

// Обработка действий с ТМЦ. Принять или отказать!
function processItem(tmcId, action) {
  fetch(
    `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${encodeURIComponent(tmcId)}&action=${encodeURIComponent(action)}`
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        document.getElementById(`itemRow${tmcId}`)?.remove();

        updateInventoryStatus([tmcId], StatusItem.Released);

        const badge = document.getElementById("confirmBadge");
        const notification = document.getElementById("confirmNotification");
        const countText = document.getElementById("confirmCountText");
        const current =
          parseInt(
            (badge?.textContent || countText?.textContent || "1").trim(),
            10,
          ) || 1;
        const count = Math.max(0, current - 1);

        window.needFullReload = true;

        if (badge) {
          badge.textContent = String(count);
          badge.style.display = count > 0 ? "block" : "none";
        }
        if (countText) {
          countText.textContent = String(count);
        }
        if (notification) {
          if (!countText) {
            notification.textContent = `Принять ${count} ТМЦ`;
          }
          notification.classList.toggle("is-empty", count === 0);
          if (count === 0 && !countText) {
            notification.remove();
          }
        }

        if (count === 0) {
          bootstrap.Modal.getInstance(
            document.getElementById("confirmModal"),
          )?.hide();
        }
      } else {
        showNotification(TypeMessage.error, "Ошибка: " + data.message);
      }
    })
    .catch((error) => {
      console.error(error);
      showNotification(TypeMessage.error, "Ошибка сети при подтверждении ТМЦ");
    });
}

/**
 * Обработчик работы модального окна confirm
 * @param {HTMLElement} modalElement
 */
export function initСonfirmModalHandlers(modalElement) {
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleСonfirmModalFormSubmit(modalElement);
  });

  const searchInput = modalElement.querySelector("#confirmModalSearch");
  if (searchInput && searchInput.dataset.bound !== "1") {
    searchInput.dataset.bound = "1";
    searchInput.addEventListener("input", () => filterConfirmModalRows(modalElement));
    searchInput.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        searchInput.value = "";
        filterConfirmModalRows(modalElement);
      }
    });
  }
}

function filterConfirmModalRows(modalElement) {
  const q = (
    modalElement.querySelector("#confirmModalSearch")?.value || ""
  )
    .trim()
    .toLowerCase();
  const rows = modalElement.querySelectorAll("tr.confirm-item-row");
  let visible = 0;
  rows.forEach((row) => {
    const blob = (row.getAttribute("data-search") || row.textContent || "").toLowerCase();
    const show = !q || blob.includes(q);
    row.classList.toggle("confirm-row-hidden", !show);
    if (show) visible += 1;
  });
  const empty = modalElement.querySelector("#confirmSearchEmpty");
  const table = modalElement.querySelector(".confirm-table");
  if (empty) empty.style.display = visible === 0 && q ? "block" : "none";
  if (table) table.style.display = visible === 0 && q ? "none" : "";
}

async function handleСonfirmModalFormSubmit(modalElement) {
  try {
  } catch (error) {
    console.error("Ошибка:", error);
  }
}

(function () {
  function openConfirmModal() {
    openEntityModal(Action.CREATE, "confirmModal");
  }

  window.openConfirmModal = openConfirmModal;
  window.processItem = processItem;
})();
