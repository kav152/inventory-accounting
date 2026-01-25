import {
  modalRegistry,
  getModalIdByType,
  initModalHandlers,
} from "../modalTypes.js";

import { StatusItem } from '../../src/constants/statusItem.js';
import { ServiceStatus } from '../../src/constants/statusService.js';
import { Action } from '../../src/constants/actions.js';

(function () {

  const statusEntity = Action.CREATE;

  window.openModalAction = openModalAction;
  window.openEntityModal = openEntityModal;
  window.statusEntity = statusEntity;
})();

/**
 * Универсальная функция для открытия модального окна сущности
 * Использует реестр конфигураций для получения параметров
 * @param {Action} action - действие (CREATE, UPDATE, DELETE)
 * @param {string} entityType - тип сущности ('user', 'customer')
 * @throws {Error} если конфигурация для сущности не найдена
 */
export function openEntityModal(action, entityType) {
  const config = modalRegistry.getByModalType(entityType);

  if (!config) {
    const errorMsg = `Конфигурация для сущности '${entityType}' не найдена. Зарегистрированные типы: ${modalRegistry
      .getRegisteredEntityTypes()
      .join(", ")}`;
    console.error(errorMsg);
    showNotification(TypeMessage.error, errorMsg);
    return;
  }

  // Преобразуем действие в нижний регистр для проверки
  const actionLower = action.toLowerCase();

  // Проверяем поддержку действия
  if (!config.supportsAction(actionLower == Action.CREATE_ANALOG ? Action.CREATE : actionLower)) {
    const actionText = getActionDisplayText(action);
    showNotification(
      TypeMessage.notification,
      `Действие '${actionText}' не поддерживается для ${config.entityName}`
    );
    return;
  }

  let selectedId = null;
  let selectedRows = [];

  // Обработка разных типов действий
  switch (action) {
    case Action.CREATE:
      selectedId = null;
      break;

    case Action.CREATE_ANALOG:
      selectedRows = window.rowSelectionManager.getSelectedRows(config.tableContainerId);

      if (selectedRows.length === 0) {
        const actionText = getActionDisplayText(action);
        showNotification(TypeMessage.notification, `Выберите ${config.entityName} для ${actionText}`);
        return;
      }

      selectedId = selectedRows[0]["id"];
      break;

    case Action.UPDATE:
    case Action.EDIT:
      // Получаем выбранные строки через менеджер выделения
      selectedRows = window.rowSelectionManager.getSelectedRows(config.tableContainerId);

      if (selectedRows.length === 0) {
        const actionText = getActionDisplayText(action);
        showNotification(TypeMessage.notification, `Выберите ${config.entityName} для ${actionText}`);
        return;
      }

      selectedId = selectedRows[0]["id"];
      break;

    case Action.DELETE:

    default:
      console.warn(`Неизвестное действие: ${action}`);
      return;
  }
  window.statusEntity = action == Action.CREATE_ANALOG ? Action.CREATE : action;

  //console.log(`Текущий статус - ${window.statusEntity}`);

  // Очищаем выделение
  window.rowSelectionManager.clearSelection(config.rowClass);
  //console.log(`config.modalType - ${config.modalType}`);

  // Открываем модальное окно
  openModalAction(config.modalType, null, [], {
    statusEntity: action,
    id: selectedId,
  });
}

// Открыть модальное окно действи
async function openModalAction(
  type,
  selectedRows = null,
  validStatuses = [],
  additionalParams = {}
) {
  const modalContainer = document.getElementById("modalContainer");
  try {
    const params = new URLSearchParams();
    params.append("type", type);

    for (const [key, value] of Object.entries(additionalParams)) {
      params.append(key, value);
    }

    //console.log(`params - ${params}`);

    const response = await fetch(
      `/src/View/ModalLoader/modal_handler.php?${params}`
    );
    if (!response.ok) {
      throw new Error("Ошибка загрузки формы");
    }

    const html = await response.text();
    modalContainer.innerHTML = html;

    //============================================================
    const scripts = modalContainer.querySelectorAll("script");
    scripts.forEach((script) => {
      const newScript = document.createElement("script");
      newScript.textContent = script.textContent;
      document.body.appendChild(newScript).remove();
    });

    //============================================================

    //console.log(`type ${type}`);
    // Инициализируем модальное окно
    const modalId = getModalIdByType(type);
    //console.log(modalId);
    const modalElement = document.getElementById(modalId);

    if (!modalElement) throw new Error(`Модальное окно не найдено: ${modalId}`);
    const modalInstance = new bootstrap.Modal(modalElement);

    modalInstance.show();

    //setTimeout(() => modalInstance._focustrap.activate(), 100);
    // Заполняем таблицу выбранными ТМЦ (если нужно)
    if (selectedRows) {
      //console.log("fillSelectedItemsTable - Заполняем таблицу выбранными ТМЦ");
      fillSelectedItemsTable(type, selectedRows, validStatuses);
    }
    getModalParams(type, validStatuses);
    // Инициализируем обработчики
    //console.log("Инициализируем обработчики");
    //initModalHandlers(type, modalElement);
    // Используем реестр для получения и вызова обработчика
    const config = modalRegistry.getByModalType(type);
    if (config && typeof config.handler === 'function') {
      config.handler(modalElement);
    } else {
      console.warn(`Обработчик для модального окна '${type}' не найден`);
    }
    //console.log("обработчики готовы!");
  } catch (error) {
    showNotification(TypeMessage.error, error);
    console.error(error);
  }
}

