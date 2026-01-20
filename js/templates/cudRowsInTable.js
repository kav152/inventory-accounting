import { Action } from "../../src/constants/actions.js";

/**
 * Выполнить действия по CUD для таблицы в зависимости от статуса сущности
 * @param {string} statusEntity - статус действия для сущности
 * @param {Array<Object>} entity - Объект с данными для вставки в таблицу
 * @param {string} tableId - ID таблицы (без символа #)
 * @param {Array<Object>} fields - перечень полей
 * @param {string} rowClassName - CSS класс для строки таблицы
 * @param {string} dataIdField - Поле объекта, которое будет использоваться как data-id атрибут
 */
export function executeActionForCUD(
  statusEntity,
  entity,
  tableId,
  fields,
  rowClassName,
  dataIdField = "id"
) {
  switch (statusEntity) {
    case Action.CREATE:
      createRowsInTable(entity, tableId, fields, rowClassName, dataIdField);
      break;
    case Action.UPDATE:
      updateRowsInTable(entity, tableId, fields, rowClassName, dataIdField);
      break;
    case Action.DELETE:
      deleteRowsInTable(tableId, rowClassName, dataIdField);
      break;
  }
}

/**
 * Функция для добавления строки в таблицу
 * @param {Array<Object>} entity - Объект с данными для вставки в таблицу
 * @param {string} tableId - ID таблицы (без символа #)
 * @param {Array<Object>} fields - перечень полей
 * @param {string} [rowClassName] - CSS класс для строки таблицы
 * @param {string} [dataIdField='id'] - Поле объекта, которое будет использоваться как data-id атрибут
 * @returns {void}
 */
function createRowsInTable(entity, tableId, fields, rowClassName, dataIdField = "id") {
  const tableBody = document.querySelector(`#${tableId} tbody`);

  if (!tableBody) {
    console.error(`Таблица с id ${tableId} не найдена`);
    return;
  }

  // Создаем новую строку
  const newRow = document.createElement("tr");

  //console.log(`Получаемый статус`);
 /* console.log(
    StatusItem.getStatusClasses(StatusItem.getByDescription(entity["Status"]))
  );*/
  // Устанавливаем классы строки
  if (rowClassName) {
    let statusClass = "";
    if (StatusItem.getByDescription(entity["Status"]) != null) {
      statusClass =
        " " +
        StatusItem.getStatusClasses(
          StatusItem.getByDescription(entity["Status"])
        );
      newRow.setAttribute("onclick", "handleAction(event)");
      newRow.setAttribute(
        "data-status",
        StatusItem.getByDescription(entity["Status"])
      );
    }
    //console.log("Статус строки");
    //console.log(rowClassName + statusClass);
    newRow.className = rowClassName + statusClass;
  }

  /*entity.forEach((value) => {
    console.log(`: ${value}`);
  })*/

  //console.log(`Получаемая сущность - ${entity}`);

  // Устанавливаем data-id атрибут
  if (entity[dataIdField]) {
    newRow.setAttribute("data-id", entity[dataIdField]);
  }

  // Создаем ячейки на основе конфигурации колонок
  fields.forEach((column) => {
    const td = document.createElement("td");
    // console.log(`td: ${td}`);
    // console.log(`column: ${column.toString()}`);

    // Получаем значение из entity
    let value = getNestedValue(entity, column.toString());
    //console.log(`Получаем значение из entity: ${value}`);

    // Если есть функция форматирования, применяем ее
    if (column.format && typeof column.format === "function") {
      value = column.format(value);
    }

    // Если значение undefined или null, ставим пустую строку
    td.textContent = value || "";

    newRow.appendChild(td);
  });

  // console.log(newRow);

  // Добавляем строку в начало таблицы
  tableBody.insertBefore(newRow, tableBody.firstChild);
}

/**
 * Вспомогательная функция для получения вложенных свойств объекта
 * @param {Object} obj - Исходный объект
 * @param {string} path - Путь к свойству (например, 'Location.city')
 * @returns {*} Значение свойства или undefined
 */
// Вспомогательная функция для получения вложенных свойств объекта (например, Location.city)
function getNestedValue(obj, path) {
  return path.split(".").reduce((acc, part) => acc && acc[part], obj);
}

/**
 * Функция для обновления строки в таблице
 * @param {Array<Object>} entity - Объект с данными для обновления
 * @param {string} tableId - ID таблицы
 * @param {Array<Object>} fields - перечень полей
 * @param {string} rowClassName - CSS класс для строки таблицы
 * @param {string} dataIdField - Поле объекта для data-id атрибута
 */
