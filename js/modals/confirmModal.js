import {
  executeEntityAction,
  getCollectFormData
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
<<<<<<< HEAD
import {showNotification} from "./setting.js";


=======
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "./setting.js";
>>>>>>> feature/local-updates-2026-08

// Обработка действий с ТМЦ. Принять или отказать!
function processItem(tmcId, action) {
  fetch(
<<<<<<< HEAD
    `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${tmcId}&action=${action}`
=======
    `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${encodeURIComponent(tmcId)}&action=${encodeURIComponent(action)}`
>>>>>>> feature/local-updates-2026-08
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
<<<<<<< HEAD
        // Удаляем строку из таблицы
        document.getElementById(`itemRow${tmcId}`).remove();

        updateInventoryStatus([tmcId], StatusItem.Released);


        // Обновляем счетчик уведомлений
        const badge = document.getElementById("confirmBadge");
        const notification = document.getElementById("confirmNotification");
        const count = parseInt(badge.textContent) - 1;
        needFullReload = true;


        if (count > 0) {
          badge.textContent = count;
          notification.textContent = `Принять ${count} ТМЦ`;
        } else {
          // Скрываем уведомление если элементов не осталось
          badge.remove();
          notification.remove();
          // Закрываем модальное окно
          bootstrap.Modal.getInstance(
            document.getElementById("confirmModal")
          ).hide();
=======
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
>>>>>>> feature/local-updates-2026-08
        }
      } else {
        showNotification(TypeMessage.error, "Ошибка: " + data.message);
      }
<<<<<<< HEAD
=======
    })
    .catch((error) => {
      console.error(error);
      showNotification(TypeMessage.error, "Ошибка сети при подтверждении ТМЦ");
>>>>>>> feature/local-updates-2026-08
    });
}

/**
<<<<<<< HEAD
    * Обработчик работы модального окна [yourEntity]
    * @param {HTMLElement} modalElement 
    */
    export function initСonfirmModalHandlers(modalElement) {
        // 1. Инициализация обработчиков формы
        modalElement.addEventListener("submit", async function (e) {
            e.preventDefault();
            await handleСonfirmModalFormSubmit(modalElement);
        });

        // 2. Инициализация динамических элементов (если нужны)
       // initDynamicElements(modalElement);
    }

    /**
    * Инициализация динамических элементов
    */
    function initDynamicElements(modalElement) {
        // Пример: обновление заголовка модального окна
        const modalTitle = modalElement.querySelector('#[yourEntity]ModalTitle');
        const statusEntity = window.statusEntity;
        
        if (statusEntity === Action.UPDATE) {
            modalTitle.textContent = 'Редактировать [entityName]';
        } else {
            modalTitle.textContent = 'Добавить [entityName]';
        }
    }

    /**
    * Обработчик отправки формы
    */
    async function handleСonfirmModalFormSubmit(modalElement) {
        try {

        } catch (error) {
            console.error("Ошибка:", error);
        }
    }


(function () {
  /**
   * Открыть модальное окно AtWorkModal
   * @param {Action} action - действие (CREATE, UPDATE, DELETE)
   */
=======
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
>>>>>>> feature/local-updates-2026-08
  function openConfirmModal() {
    openEntityModal(Action.CREATE, "confirmModal");
  }

<<<<<<< HEAD
  // window.openAtWorkModalModal = openAtWorkModalModal;
  window.openConfirmModal = openConfirmModal;
  window.processItem = processItem;
})();
=======
  window.openConfirmModal = openConfirmModal;
  window.processItem = processItem;
})();
>>>>>>> feature/local-updates-2026-08
