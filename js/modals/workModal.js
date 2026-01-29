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

  const trigger = document.getElementById('brigadeDropdownTrigger');
  const dropdownList = document.getElementById('brigadeDropdownList');
  const selectedBrigadeId = document.getElementById('selectedBrigadeId');
  const selectedBrigadeText = document.getElementById('selectedBrigadeText');

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

  document.getElementById("createBrigadeForm").addEventListener("submit", handleCreateBrigadeFormSubmit);

  // Обработчик удаления бригады
  document.addEventListener('click', async function (e) {
    const deleteBtn = e.target.closest('.delete-brigade-btn');
    if (deleteBtn) {
      e.preventDefault();
      e.stopPropagation();

      const brigadeId = deleteBtn.dataset.id;
      const brigadeItem = deleteBtn.closest('.brigade-item');

      if (confirm('Вы уверены, что хотите удалить эту бригаду?')) {
        await deleteBrigade(brigadeId, brigadeItem);
      }
    }
  });

  // Обработчик передачи ТМЦ в работу
  document.getElementById("workForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    //const brigadeId = document.getElementById("brigadeSelect").value;
    const brigadeId = document.getElementById("selectedBrigadeId").value;
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
      statusEntity: Action.UPDATE,
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

  // Обработчик выбора бригады
  document.addEventListener('click', function (e) {
    const brigadeItem = e.target.closest('.brigade-item');
    if (brigadeItem && !e.target.closest('.delete-brigade-btn')) {
      const brigadeId = brigadeItem.dataset.id;
      const brigadeName = brigadeItem.dataset.name;
      const brigadirName = brigadeItem.dataset.brigadir;

      // Устанавливаем выбранное значение
      selectedBrigadeId.value = brigadeId;
      selectedBrigadeText.textContent = `${brigadeName} (${brigadirName})`;

      // Закрываем dropdown
      const dropdown = bootstrap.Dropdown.getInstance(trigger);
      if (dropdown) {
        dropdown.hide();
      }
    }
  });

}

/**
 * Удаление бригады
 */
async function deleteBrigade(brigadeId, brigadeItem) {
  try {

    const data = {
      statusEntity: Action.DELETE,
      id: brigadeId
    };

    const result = await executeEntityAction({
      action: Action.DELETE,
      formData: data,
      url: "/src/BusinessLogic/Actions/processCUDBrigades.php",
      successMessage: "Бригада успешно удалена",
    });

    if (result.success) {
      // Удаляем элемент из списка
      brigadeItem.remove();

      // Если удалена выбранная бригада, сбрасываем выбор
      const selectedBrigadeId = document.getElementById('selectedBrigadeId');
      if (selectedBrigadeId.value === brigadeId) {
        selectedBrigadeId.value = '';
        document.getElementById('selectedBrigadeText').textContent = 'Выберите бригаду';
      }

      // Показываем уведомление об успехе
      showNotification(TypeMessage.success, "Бригада успешно удалена");
    }
  } catch (error) {
    console.error("Error deleting brigade:", error);
    showNotification(TypeMessage.error, "Ошибка при удалении бригады - убедитесь, что все ТМЦ изъяты из бригады");
  }
}

/**
 * Добавление новой бригады в dropdown после создания
 */
function addBrigadeToDropdown(brigadeData) {
  const dropdownList = document.getElementById('brigadeDropdownList');

  // Создаем новый элемент списка
  const newItem = document.createElement('li');
  newItem.className = 'dropdown-item brigade-item d-flex justify-content-between align-items-center';
  newItem.dataset.id = brigadeData.id;
  newItem.dataset.name = brigadeData.NameBrigade;
  newItem.dataset.brigadir = brigadeData.NameBrigadir;

  newItem.innerHTML = `
    <span>${brigadeData.NameBrigade} (${brigadeData.NameBrigadir})</span>
    <button type="button" 
            class="btn btn-sm btn-outline-danger delete-brigade-btn"
            data-id="${brigadeData.id}"
            title="Удалить бригаду">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
      </svg>
    </button>
  `;

  // Добавляем в начало списка
  dropdownList.insertBefore(newItem, dropdownList.firstChild);

  // Автоматически выбираем новую бригаду
  const selectedBrigadeId = document.getElementById('selectedBrigadeId');
  const selectedBrigadeText = document.getElementById('selectedBrigadeText');

  selectedBrigadeId.value = brigadeData.id;
  selectedBrigadeText.textContent = `${brigadeData.NameBrigade} (${brigadeData.NameBrigadir})`;
}

/**
 * Обработчик создания бригады (обновленная версия)
 */
async function handleCreateBrigadeFormSubmit(e) {
  e.preventDefault();

  const form = document.getElementById("createBrigadeForm");
  if (!form) {
    showNotification(TypeMessage.error, "Форма не найдена");
    return;
  }

  try {
    const formData = getCollectFormData(form, window.statusEntity);

    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: formData,
      url: "/src/BusinessLogic/Actions/processCUDBrigades.php",
      successMessage: "Бригада успешно создана",
    });

    if (result.resultEntity) {
      // Добавляем новую бригаду в dropdown
      addBrigadeToDropdown(result.resultEntity);

      // Закрываем секцию создания
      document.getElementById("createBrigadeSection").style.display = "none";
      // Очищаем форму
      form.reset();
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification(
      TypeMessage.error,
      "Произошла ошибка при создании бригады",
    );
  }
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
