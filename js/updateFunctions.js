import { StatusItem } from "../src/constants/statusItem.js";

/**
 * Функция для обновления статуса в главной таблице
 * @param {*} tmcIds 
 * @param {*} newStatus 
 */
export function updateInventoryStatus(tmcIds, newStatus) {
  //console.log(`Перечень tmcIds: ${tmcIds}`);
  tmcIds.forEach((id) => {
    const row = document.querySelector(`.row-container[data-id="${id}"]`);
    //console.log(`.row-container[data-id="${id}"]`);
    if (row) {
      // Обновляем ячейку статуса (5-я ячейка в строке)
      const statusCell = row.cells[4];
      //console.log(statusCell.textContent);
      //console.log(`Новый статус: ${StatusItem.getDescription(newStatus)}`);
      statusCell.textContent = StatusItem.getDescription(newStatus);

      // Обновляем классы статуса
      updateStatusClasses(row, newStatus);

      // ОБНОВЛЯЕМ АТРИБУТ DATA-STATUS - добавляем эту строку
      row.setAttribute('data-status', newStatus);
    }
    else{
      console.log(`Строка с id = ${id} не найдена - статус не изменен`);
    }
  });
}

/**
 * Обновление CSS-классов статуса
 * @param {*} row 
 * @param {*} newStatus 
 */
function updateStatusClasses(row, newStatus) {
  // Удаляем все существующие классы статуса
  Object.values(StatusItem.statusClasses).forEach((className) => {
    row.classList.remove(className);
  });

  // Добавляем новый класс статуса
  const statusClass = StatusItem.statusClasses[newStatus];
  if (statusClass) {
    row.classList.add(statusClass);
  }
}

