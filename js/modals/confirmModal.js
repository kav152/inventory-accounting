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
            notification.textContent = `Проверить УПД / принять ${count} ТМЦ`;
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
