import {
  executeEntityAction,
  getCollectFormData
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { updateInventoryStatus } from "../updateFunctions.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "./setting.js";

/**
* Обработчик работы модального окна [yourEntity]
* @param {HTMLElement} modalElement 
*/
export function initConfirmRepairModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleConfirmRepairFormSubmit(modalElement);
  });

  // Обработчики для кнопок "В ремонт" и "Списать"
  modalElement.querySelectorAll(".itemRepair-row").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const isRepair = this.classList.contains("btn-repair");
      const formRow = document.getElementById(`repairForm${id}`);

      // Скрываем все открытые формы
      document.querySelectorAll(".repair-form").forEach((row) => {
        row.style.display = "none";
      });

      // Показываем нужную форму
      formRow.style.display = "table-row";

      // Показываем соответствующую форму
      const repairForm = formRow.querySelector(".repair-data-form");
      const writeOffForm = formRow.querySelector(".write-off-form");
    });
  });

  // 2. Инициализация динамических элементов (если нужны)
  //initDynamicElements(modalElement);
}

/**
* Инициализация динамических элементов
*/
function initDynamicElements(modalElement) {

}

/**
* Обработчик отправки формы
*/
async function handleConfirmRepairFormSubmit(modalElement) {

}


(function () {

  async function sendForRepair(idTMC, action) {
    const repairFormContainer = document.getElementById(`repairForm${idTMC}`);
    // Извлекаем саму форму внутри контейнера
    const form = repairFormContainer.querySelector("form.repair-data-form");

    // Валидация обязательных полей
    const requiredFields = {
      IDLocation: "Организация",
      InvoiceNumber: "Счет",
      RepairDescription: "Описание ремонта",
    };

    let isValid = true;
    for (const [fieldName, fieldLabel] of Object.entries(requiredFields)) {
      const field = form.elements[fieldName];
      if (!field || field.value === "0" || field.value.trim() === "") {
        showNotification(TypeMessage.error, `Поле "${fieldLabel}" обязательно для заполнения`);
        field?.focus();
        isValid = false;
        break;
      }
    }
    if (!isValid) return;


    const formData1 = getCollectFormData(form, window.statusEntity, {
      action: action,});
      
    try {
      const result = await executeEntityAction({
        action: window.statusEntity,
        formData: formData1,
        url: "/src/BusinessLogic/Actions/processCUDRepairItem.php",
        successMessage:  action == 'repair' ? "Ремонт ТМЦ подтвержден" : "ТМЦ списано",
      });

      if (result.resultEntity) {

        // Удаляем строки с данным id
        const itemRow = document.querySelector(
          `tr.itemRepair-row[data-id="${idTMC}"]`
        );
        const formRow = document.getElementById(`repairForm${idTMC}`);

        if (itemRow) itemRow.remove();
        if (formRow) formRow.remove();


        updateInventoryStatus([idTMC], action == 'writeOff' ? StatusItem.WrittenOff : StatusItem.Repair);

        // Обновляем счетчик в верхней панели
        const badge = document.getElementById("confirmRepairBadge");
        const notification = document.getElementById(
          "confirmRepairNotification"
        );
        const count = parseInt(badge.textContent) - 1;
        //console.log("Кол-во");
        //console.log(count);
        badge.textContent = count;
        notification.textContent = `Подтвердить ремонт ${count} ТМЦ`;

        // Проверяем, остались ли еще строки в таблице
        const remainingRows = document.querySelectorAll("tr.itemRepair-row");
        if (remainingRows.length === 0) {
          // Закрываем модальное окно
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("confirmRepairModal")
          );
          modal.hide();

        }

        /* === */
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error("Ошибка:", error);
      showNotification(
        TypeMessage.error,
        `Ошибка при отправке: ${error.message}`
      );
    }

    //console.log(`ИД:${idTMC}. ${action}. ${IDLocation}`);
  }

  function openConfirmRepairModal() {
    openEntityModal(Action.CREATE, "confirmRepairModal");
  }

  window.sendForRepair = sendForRepair;
  window.openConfirmRepairModal = openConfirmRepairModal;
})();
