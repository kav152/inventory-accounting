import { showNotification } from "../modals/setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { Action } from "../../src/constants/actions.js";
import { executeEntityAction, getCollectFormData, } from "../templates/entityActionTemplate.js";
import { updateInventoryStatus } from "../updateFunctions.js";


// Обработчик клика на "Передать ТМЦ"
(function () {
  let distributeModalInstance = null;

  function openDistributeModal(StatusUser) {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );
    //const selectedRows = rowSelectionManager.getSelectedRows("inventoryTable");

    if (selectedRows.length === 0) {
      showNotification(TypeMessage.notification, "Выберите ТМЦ для передачи");
      return;
    }
    let validStatuses = [StatusItem.Released];
    if (StatusUser == 0) {
      validStatuses = [StatusItem.Released, StatusItem.NotDistributed]; // Добавляем в конец массива
    }

    //console.log(`selectedRows - ${selectedRows}`);
    window.openModalAction("distributeModal", selectedRows, validStatuses);
    //window.removingSelection();
  }


  window.openDistributeModal = openDistributeModal;
  //window.initDistributeHandlers = initDistributeHandlers;
})();
/*
export function initDistributeHandlers(modalElement) {
    const form = document.getElementById("distributeForm");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append("tmc_ids", JSON.stringify(window.selectedTMCIds));

       try {
         const response = await fetch(
           "/src/BusinessLogic/ActionsTMC/processDistributeTMC.php",
           {
             method: "POST",
             body: formData,
           }
         );
 
         const data = await response.json();
 
         if (data.success) {
           const modal = bootstrap.Modal.getInstance(modalElement);
           modal.hide();
 
           showNotification(TypeMessage.success, data.message);
 
           // Обновляем статусы в таблице
           if (typeof updateInventoryStatus === "function") {
             updateInventoryStatus(
               window.selectedTMCIds,
               StatusItem.ConfirmItem
             );
           }
         } else {
           showNotification(TypeMessage.error, data.message);
         }
       } catch (error) {
         console.error("Ошибка отправки:", error);
         showNotification(TypeMessage.error, "Ошибка сети");
       }
    };
  }*/

/**
* Обработчик работы модального окна распределения
* @param {HTMLElement} modalElement 
*/
export function initDistributeModalHandlers(modalElement) {

  if (!modalElement) {
    console.error("Modal element is null or undefined");
    return;
  }

  const form = modalElement.querySelector("#distributeForm");
  if (!form) {
    console.error("Form element not found in modal");
    return;
  }
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleDistributeFormSubmit(modalElement);
  });

}




/**
 * Обработчик отправки формы распределения
 */
async function handleDistributeFormSubmit(modalElement) {
  const form = modalElement.querySelector("#distributeForm");
  const formData = getCollectFormData(form, Action.UPDATE);
  let tmc_ids = window.selectedTMCIds;
  formData['tmc_ids'] = JSON.stringify(tmc_ids);

  try {
    const result = await executeEntityAction({
      action: Action.UPDATE,
      formData: formData,
      url: "/src/BusinessLogic/Actions/processCUDDistribute.php",
      successMessage: "ТМЦ успешно переданы",
    });

    // Обновляем статусы в таблице ТМЦ
    if (tmc_ids) {
      // Вызываем функцию обновления статусов в таблице
      updateInventoryStatus(tmc_ids, StatusItem.ConfirmItem);
      // Снимаем выделение с строк
      window.removingSelection();
    }

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();



  } catch (error) {
    console.error("Ошибка при передаче ТМЦ:", error);
    showNotification(TypeMessage.error, "Ошибка при передаче ТМЦ: " + error.message);
  }
}
