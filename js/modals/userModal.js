import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
//import { openModalAction } from './modalLoader.js';
import { executeActionForCUD } from "../templates/cudRowsInTable.js";
import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";


(function () {
  /**
   * Открыть модальное окно Пользователя
   * @param {Action} action
   */
  function openUserModal(action) {
    //const selectedRows = document.querySelectorAll("#customersTableContainer .row-customer.selected");
    let validStatuses = [];
    let selectedId = null;

    switch (action) {
      case Action.CREATE:
        selectedId = null;
        break;
      case Action.UPDATE:
      case Action.DELETE:
        const selectedRows = rowSelectionManager.getSelectedRows(
          "usersTableContainer"
        );
        if (selectedRows.length === 0) {
          showNotification(
            TypeMessage.notification,
            "Выберите пользователя для редактирования"
          );
          return;
        }
        selectedId = selectedRows[0]["id"];
        break;
    }
    statusEntity = action;
    openModalAction("userModal", null, validStatuses, {
      statusEntity: action,
      id: selectedId,
    });

    /*  openModalAction(action, null, validStatuses, {
      statusItem: action,
      id: selectedId,
    });*/

    window.rowSelectionManager.clearSelection("row-user");
  }

  window.openUserModal = openUserModal;
  window.saveUsersStatuses = saveUsersStatuses;
})();

/**
 * Обработчик работы модального окна пользователей (работы при загрузке, сохранении, закрытие)
 * @param {HTMLElement} modalElement
 */
export function initUserModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleUserFormSubmit(modalElement);
  });
}

/**
 * Обработчик модального окна
 * @param {HTMLElement} modalElement
 */
async function handleUserFormSubmit(modalElement) {
  const form = modalElement.querySelector("#userForm");
  const userData = getCollectFormData(form, window.statusEntity);

  try {
    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: userData,
      url: "/src/BusinessLogic/Actions/processCUDUsers.php",
      successMessage: "Пользователь добавлен успешно",
    });

    executeActionForCUD(
      window.statusEntity,
      result.resultEntity,
      "usersTableContainer",
      result.fields,
      "row-user",
      "id"
    );

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}

/**
 * Функция для сохранения статусов пользователей
 */
async function saveUsersStatuses() {
  try {
    const userRows = document.querySelectorAll(".row-user");
    if (!userRows.length) {
      throw new Error("Нет данных пользователей для сохранения");
    }

    userRows.forEach((row) => {
      const userId = row.getAttribute("data-id");
      if (!userId) return;

      // Получаем все данные из data-атрибутов
      const id = row.getAttribute("data-id");
      const name = row.getAttribute("data-name");
      const patronymic = row.getAttribute("data-patronymic");    
      const activeCheckbox = row.querySelector('input[name="active[]"]');
      const statusSelect = row.querySelector('select[name="Status"]');

      //console.log(row);
      
      // Сохраняем данные пользователя
      const usersData = {
        statusEntity: Action.UPDATE,
        id: parseInt(userId),
        Surname: surname,
        Name: name,
        Patronymic: patronymic,
        Password: "", // Пароль не меняем
        Status: statusSelect.value,
        isActive: activeCheckbox.checked,
      };

      //console.log(usersData);
      //return;

      try {
        const result = executeEntityAction({
          action: Action.UPDATE,
          formData: usersData,
          url: "/src/BusinessLogic/Actions/processCUDUsers.php",
          successMessage: `Активация пользователей выполнена`,
        });

        // Определяем поля для отображения в select
        //const displayFields = fields.map(field => field.name);
        //const displaySeparator = fields.length > 1 ? ', ' : '';

        console.log(result.resultEntity);

        // executeActionForCUDSelect(Action.CREATE, result.resultEntity, 'typeTMCSelect', ['value'], '', true);
      } catch (error) {
        console.error("Ошибка:", error);
        showNotification(TypeMessage.error, error);
      }

      // Добавляем в список активных
      /*  if (isActive) {
                      activeUsers.push(userId);
                  }*/
    });

    // Подготавливаем данные для отправки
    /* const data = {
                 statusEntity: 'update',
                 users: usersData,
                 active: activeUsers
             };*/

    //console.log('Данные для сохранения:', data);

    // Выполняем запрос на обновление
    /*   const response = await fetch('/src/BusinessLogic/Actions/processCUDUsers.php', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify(data)
               });*/

    /*  if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`);
              }

              const result = await response.json();

              if (result.success) {
                  showNotification(TypeMessage.success, 'Статусы пользователей успешно обновлены');

                  // Обновляем data-атрибуты строк
                  if (result.resultEntity && Array.isArray(result.resultEntity)) {
                      result.resultEntity.forEach(user => {
                          const row = document.querySelector(`tr[data-id="${user.IDUser}"]`);
                          if (row) {
                              // Обновляем data-атрибуты
                              row.setAttribute('data-status', user.Status);
                              row.setAttribute('data-active', user.isActive ? '1' : '0');
                          }
                      });
                  }
              } else {
                  throw new Error(result.message || 'Ошибка при обновлении статусов');
              }*/
  } catch (error) {
    console.error("Ошибка при сохранении статусов:", error);
    showNotification(
      TypeMessage.error,
      error.message || "Ошибка при сохранении статусов"
    );
  }
}
