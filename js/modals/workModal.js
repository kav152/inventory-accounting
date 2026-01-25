import { showNotification } from "../modals/setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { Action } from "../../src/constants/actions.js";
import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { updateInventoryStatus } from "../updateFunctions.js";

/**
 * Обработкчик workModal
 * @param {*} modalElement
 */
export function initWorkModalHandlers(modalElement) {
  // Обработчики для кнопок расширения/сворачивания
  modalElement
    .querySelector("#btnExpand")
    .addEventListener("click", function () {
      modalElement.querySelector("#createBrigadeSection").style.display =
        "block";
    });
  // Обработчики для кнопок расширения/сворачивания

  document.getElementById("btnExpand").addEventListener("click", function () {
    document.getElementById("mainSection").classList.remove("col-md-8");
    document.getElementById("mainSection").classList.add("col-md-8");
    document.getElementById("createBrigadeSection").style.display = "block";
  });

  document.getElementById("btnCollapse").addEventListener("click", function () {
    document.getElementById("createBrigadeSection").style.display = "none";
  });

  document.getElementById("btnCancelCreate").addEventListener("click", function () {
      document.getElementById("createBrigadeSection").style.display = "none";
    });

  // Обработчик создания бригады
  document.getElementById("createBrigadeForm").addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      try {
        const response = await fetch("/src/BusinessLogic/createBrigade.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          // Добавляем новую бригаду в выпадающий список
          const select = document.getElementById("brigadeSelect");
          const option = document.createElement("option");
          option.value = data.id;
          option.textContent = `${formData.get("brigade_name")} (${formData.get(
            "brigadir",
          )})`;
          option.selected = true;
          select.appendChild(option);

          // Закрываем секцию создания
          document.getElementById("createBrigadeSection").style.display =
            "none";

          needFullReload = true;

          // Очищаем форму
          this.reset();
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(
          TypeMessage.error,
          "Произошла ошибка при создании бригады",
        );
      }
    });

  // Обработчик передачи ТМЦ в работу
  document.getElementById("workForm").addEventListener("submit", async function (e) {
      e.preventDefault();

      const brigadeId = document.getElementById("brigadeSelect").value;
      if (!brigadeId) {
        showNotification(TypeMessage.notification, "Выберите бригаду");
        return;
      }

      if (!window.selectedTMCIds || window.selectedTMCIds.length === 0) {
        showNotification(
          TypeMessage.notification,
          "Не выбрано ни одного ТМЦ для передачи",
        );
        return;
      }

      const modalElement = document.getElementById("workModal");
      const modal = bootstrap.Modal.getInstance(modalElement);

      const form = new FormData(this);
      form.append("tmc_ids", JSON.stringify(window.selectedTMCIds));
      form.append("brigade_id", JSON.stringify(brigadeId));
      //const formData = getCollectFormData(form, Action.UPDATE);

      const data = {
        statusEntity: statusEntity,
        tmc_ids: JSON.stringify(window.selectedTMCIds),
        brigade_id: JSON.stringify(brigadeId),
      };

      try {
        const result = await executeEntityAction({
          action: Action.UPDATE,
          formData: data,
          url: "/src/BusinessLogic/Actions/proccessCUDWorkTMC.php",
          successMessage: "ТМЦ успешно переданы в работу",
        });

        updateInventoryStatus(window.selectedTMCIds, StatusItem.AtWorkTMC);
        updateCounters({
          brigadesToItemsCount: window.selectedTMCIds.length,
        });
        modal.hide();
      } catch (error) {
        console.error("Error:", error);
        showNotification(
          TypeMessage.error,
          "Произошла ошибка при передаче ТМЦ в работу",
        );
      }
    });
  
}



// Обработчик открытия модального окна "В работу ТМЦ"
(function () {
  function openWorkModal() {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected",
    );

    if (selectedRows.length === 0) {
      showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для передачи в работу",
      );
      return;
    }
    let validStatuses = [StatusItem.Released];
    openModalAction("workModal", selectedRows, validStatuses);
    window.removingSelection();
  }
  function openAtWorkModalModal() {
    openEntityModal(Action.CREATE, "atWorkModal");
  }

  window.openAtWorkModalModal = openAtWorkModalModal;
  window.openWorkModal = openWorkModal;  
})();
