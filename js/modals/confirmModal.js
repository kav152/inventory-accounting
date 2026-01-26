import {
  executeEntityAction,
  getCollectFormData
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import {showNotification} from "./setting.js";



// Обработка действий с ТМЦ. Принять или отказать!
function processItem(tmcId, action) {
  fetch(
    `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${tmcId}&action=${action}`
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
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
        }
      } else {
        showNotification(TypeMessage.error, "Ошибка: " + data.message);
      }
    });
}

/**
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
  function openConfirmModal() {
    openEntityModal(Action.CREATE, "confirmModal");
  }

  // window.openAtWorkModalModal = openAtWorkModalModal;
  window.openConfirmModal = openConfirmModal;
  window.processItem = processItem;
})();