function updateRowsInTable(
  entity,
  tableId,
  fields,
  rowClassName,
  dataIdField = "id"
) {
  const tableBody = document.querySelector(`#${tableId} tbody`);
  if (!tableBody) {
    console.error(`Таблица с id ${tableId} не найдена`);
    return;
  }
  
  // Находим существующую строку по data-id
  const existingRow = tableBody.querySelector(`tr[data-id="${entity[dataIdField]}"]`);
  if (!existingRow) {
    console.error(`Строка с data-id ${entity[dataIdField]} не найдена`);
    return;
  }
  console.log(existingRow);  

  // Обновляем ячейки
  fields.forEach((column, index) => {
    console.log(column);    
    const td = existingRow.cells[index];
    console.log(`Строка - ${td}`);
    if (td) {
      let value = getNestedValue(entity, column);
      if (column.format && typeof column.format === "function") {
        value = column.format(value);
      }
      td.textContent = value || "";
    }
  });
}

/**
 * Функция для обновления строки в таблице
 * @param {Object} entity - Объект с данными для обновления
 * @param {string} tableId - ID таблицы
 * @param {Array<Object>} fields - перечень полей
 * @param {string} rowClassName - CSS класс для строки таблицы
 * @param {string} dataIdField - Поле объекта для data-id атрибута
 */
function updateRowsInTable111(
  entity,
  tableId,
  fields,
  rowClassName,
  dataIdField = "id"
) {
  const tableBody = document.querySelector(`#${tableId} tbody`);
  if (!tableBody) {
    console.error(`Таблица с id ${tableId} не найдена`);
    return;
  }

  // Находим существующую строку по data-id
  const existingRow = tableBody.querySelector(`tr[data-id="${entity[dataIdField]}"]`);
  if (!existingRow) {
    console.error(`Строка с data-id ${entity[dataIdField]} не найдена`);
    return;
  }

  // Обновляем данные в глобальной коллекции users
  if (window.users && Array.isArray(window.users)) {
    // Если entity это массив (старый формат), берем первый элемент
    const userData = Array.isArray(entity) ? entity[0] : entity;
    const userId = userData[dataIdField];
    
    // Находим индекс пользователя в коллекции
    const userIndex = window.users.findIndex(user => user[dataIdField] == userId);
    if (userIndex !== -1) {
      // Заменяем старые данные на новые
      window.users[userIndex] = { ...window.users[userIndex], ...userData };
    } else {
      console.warn(`Пользователь с id ${userId} не найден в коллекции users`);
    }
  }

  // Обновляем ячейки
  fields.forEach((column, index) => {
    const td = existingRow.cells[index];
    if (td) {
      let value = getNestedValue(entity, column.toString());
      if (column.format && typeof column.format === "function") {
        value = column.format(value);
      }
      td.textContent = value || "";
    }
  });
}

/**
 * Функция для удаления строки из таблицы
 * @param {Array<Object>} entity - Объект с данными для удаления
 * @param {string} tableId - ID таблицы
 * @param {string} dataIdField - Поле объекта для data-id атрибута
 */
function deleteRowsInTable(entity, tableId, dataIdField = "id") {
  const tableBody = document.querySelector(`#${tableId} tbody`);
  if (!tableBody) {
    console.error(`Таблица с id ${tableId} не найдена`);
    return;
  }

  // Находим и удаляем строку по data-id
  const rowToDelete = tableBody.querySelector(
    `tr[data-id="${entity[dataIdField]}"]`
  );
  if (rowToDelete) {
    rowToDelete.remove();
  } else {
    console.error(`Строка с data-id ${entity[dataIdField]} не найдена`);
  }
}

/**
 * Класс для управления выделением строк в таблицах
 */
export class RowSelectionManager {
  constructor() {
    this.lastSelectedRow = null;
    this.instances = new Map();
  }

  /**
   * Инициализирует выделение строк для конкретной таблицы
   * @param {string} tableContainerId - ID контейнера таблицы
   * @param {string} rowClass - CSS класс строк (например, 'row-user', 'row-customer')
   */
  initializeTable(tableContainerId, rowClass) {
    const tableContainer = document.getElementById(tableContainerId);
    if (!tableContainer) {
      console.warn(`Таблица с ID "${tableContainerId}" не найдена`);
      return;
    }

    // Сохраняем экземпляр
    this.instances.set(tableContainerId, {
      tableContainer,
      rowClass,
      lastSelectedRow: null,
    });

    // Добавляем обработчик событий
    tableContainer.addEventListener("click", (e) =>
      this.handleTableClick(e, tableContainerId)
    );

    //console.log(`Инициализировано выделение строк для таблицы: ${tableContainerId}`);
  }

