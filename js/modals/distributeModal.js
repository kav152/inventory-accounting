import { showNotification } from "../modals/setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { Action } from "../../src/constants/actions.js";
import { executeEntityAction, getCollectFormData,} from "../templates/entityActionTemplate.js";


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
    window.removingSelection();
  }


  window.openDistributeModal = openDistributeModal;
  //window.initDistributeHandlers = initDistributeHandlers;
})();

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
  }
