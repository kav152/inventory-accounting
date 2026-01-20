import { Action } from "../../src/constants/actions.js";

/**
 * Выполнить действия по CUD для Select в зависимости от статуса сущности
 * @param {string} statusEntity - статус действия для сущности
 * @param {Object} entity - Объект с данными для Select
 * @param {string} selectId - ID select элемента (без символа #)
 * @param {Array<string>} displayFields - поля для отображения в тексте option
 * @param {string} separator - разделитель полей (по умолчанию ', ')
 * @param {boolean} selectAfterCreate - выбирать ли новую опцию после создания
 */
export function executeActionForCUDSelect(
  statusEntity,
  entity,
  selectId,
  displayFields,
  separator = ", ",
  selectAfterCreate = true
) {
  switch (statusEntity) {
    case Action.CREATE:
      createOptionInSelect(
        entity,
        selectId,
        displayFields,
        separator,
        selectAfterCreate
      );
      break;
    case Action.UPDATE:
      updateOptionInSelect(entity, selectId, displayFields, separator);
      break;
    case Action.DELETE:
      deleteOptionFromSelect(selectId);
      break;
  }
}

/**
 * Универсальная функция для добавления option в Select
 * @param {Object} entity - Объект с данными для option
 * @param {string} selectId - ID select элемента (без символа #)
 * @param {Array<string>} displayFields - поля для отображения в тексте option
 * @param {string} separator - разделитель полей
 * @param {boolean} selectAfterCreate - выбирать ли новую строку
 */
function createOptionInSelect(
  entity,
  selectId,
  displayFields,
  separator,
  selectAfterCreate
) {
  const select = document.getElementById(selectId);

  if (!select) {
    console.error(`Select с id ${selectId} не найден`);
    return;
  }

  // Проверяем, существует ли уже option с таким value
  const existingOption = select.querySelector(`option[value="${entity.id}"]`);
  if (existingOption) {
    console.warn(`Option с value ${entity.id} уже существует`);
    return;
  }

  //console.log(`entity.id - ${entity.id}`);

  // Создаем новую option
  const newOption = document.createElement("option");
  newOption.value = entity.id;

  //console.log(`displayFields - ${displayFields}`);
  // Формируем текст из указанных полей
  const displayText = displayFields
    .map((field) => entity[field])
    .join(separator);
  //console.log(`displayText - ${displayText}`);
  newOption.textContent = displayText;

  // Добавляем option в select (перед последним option, если есть пустая опция)
  const hasEmptyOption =
    select.firstElementChild && select.firstElementChild.value === "";
  if (hasEmptyOption) {
    select.insertBefore(newOption, select.firstElementChild.nextSibling);
  } else {
    select.appendChild(newOption);
  }

  // Если нужно выбрать новую строку
  if (selectAfterCreate) {
   // triggerChangeEvent(select);
   // newOption.selected = true;
  }
}

/**
 * Функция для имитации события change
 * @param {HTMLElement} element - DOM элемент, для которого имитируется событие
 */
function triggerChangeEvent(element) {
  const event = new Event("change", {
    bubbles: true,
    cancelable: true,
  });
  element.dispatchEvent(event);
}

/**
 * Функция для обновления option в Select
 * @param {Object} entity - Объект с данными для обновления
 * @param {string} selectId - ID select элемента
 * @param {Array<string>} displayFields - поля для отображения
 * @param {string} separator - разделитель полей
 */
function updateOptionInSelect(entity, selectId, displayFields, separator) {
  const select = document.getElementById(selectId);

  if (!select) {
    console.error(`Select с id ${selectId} не найден`);
    return;
  }

  // Находим существующую option
  const existingOption = select.querySelector(`option[value="${entity.id}"]`);
  if (!existingOption) {
    console.error(`Option с value ${entity.id} не найдена`);
    return;
  }

  // Обновляем текст
  const displayText = displayFields
    .map((field) => entity[field])
    .join(separator);
  existingOption.textContent = displayText;
}

/**
 * Функция для удаления option из Select
 * @param {string} selectId - ID select элемента
 */
function deleteOptionFromSelect(selectId) {
  const select = document.getElementById(selectId);

  if (!select) {
    console.error(`Select с id ${selectId} не найден`);
    return;
  }

  // Находим и удаляем option
  const optionToDelete = select.querySelector(`option[value="${selectId}"]`);
  if (optionToDelete) {
    const wasSelected = optionToDelete.selected;
    optionToDelete.remove();

    // Если удаленная опция была выбрана, выбираем первую доступную
    if (wasSelected && select.options.length > 0) {
      select.options[0].selected = true;
    }
  } else {
    console.error(`Option с value ${selectId} не найдена`);
  }
}

/**
 * Дополнительная функция для очистки всех option кроме первой (пустой)
 * @param {string} selectId - ID select элемента
 */
export function clearSelectOptions(selectId) {
  const select = document.getElementById(selectId);

  if (!select) {
    console.error(`Select с id ${selectId} не найден`);
    return;
  }

  // Сохраняем первую опцию (пустую)
  const firstOption = select.options[0];

  // Очищаем все options
  select.innerHTML = "";

  // Возвращаем первую опцию
  if (firstOption) {
    select.appendChild(firstOption);
  }
}
