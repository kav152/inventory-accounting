import { showNotification } from './modals/setting.js';
import { TypeMessage } from '../src/constants/typeMessage.js';
import { Action } from '../src/constants/actions.js';
import {
  executeEntityAction,
  getCollectFormData,
} from "./templates/entityActionTemplate.js";


(function () {
  async function deleteRow(id) {
    if (confirm("Вы уверены, что хотите переместить в корзину?")) {
      const row = document.querySelector(`.main-row[data-id="${id}"]`);
      const detailsRow = document.getElementById(`details-${id}`);

     /* if (row) row.style.display = "none";
      if (detailsRow) detailsRow.style.display = "none";

      // Получаем все данные из data-атрибутов
      const ID_TMC = row.getAttribute("data-id");
      const ID_Repair = row.getAttribute("data-id_Repair");

      const repairItemData = {
        ID_TMC: parseInt(ID_TMC),
        ID_Repair: parseInt(ID_Repair),
      };



      try {
        // Отправка на сервер
        const result = await executeEntityAction({
          action: Action.UPDATE,
          formData: formData,
          url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
          successMessage: "ТМЦ успешно сохранен",
        });

        if(result)
        {
          row.remove();
          if (detailsRow) detailsRow.remove();

        }


      }
      catch (error) {
        console.error("Ошибка сохранения ТМЦ:", error);
        showNotification(TypeMessage.error, "Ошибка при перемещении в корзину: ". error);
      }*/

      try {
        const formData = new FormData();
        formData.append("ID_TMC", id);
        formData.append("NameTMC", row.dataset.name);
        //console.log(id);
        //console.log(row.dataset.name);
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processRepairInBasket.php",
          {
            method: "POST",
            body: formData,
          }
        );
        const data = await response.json();
        if (data.success) {

          row.remove();
          if (detailsRow) detailsRow.remove();

          showNotification(TypeMessage.success, data.message);
          let sum = row.dataset.name;
          updateTotalSum(sum);


        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, error);
      }

      // Пересчитываем общую сумму
      applyFilters();
    }
  }
  

  async function returnToWorkTMC() {
    if (!selectedRow) {
      showNotification(TypeMessage.notification, "Пожалуйста, выберите запись для редактирования.");
      return;
    }

    const status = selectedRow.getAttribute('data-status');
    const id = selectedRow.getAttribute('data-id');
    console.log(status);
    if (status != StatusItem.WrittenOff) {
      showNotification(TypeMessage.notification, "Выбирите списанные ТМЦ");
      return;
    }

    if (confirm("Вы уверены, что хотите вернуть ТМЦ в работу?")) {
      try {

        let action = 'cancelWriteOff';
        const response = await fetch(
          `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${id}&action=${action}`
        );
        const data = await response.json();
        if (data.success) {
          showNotification(TypeMessage.success, 'Списаное ТМЦ возвращено на склад');
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, error);
      }
    }
  }

  window.deleteRow = deleteRow;
  window.returnToWorkTMC = returnToWorkTMC;
  //window.initCardWriteOffHandlers = initCardWriteOffHandlers;
})();


export function initCardWriteOffHandlers(modalElement) {
    const form = document.getElementById("edit_write_off");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

      //const form = modalElement.querySelector("#editWriteOffModal");
      const repairs = modalElement.querySelectorAll(".repair-item");
      const formData = new FormData();
      repairs.forEach((repair, index) => {
        formData.append(
          `repairs[${index}][ID_Repair]`,
          repair.dataset.repairId
        );
        formData.append(
          `repairs[${index}][ID_TMC]`,
          repair.querySelector(".id-tmc").value
        );
        formData.append(
          `repairs[${index}][InvoiceNumber]`,
          repair.querySelector(".invoice-number").value
        );
        formData.append(
          `repairs[${index}][RepairCost]`,
          repair.querySelector(".repair-cost").value
        );
        formData.append(
          `repairs[${index}][DateToService]`,
          repair.querySelector(".date-to-service").value
        );
        formData.append(
          `repairs[${index}][DateReturnService]`,
          repair.querySelector(".date-return-service").value
        );
        formData.append(
          `repairs[${index}][RepairDescription]`,
          repair.querySelector(".repair-description").value
        );
        formData.append(
          `repairs[${index}][IDLocation]`,
          repair.querySelector(".idLocation").value
        );
        formData.append(`repairs[${index}][inBasket]`, "0");
      });

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processUpdateRepairs.php",
          {
            method: "POST",
            body: formData,
          }
        );
        const data = await response.json();

        if (data.success) {
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();
          window.needFullReload = true;

          showNotification(TypeMessage.success, data.message);

          if (typeof handleSuccess === "undefined") {
            console.warn(
              "handleSuccess не найдена. Ожидание загрузки updateFunctions.js"
            );
          }

          if (typeof handleSuccess === "function") {
            handleSuccess();
          } else if (typeof window.handleSuccess === "function") {
            console.error("Функция handleSuccess недоступна");
            window.handleSuccess();
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