  /**
   * Обрабатывает клики в таблице
   * @param {*} e
   * @param {String} tableContainerId - индификатор таблицы
   * @returns
   */
  handleTableClick(e, tableContainerId) {
    const instance = this.instances.get(tableContainerId);
    if (!instance) return;

    const { tableContainer, rowClass } = instance;
    const row = e.target.closest(`.${rowClass}`);

    if (!row) return;

    // Пропускаем клики по элементам, которые не должны вызывать выделение
    if (this.shouldIgnoreClick(e)) {
      return;
    }

    this.handleRowSelection(row, e, tableContainerId);
  }

  /**
   * Проверяет, нужно ли игнорировать клик
   * @param {*} e
   * @returns
   */
  shouldIgnoreClick(e) {
    return (
      e.target.closest(".active-toggle") ||
      e.target.type === "checkbox" ||
      e.target.tagName === "BUTTON" ||
      e.target.closest("button") ||
      e.target.closest(".no-select")
    );
  }

  /**
   * Обрабатывает выделение строки
   * @param {*} row
   * @param {*} e
   * @param {String} tableContainerId
   * @returns
   */
  handleRowSelection(row, e, tableContainerId) {
    const instance = this.instances.get(tableContainerId);
    const { rowClass, lastSelectedRow } = instance;

    const rows = Array.from(document.querySelectorAll(`.${rowClass}`));

    // Ctrl + клик - добавить/удалить из выделения
    if (e.ctrlKey) {
      row.classList.toggle("selected");
      instance.lastSelectedRow = row.classList.contains("selected")
        ? row
        : null;
      this.dispatchSelectionEvent(tableContainerId, rowClass);
      return;
    }

    // Shift + клик - выделить диапазон
    if (e.shiftKey && lastSelectedRow) {
      this.selectRange(rows, lastSelectedRow, row, rowClass);
      instance.lastSelectedRow = row;
      this.dispatchSelectionEvent(tableContainerId, rowClass);
      return;
    }

    // Обычный клик - выделить одну строку
    this.clearSelection(rowClass);
    row.classList.add("selected");
    instance.lastSelectedRow = row;
    this.dispatchSelectionEvent(tableContainerId, rowClass);
  }

  /**
   * Выделяет диапазон строк
   * @param {*} rows
   * @param {*} startRow
   * @param {*} endRow
   * @param {*} rowClass
   * @returns
   */
  selectRange(rows, startRow, endRow, rowClass) {
    const startIndex = rows.indexOf(startRow);
    const endIndex = rows.indexOf(endRow);

    if (startIndex === -1 || endIndex === -1) return;

    const [start, end] =
      startIndex < endIndex ? [startIndex, endIndex] : [endIndex, startIndex];

    this.clearSelection(rowClass);

    for (let i = start; i <= end; i++) {
      rows[i].classList.add("selected");
    }
  }

  /**
   * Снимает выделение со всех строк
   */
  clearSelection(rowClass) {
    document.querySelectorAll(`.${rowClass}.selected`).forEach((row) => {
      row.classList.remove("selected");
    });
  }

  /**
   * Получает ID выделенных строк
   * @param {String} tableContainerId - индификатор таблицы
   * @returns
   */
  getSelectedRows(tableContainerId) {
    const instance = this.instances.get(tableContainerId);
    if (!instance) return [];

    const { rowClass } = instance;
    const selectedRows = document.querySelectorAll(`.${rowClass}.selected`);

    return Array.from(selectedRows).map((row) => ({
      element: row,
      id: row.getAttribute("data-id"),
      data: this.getRowData(row),
    }));
  }

  /**
   * Извлекает данные из строки
   */
  getRowData(row) {
    const cells = row.querySelectorAll("td");
    const data = {};

    cells.forEach((cell, index) => {
      const header = this.getColumnHeader(index);
      if (header) {
        data[header] = cell.textContent.trim();
      }
    });

    return data;
  }

  /**
   * Получает заголовок колонки по индексу
   */
  getColumnHeader(index) {
    const headers = document.querySelectorAll("thead th");
    return headers[index]
      ? headers[index].textContent.trim()
      : `column_${index}`;
  }

  /**
   * Генерирует custom event при изменении выделения
   */
  dispatchSelectionEvent(tableContainerId, rowClass) {
    const selectedRows = this.getSelectedRows(tableContainerId);
    const event = new CustomEvent("rowSelectionChanged", {
      detail: {
        tableId: tableContainerId,
        rowClass: rowClass,
        selectedRows: selectedRows,
      },
    });
    document.dispatchEvent(event);
  }

  /**
   * Уничтожает экземпляр менеджера
   */
  destroyTable(tableContainerId) {
    this.instances.delete(tableContainerId);
  }
}

// Создаем глобальный экземпляр менеджера
window.rowSelectionManager = new RowSelectionManager();

// Экспорт для использования в модулях
export default window.rowSelectionManager;