(function () {
  /**
   * Обновляет счетчики в верхней панели уведомлений
   * @param {Object} counters - Объект с изменениями счетчиков
   * @param {number} [counters.confirmCount=0] - Изменение счетчика подтверждения
   * @param {number} [counters.confirmRepairCount=0] - Изменение счетчика подтверждения ремонта
   * @param {number} [counters.brigadesToItemsCount=0] - Изменение счетчика ТМЦ в работе
   */
  function updateCounters(counters = {}) {
    // Обновляем счетчик подтверждения
    if (counters.confirmCount !== undefined) {
      const badge = document.getElementById("confirmBadge");
      const notification = document.getElementById("confirmNotification");

      if (badge && notification) {
        const newCount = parseInt(badge.textContent) + counters.confirmCount;
        badge.textContent = newCount;
        notification.textContent = `Принять ${newCount} ТМЦ`;

        badge.style.display = newCount > 0 ? "block" : "none";
        notification.style.display = newCount > 0 ? "block" : "none";
      }
    }

    // Обновляем счетчик подтверждения ремонта
    if (counters.confirmRepairCount !== undefined) {
      const badge = document.getElementById("confirmRepairBadge");
      const notification = document.getElementById("confirmRepairNotification");

      if (badge && notification) {
        const newCount =
          parseInt(badge.textContent) + counters.confirmRepairCount;
        badge.textContent = newCount;
        notification.textContent = `Подтвердить ремонт ${newCount} ТМЦ`;

        badge.style.display = newCount > 0 ? "block" : "none";
        notification.style.display = newCount > 0 ? "block" : "none";
      }
    }

    // Обновляем счетчик ТМЦ в работе
    if (counters.brigadesToItemsCount !== undefined) {
      const badge = document.getElementById("atWorkBadge");
      const notification = document.getElementById("atWorkNotification");

      if (badge && notification) {
        const newCount =
          parseInt(badge.textContent) + counters.brigadesToItemsCount;
        badge.textContent = newCount;
        notification.innerHTML = `Выдано в работу <span id="atWorkCount">${newCount}</span> ТМЦ`;

        badge.style.display = newCount > 0 ? "block" : "none";
        notification.style.display = newCount > 0 ? "block" : "none";
      }
    }
  }


  /**
   * Обновляет конкретный ТМЦ в таблице после редактирования
   * @param {number} itemId - ID ТМЦ
   * @param {Object} updates - Объект с обновлениями
   * @param {string} [updates.name] - Новое наименование
   * @param {string} [updates.serialNumber] - Новый серийный номер
   * @param {string} [updates.brand] - Новый бренд
   * @param {string} [updates.responsible] - Новый ответственный
   * @param {number} [updates.status] - Новый статус
   */
  function updateSingleInventoryItem(itemId, updates = {}) {
    const row = document.querySelector(`.row-container[data-id="${itemId}"]`);
    if (!row) {
      console.warn(`Строка с ID ${itemId} не найдена`);
      return false;
    }

    const cells = row.cells;
    let updated = false;

    // Наименование (ячейка 1)
    if (updates.name !== undefined && cells[1]) {
      cells[1].textContent = updates.name;
      updated = true;
    }

    // Серийный номер (ячейка 2)
    if (updates.serialNumber !== undefined && cells[2]) {
      cells[2].textContent = updates.serialNumber;
      updated = true;
    }

    // Бренд (ячейка 3)
    if (updates.brand !== undefined && cells[3]) {
      cells[3].textContent = updates.brand;
      updated = true;
    }

    // Статус (ячейка 4)
    /*  if (updates.status !== undefined && cells[4]) {
      cells[4].textContent = StatusItem.getDescription(updates.status);
      updateStatusClasses(row, updates.status);
      updated = true;
    }*/

    // Ответственный (ячейка 5)
    /*  if (updates.responsible !== undefined && cells[5]) {
      cells[5].textContent = updates.responsible;
      updated = true;
    }*/

    if (updated) {
      //console.log(`ТМЦ ID: ${itemId} успешно обновлен`);
    }

    return updated;
  }

  /**
   * Вставляет новый ТМЦ в начало таблицы после создания
   * @param {Object} newItem - Данные нового ТМЦ
   * @param {number} newItem.id - ID ТМЦ
   * @param {string} newItem.name - Наименование
   * @param {string} newItem.serialNumber - Серийный номер
   * @param {string} newItem.brand - Бренд
   * @param {string} newItem.model - Модель
   * @param {string} newItem.responsible - Ответственный
   * @param {string} newItem.location - Локация
   * @param {number} newItem.status - Статус
   */
  function insertNewInventoryItem(newItem) {
    console.log("Вставка нового ТМЦ:", newItem);

    const tbody = document.querySelector("#inventoryTable tbody");
    if (!tbody) {
      console.error("Тело таблицы не найдено");
      return;
    }

    // Создаем новую строку
    const newRow = document.createElement("tr");
    newRow.className = `row-container ${StatusItem.statusClasses[newItem.status] || ""
      }`;
    newRow.setAttribute("data-id", newItem.id);
    newRow.setAttribute("data-status", newItem.status);
    newRow.onclick = handleAction;

    // Заполняем ячейки в соответствии со структурой home.php
    newRow.innerHTML = `
      <td class="rowGrid1">${newItem.id}</td>
      <td class="rowGrid1">${newItem.name || ""}</td>
      <td class="rowGrid1">${newItem.serialNumber || ""}</td>
      <td class="rowGrid1">${newItem.brand || ""}</td>
      <td class="rowGrid1">${StatusItem.getDescription(
      StatusItem.NotDistributed
    )}</td>
      <td class="rowGrid1">${newItem.responsible || ""}</td>
      <td class="rowGrid1">${newItem.location || ""}</td>
    `;

    // Вставляем в начало таблицы (первой строкой)
    if (tbody.firstChild) {
      tbody.insertBefore(newRow, tbody.firstChild);
    } else {
      tbody.appendChild(newRow);
    }

    // Обновляем счетчик строк
    updateRowCounter(1);

    console.log(`Новый ТМЦ ID: ${newItem.id} успешно добавлен в таблицу`);
  }

  /**
   * Обновляет счетчик строк в статус-баре
   * @param {number} change - Изменение количества строк (положительное или отрицательное)
   */
  function updateRowCounter(change) {
    const rowCounter = document.getElementById("row-counter");
    if (!rowCounter) return;

    // Текущий текст: "Кол-во строк: X из Y"
    const text = rowCounter.textContent;
    const match = text.match(/Кол-во строк: (\d+) из (\d+)/);
    if (match) {
      let current = parseInt(match[1]);
      let total = parseInt(match[2]);

      current += change;
      total += change;

      rowCounter.textContent = `Кол-во строк: ${current} из ${total}`;
    } else {
      // Если не удалось распарсить, просто обновляем
      const currentRows = document.querySelectorAll(
        "#inventoryTable tbody tr"
      ).length;
      rowCounter.textContent = `Кол-во строк: ${currentRows} из ${currentRows}`;
    }
  }

  /* =============== Перезагрузка ====================================================*/
  window.needFullReload = false;

  function showGlobalLoader(message = "Обновление данных...") {
    const loader = document.createElement("div");
    loader.id = "global-loader-overlay";
    loader.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 9998;"></div>
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); z-index: 9999; text-align: center;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">${message}</span>
            </div>
            <div class="mt-2">${message}</div>
        </div>
    `;
    document.body.appendChild(loader);
  }


  // Функция для скрытия индикатора
  function hideGlobalLoader() {
    const loader = document.getElementById("global-loader-overlay");
    if (loader) {
      loader.remove();
    }
  }

  // Обновленная handleSuccess с индикатором
  function handleSuccess() {

    if (window.needFullReload) {
      sessionStorage.setItem("reloadStartTime", Date.now());
      showGlobalLoader("Обновление данных...");

      // Даем время для отображения индикатора
      setTimeout(() => {
        console.log(location);
        location.reload();
      }, 150);

      window.needFullReload = false;
    }
  }

  // Функция для измерения времени перезагрузки
  function measureReloadTime() {
    const reloadStartTime = sessionStorage.getItem("reloadStartTime");
    if (reloadStartTime) {
      const reloadEndTime = Date.now();
      const reloadDuration = reloadEndTime - parseInt(reloadStartTime);

     /* console.log(
        `🕒 Время перезагрузки страницы: ${reloadDuration / 1000} сек`
      );*/

      if (reloadDuration > 1000) {
        showNotification(
          TypeMessage.info,
          `Страница обновлена за ${reloadDuration / 1000} сек`
        );
      }

      // Очищаем измерение
      sessionStorage.removeItem("reloadStartTime");
    }
  }

  /* ================================================================================== */

  window.updateInventoryStatus = updateInventoryStatus;

  window.handleSuccess = handleSuccess;
  window.hideGlobalLoader = hideGlobalLoader;
  window.showGlobalLoader = showGlobalLoader;
  window.measureReloadTime = measureReloadTime;

  window.updateCounters = updateCounters;
  window.updateSingleInventoryItem = updateSingleInventoryItem;
  window.insertNewInventoryItem = insertNewInventoryItem;
  window.updateRowCounter = updateRowCounter;
})();

