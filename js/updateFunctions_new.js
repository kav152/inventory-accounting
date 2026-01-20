

(function () {

});


/**
  * Универсальная функция обновления интерфейса
  * @param {Object} options - Параметры обновления
  * @param {string} options.action - Тип действия ('confirm', 'repair', 'work')
  * @param {Array} options.updatedIds - Массив ID обновленных ТМЦ
  * @param {number} options.newStatus - Новый статус ТМЦ
  * @param {Object} options.counters - Изменения счетчиков
  */
function updateInterface(options) {
    const {
        action,
        updatedIds = [],
        newStatus,
        counters = {}
    } = options;

    // 1. Обновляем главную таблицу
    if (updatedIds.length > 0 && newStatus !== undefined) {
        updateInventoryStatus(updatedIds, newStatus);
    }

    // 2. Обновляем счетчики уведомлений
    if (Object.keys(counters).length > 0) {
        updateCounters(counters);
    }

    // 3. Обновляем конкретные модальные окна
    switch (action) {
        case 'confirm':
            removeRowsFromModal('confirmModal', updatedIds, 'itemRow');
            break;
        case 'repair':
            removeRowsFromModal('confirmRepairModal', updatedIds, 'itemRepair-row');
            break;
        case 'work':
            removeRowsFromModal('atWorkModal', updatedIds, 'row-container1');
            break;
    }

    // 4. Обновляем счетчики в группах (для atWorkModal)
    if (action === 'work') {
        updateAtWorkGroupCounters();
    }

    // 5. Показываем уведомление
    if (action && updatedIds.length > 0) {
        const actionNames = {
            'confirm': 'принято',
            'repair': 'обработано',
            'work': 'возвращено'
        };
        showNotification(TypeMessage.success,
            `ТМЦ ${actionNames[action] || 'обновлено'}: ${updatedIds.length} шт.`);
    }
}

/**
 * Удаляет строки из модального окна
 * @param {string} modalId - ID модального окна
 * @param {Array} ids - Массив ID для удаления
 * @param {string} rowSelector - CSS-селектор строк
 */
function removeRowsFromModal(modalId, ids, rowSelector) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    ids.forEach(id => {
        let row;
        if (rowSelector === 'itemRow') {
            row = modal.querySelector(`#itemRow${id}`);
        } else if (rowSelector === 'itemRepair-row') {
            row = modal.querySelector(`.itemRepair-row[data-id="${id}"]`);
            if (row) {
                const formRow = document.getElementById(`repairForm${id}`);
                if (formRow) formRow.remove();
            }
        } else if (rowSelector === 'row-container1') {
            row = modal.querySelector(`.row-container1[data-id="${id}"]`);
        }

        if (row) row.remove();
    });

    // Проверяем, остались ли строки в модальном окне
    const remainingRows = modal.querySelectorAll(`.${rowSelector}, ${rowSelector}`);
    if (remainingRows.length === 0) {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            setTimeout(() => modalInstance.hide(), 500);
        }
    }
}

/**
 * Обновляет счетчики в группах atWorkModal
 */
function updateAtWorkGroupCounters() {
    const groups = document.querySelectorAll('.brigade-group');
    groups.forEach(group => {
        const groupId = group.getAttribute('data-group-id');
        const visibleRows = group.querySelectorAll('.row-container1:not([style*="display: none"])');
        const countEl = group.querySelector('.items-count');

        if (countEl) {
            countEl.textContent = visibleRows.length;

            // Скрываем группу если нет видимых строк
            const header = group.querySelector('.brigade-header');
            const collapse = group.querySelector('.collapse');
            if (visibleRows.length === 0) {
                header.style.display = 'none';
                if (collapse && collapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(collapse);
                    bsCollapse.hide();
                }
            } else {
                header.style.display = '';
            }
        }
    });
}

/**
 * Обновленный handleSuccess с интеллектуальным обновлением
 */
function handleSuccess(action, updatedIds, newStatus, counters) {
    // Если переданы параметры - обновляем интерфейс без перезагрузки
    if (action && updatedIds) {
        updateInterface({
            action: action,
            updatedIds: updatedIds,
            newStatus: newStatus,
            counters: counters
        });
        return;
    }

    // Иначе - старая логика перезагрузки (для обратной совместимости)
    if (window.needFullReload) {
        sessionStorage.setItem("reloadStartTime", Date.now());
        showGlobalLoader("Обновление данных...");

        setTimeout(() => {
            location.reload();
        }, 150);

        window.needFullReload = false;
    }
}

// Функция для обновления статуса в главной таблице
function updateInventoryStatus(tmcIds, newStatus) {
    //console.log(`Перечень tmcIds: ${tmcIds}`);
    tmcIds.forEach((id) => {
        const row = document.querySelector(`.row-container[data-id="${id}"]`);
        if (row) {
            // Обновляем ячейку статуса (5-я ячейка в строке)
            const statusCell = row.cells[4];
            console.log(statusCell.textContent);
            console.log(`Новый статус: ${StatusItem.getDescription(newStatus)}`);
            statusCell.textContent = StatusItem.getDescription(newStatus);

            // Обновляем классы статуса
            updateStatusClasses(row, newStatus);

            // ОБНОВЛЯЕМ АТРИБУТ DATA-STATUS - добавляем эту строку
            row.setAttribute('data-status', newStatus);


        }
    });
}

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