//Индификаторы tbody таблицы ТМЦ
function fillSelectedItemsTable(type, selectedRows, validStatuses = []) {
  //console.log('Мы в fillSelectedItemsTable');

  const tableSelectors = {
    workModal: "#selectedWorkItemsTable",
    at_work: "#selectedItemsTable",
    confirm: "#selectedItemsTable",
    confirmRepair: "#selectedItemsTable",
    distributeModal: "#selectedItemsTable",
    serviceModal: "#selectedServiceItemsContainer",
    create: "",
    create_analog: "",
    edit: "",
    edit_write_off: "",
  };

  const selector = tableSelectors[type];
  if (!selector) return;

  const table = document.querySelector(selector);
  if (!table) return;

  table.innerHTML = "";
  window.selectedTMCIds = [];

  //console.log(`selectedRows -`);
  //console.log(selectedRows);

  selectedRows.forEach((row) => {
    const cells = row.cells;
    const status = parseInt(row.getAttribute("data-status"));

    if (validStatuses.includes(status)) {
      window.selectedTMCIds.push(cells[0].textContent);

      //console.log(`row -`);
      //console.log(row);

      table.innerHTML += fillInTable(type, cells);
    }
  });
}
/**
 * Заполнить таблицу в зависимости от типа модального окна
 * @param {*} type
 * @param {*} cells
 * @returns
 */
function fillInTable(type, cells = []) {
  //console.log('Мы в fillInTable');
  let html = null;
  switch (type) {
    case "workModal":
    case "at_work":
    case "confirm":
    case "confirmRepair":
    case "distributeModal":
      html = `
            <tr>
                <td>${cells[0].textContent}</td>
                <td>${cells[1].textContent}</td>
                <td>${cells[2].textContent}</td>
                <td>${cells[6].textContent}</td>
            </tr>
        `;
      break;
    case "serviceModal":
      const id = cells[0].textContent.trim();
      html = `
            <tr>
                <td>${id}</td>
                <td>${cells[1].textContent}</td>
                <td>
                    <textarea class="repair-reason-input" 
                              data-id="${cells[0].textContent.trim()}" 
                              required></textarea>
                </td>
            </tr>
        `;
      break;
  }
  //console.log('html - ');
  //console.log(html);
  return html;
}

function getModalParams(type, validStatuses) {
  switch (type) {
    case "serviceModal":
      getServiceModalParams(validStatuses);
    default:
      return {};
  }
}

function getActionDisplayText(action) {
  const actionTexts = {
    [Action.CREATE]: "создания",
    [Action.CREATE_ANALOG]: "создание по аналогу",
    [Action.UPDATE]: "редактирования",
    [Action.DELETE]: "удаления",
  };
  return actionTexts[action] || action.toLowerCase();
}

/**
 * Функция для определения параметров модального окна сервиса
 * @param {*} validStatuses - список статусов
 */
function getServiceModalParams(validStatuses) {
  let params = {};
  if (validStatuses.includes(StatusItem.Repair)) {
    params = {
      nameColumn: "Комментарии",
      nameBt: "Вернуть",
      title: "Вернуть из сервиса",
      statusService: ServiceStatus.returnService,
    };
    console.log("StatusItem.Repair ");
  } else {
    params = {
      nameColumn: "Причина ремонта",
      nameBt: "Отправить",
      title: "Отправить в сервис",
      statusService: ServiceStatus.sendService,
    };
  }

  document.getElementById("colReason").textContent = params.nameColumn;
  document.getElementById("title").textContent = params.title;
  document.getElementById("btnSubmitService").textContent = params.nameBt;

  //console.log("serviceModal");

  const sm = document.getElementById("serviceModal");
  if (sm) sm.setAttribute("data-status", params.statusService);
}
