/**
 * Универсальный модуль фильтрации таблиц
 */
export class TableFilter {
  constructor(config) {
    this.config = {
      tableId: "",
      containerId: "",
      filters: [],
      rowSelector: "tr",
      excludeColumns: [],
      onFilterApplied: null,
      onRowCountChanged: null,
      ...config,
    };

    this.filters = {};
    this.currentDropdown = null;
    this.originalRowCount = 0;
    this.filteredRowCount = 0;

    this.init();
  }
  /**
   * Инициализирует фильтр: находит таблицу и контейнер, проверяет их наличие, сохраняет количество строк, запускает создание фильтров и глобальных обработчиков
   * @returns
   */
  init() {
    if (!this.config.tableId || !this.config.containerId) {
      console.error("TableFilter: tableId и containerId обязательны");
      return;
    }

    //console.log(`Текущая конфигурация ${this.config}`);
    this.table = document.getElementById(this.config.tableId);
    //console.log(`Текущая таблица ${this.table}`);
    this.container = document.getElementById(this.config.containerId);
    //console.log(`Текущий контейнер ${this.container}`);

    if (!this.table) {
      console.error("TableFilter: не найдена таблица");
      return;
    }
    if (!this.container) {
      console.error("TableFilter: не найден контейнер");
      return;
    }

    this.originalRowCount = this.getVisibleRows().length;
    this.filteredRowCount = this.originalRowCount;

    this.setupFilters();
    this.setupGlobalListeners();
  }

  /**
   * Для каждого заголовка таблицы (кроме исключённых столбцов) создаёт кнопку фильтра и контейнер dropdown, а также инициализирует структуру this.filters
   */
  setupFilters() {
    const headers = this.table.querySelectorAll("thead th");

    headers.forEach((header, columnIndex) => {
      // Пропускаем исключенные столбцы
      if (this.config.excludeColumns.includes(columnIndex)) {
        return;
      }

      // Создаем кнопку фильтра
      const filterBtn = this.createFilterButton(columnIndex);
      header.appendChild(filterBtn);

      // Создаем контейнер для dropdown
      const dropdownContainer = this.createDropdownContainer(columnIndex);
      header.appendChild(dropdownContainer);

      // Инициализируем фильтр
      this.filters[columnIndex] = {
        values: this.getColumnValues(columnIndex),
        selected: [],
      };
    });
  }

  /**
   * Создание кнопки фильтра
   * @param {String} columnIndex
   * @returns
   */
  createFilterButton(columnIndex) {
    const btn = document.createElement("button");
    btn.className = "filter-btn";
    btn.setAttribute("data-column", columnIndex);

    // SVG с двумя путями для активного и неактивного состояния
    btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960" width="18">
            <!-- Активный фильтр (скрыт по умолчанию) -->
            <path class="filter-active" d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Z" 
                  fill="currentColor" style="display: none;"/>
            <!-- Неактивный фильтр (виден по умолчанию) -->
            <path class="filter-inactive" d="M440-160q-17 0-28.5-11.5T400-200v-240L168-736q-15-20-4.5-42t36.5-22h560q26 0 36.5 22t-4.5 42L560-440v240q0 17-11.5 28.5T520-160h-80Zm40-308 198-252H282l198 252Zm0 0Z" 
                  fill="currentColor"/>
        </svg>
    `;

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      this.toggleFilterDropdown(columnIndex);
    });

    return btn;
  }

  /**
   *
   * @param {String} columnIndex
   * @returns
   */
  /* createDropdownContainer(columnIndex) {
         //console.log('выполнение createDropdownContainer');
         const container = document.createElement('div');
         container.className = 'dropdown-filter';
         //dropdown.style.zIndex = "100";
         container.style.zIndex = "1";
         container.id = `dropdown-${columnIndex}`;
 
         //console.log(`container.style.zIndex = ${container.style.zIndex}`);
         return container;
     }*/

  createDropdownContainer(columnIndex) {
    const container = document.createElement("div");
    container.className = "dropdown-filter";
    container.id = `dropdown-${columnIndex}`;

    // Получаем заголовок таблицы
    const header = this.table.querySelector(
      `thead th:nth-child(${parseInt(columnIndex) + 1})`,
    );

    // Вместо добавления в header, добавляем в body для корректного позиционирования
    document.body.appendChild(container);

    // Функция для обновления позиции
    const updatePosition = () => {
      if (header) {
        const rect = header.getBoundingClientRect();
        container.style.position = "fixed";
        container.style.left = `${rect.left + window.scrollX}px`;
        container.style.top = `${rect.bottom + window.scrollY}px`;
        container.style.zIndex = "1000";
      }
    };

    // Обновляем позицию при создании
    updatePosition();

    // Обновляем позицию при изменении размера окна
    window.addEventListener("resize", updatePosition);

    // Сохраняем функцию для обновления позиции
    container.updatePosition = updatePosition;

    return container;
  }

  getColumnValues(columnIndex) {
    const values = new Set();
    const rows = this.getVisibleRows();

    rows.forEach((row) => {
      const cell = row.cells[columnIndex];
      if (cell) {
        values.add(cell.textContent.trim());
      }
    });

    return Array.from(values).sort();
  }

  getVisibleRows() {
    return Array.from(
      this.table.querySelectorAll(this.config.rowSelector),
    ).filter((row) => row.style.display !== "none");
  }
  /*
        toggleFilterDropdown(columnIndex) {
            const dropdownContainer = document.getElementById(`dropdown-${columnIndex}`);
    
            if (this.currentDropdown && this.currentDropdown !== dropdownContainer) {
                this.currentDropdown.classList.remove('show');
                this.currentDropdown.innerHTML = '';
            }
    
            if (dropdownContainer.classList.contains('show')) {
                dropdownContainer.classList.remove('show');
                dropdownContainer.innerHTML = '';
                this.currentDropdown = null;
            } else {
                this.populateDropdown(columnIndex, dropdownContainer);
                dropdownContainer.classList.add('show');
                this.currentDropdown = dropdownContainer;
            }
        }*/

  toggleFilterDropdown(columnIndex) {
    const dropdownContainer = document.getElementById(
      `dropdown-${columnIndex}`,
    );

    if (!dropdownContainer) return;

    if (this.currentDropdown && this.currentDropdown !== dropdownContainer) {
      this.currentDropdown.classList.remove("show");
      this.currentDropdown.innerHTML = "";
    }

    if (dropdownContainer.classList.contains("show")) {
      dropdownContainer.classList.remove("show");
      dropdownContainer.innerHTML = "";
      this.currentDropdown = null;
    } else {
      // Обновляем позицию перед показом
      if (dropdownContainer.updatePosition) {
        dropdownContainer.updatePosition();
      }

      this.populateDropdown(columnIndex, dropdownContainer);
      dropdownContainer.classList.add("show");
      this.currentDropdown = dropdownContainer;
    }
  }

  populateDropdown(columnIndex, container) {
    const values = this.filters[columnIndex].values;

    const dropdown = document.createElement("div");
    dropdown.className = "filter-dropdown-content";
    dropdown.style.zIndex = "1000";

    // Поле поиска
    const searchInput = document.createElement("input");
    searchInput.type = "text";
    searchInput.className = "search-input";
    searchInput.placeholder = "Поиск...";

    // Список значений
    const filterList = document.createElement("div");
    filterList.className = "filter-list";

    // Кнопки действий
    const actions = document.createElement("div");
    actions.className = "filter-actions";

    const applyBtn = document.createElement("button");
    applyBtn.className = "filter-apply";
    applyBtn.textContent = "Применить";

    const cancelBtn = document.createElement("button");
    cancelBtn.className = "filter-cancel";
    cancelBtn.textContent = "Отмена";

    actions.appendChild(applyBtn);
    actions.appendChild(cancelBtn);

    dropdown.appendChild(searchInput);
    dropdown.appendChild(filterList);
    dropdown.appendChild(actions);
    container.appendChild(dropdown);

    // Функция заполнения списка
    const populateList = (filterText = "") => {
      filterList.innerHTML = "";

      // "Выбрать все"
      const selectAllItem = document.createElement("div");
      selectAllItem.className = "filter-item";

      const selectAllCheckbox = document.createElement("input");
      selectAllCheckbox.type = "checkbox";
      selectAllCheckbox.id = `select-all-${columnIndex}`;
      selectAllCheckbox.checked =
        !this.filters[columnIndex].selected ||
        this.filters[columnIndex].selected.length === 0;

      const selectAllLabel = document.createElement("label");
      selectAllLabel.htmlFor = `select-all-${columnIndex}`;
      selectAllLabel.textContent = "Выбрать все";

      selectAllItem.appendChild(selectAllCheckbox);
      selectAllItem.appendChild(selectAllLabel);
      filterList.appendChild(selectAllItem);

      // Значения столбца
      const filteredValues = values.filter((value) =>
        value.toLowerCase().includes(filterText.toLowerCase()),
      );

      filteredValues.forEach((value) => {
        const item = document.createElement("div");
        item.className = "filter-item";

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.value = value;
        checkbox.id = `filter-${columnIndex}-${value}`;

        if (this.filters[columnIndex].selected.includes(value)) {
          checkbox.checked = true;
        } else if (!this.filters[columnIndex].selected) {
          checkbox.checked = true;
        }

        const label = document.createElement("label");
        label.htmlFor = `filter-${columnIndex}-${value}`;
        label.textContent = value;

        item.appendChild(checkbox);
        item.appendChild(label);
        filterList.appendChild(item);
      });

      // Обработчик "Выбрать все"
      selectAllCheckbox.addEventListener("change", function () {
        const checkboxes = filterList.querySelectorAll(
          `input[type="checkbox"]:not(#select-all-${columnIndex})`,
        );
        checkboxes.forEach((checkbox) => {
          checkbox.checked = this.checked;
        });
      });
    };

    populateList("");

    // Поиск
    searchInput.addEventListener("input", function () {
      populateList(this.value);
    });

    // Применить фильтр
    applyBtn.addEventListener("click", () => {
      const checkboxes = filterList.querySelectorAll(
        `input[type="checkbox"]:not(#select-all-${columnIndex})`,
      );
      const selectedValues = [];

      checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
          selectedValues.push(checkbox.value);
        }
      });

      this.filters[columnIndex].selected = selectedValues;
      this.applyFilters();

      container.classList.remove("show");
      container.innerHTML = "";
      this.currentDropdown = null;
    });

    // Отмена
    cancelBtn.addEventListener("click", () => {
      container.classList.remove("show");
      container.innerHTML = "";
      this.currentDropdown = null;
    });
  }

  /**
   * Изменения кнопки фильтра
   * @param {String} columnIndex
   * @returns
   */
  updateFilterButton(columnIndex) {
    const button = this.table.querySelector(
      `.filter-btn[data-column="${columnIndex}"]`,
    );
    if (!button) return;

    const hasActiveFilter =
      this.filters[columnIndex] &&
      this.filters[columnIndex].selected &&
      this.filters[columnIndex].selected.length > 0;

    const activeIcon = button.querySelector(".filter-active");
    const inactiveIcon = button.querySelector(".filter-inactive");

    if (hasActiveFilter) {
      button.classList.add("active");
      button.style.color = "#3b82f6"; // Синий цвет для активного фильтра

      // Показываем активную иконку, скрываем неактивную
      if (activeIcon) activeIcon.style.display = "";
      if (inactiveIcon) inactiveIcon.style.display = "none";

      // Добавляем бейдж с количеством
      let badge = button.querySelector(".filter-badge");
      if (!badge) {
        badge = document.createElement("span");
        badge.className = "filter-badge";
        button.appendChild(badge);
      }
      badge.textContent = this.filters[columnIndex].selected.length;
    } else {
      button.classList.remove("active");
      button.style.color = "#64748b"; // Серый цвет для неактивного

      // Показываем неактивную иконку, скрываем активную
      if (activeIcon) activeIcon.style.display = "none";
      if (inactiveIcon) inactiveIcon.style.display = "";

      // Удаляем бейдж
      const badge = button.querySelector(".filter-badge");
      if (badge) badge.remove();
    }
  }

  applyFilters() {
    const rows = this.table.querySelectorAll(this.config.rowSelector);
    let visibleCount = 0;

    rows.forEach((row) => (row.style.display = ""));

    // Затем применяем фильтры последовательно
    for (const columnIndex in this.filters) {
      if (this.filters[columnIndex].selected.length === 0) continue;

      // Получаем ВИДИМЫЕ строки на текущем этапе
      const visibleRows = this.getVisibleRows();

      visibleRows.forEach((row) => {
        const cell = row.cells[columnIndex];
        if (!cell) return;

        const cellValue = cell.textContent.trim();
        // Если значение не входит в выбранные - скрываем строку
        if (!this.filters[columnIndex].selected.includes(cellValue)) {
          row.style.display = "none";
        }
      });
    }

    // Подсчет оставшихся видимых строк
    visibleCount = this.getVisibleRows().length;

    this.filteredRowCount = visibleCount;

    // Обновляем все кнопки фильтров
    for (const columnIndex in this.filters) {
      this.updateFilterButton(columnIndex);
    }

    // Вызываем callback-функции
    if (this.config.onFilterApplied) {
      this.config.onFilterApplied(this.filters, visibleCount);
    }

    if (this.config.onRowCountChanged) {
      this.config.onRowCountChanged(visibleCount, this.originalRowCount);
    }

    return visibleCount;
  }

  /*
    applyFilters() {
        const rows = this.table.querySelectorAll(this.config.rowSelector);
        let visibleCount = 0;

        rows.forEach(row => {
            let visible = true;

            for (const columnIndex in this.filters) {
                if (this.filters[columnIndex].selected.length === 0) continue;

                const cell = row.cells[columnIndex];
                if (!cell) continue;

                const cellValue = cell.textContent.trim();
                if (!this.filters[columnIndex].selected.includes(cellValue)) {
                    visible = false;
                    break;
                }
            }

            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        this.filteredRowCount = visibleCount;

        // Обновляем все кнопки фильтров
        for (const columnIndex in this.filters) {
            this.updateFilterButton(columnIndex);
        }

        // Вызываем callback-функции
        if (this.config.onFilterApplied) {
            this.config.onFilterApplied(this.filters, visibleCount);
        }

        if (this.config.onRowCountChanged) {
            this.config.onRowCountChanged(visibleCount, this.originalRowCount);
        }

        return visibleCount;
    }
        */

  clearFilters() {
    for (const columnIndex in this.filters) {
      this.filters[columnIndex].selected = [];
      this.updateFilterButton(columnIndex);
    }

    this.applyFilters();
  }

  getActiveFilters() {
    return this.filters;
  }

  getFilteredCount() {
    return this.filteredRowCount;
  }

  getTotalCount() {
    return this.originalRowCount;
  }

  setupGlobalListeners() {
    // Закрытие dropdown при клике вне
    document.addEventListener("click", (e) => {
      if (this.currentDropdown && !this.currentDropdown.contains(e.target)) {
        this.currentDropdown.classList.remove("show");
        this.currentDropdown.innerHTML = "";
        this.currentDropdown = null;
      }
    });
  }

  destroy() {
    // Удаляем все dropdown-контейнеры из body
    for (const columnIndex in this.filters) {
      const dropdown = document.getElementById(`dropdown-${columnIndex}`);
      if (dropdown) {
        dropdown.remove();
      }
    }

    // Удаляем обработчики событий
    window.removeEventListener("resize", this.updatePosition);
  }
}

/**
 * Фабрика для создания фильтров
 */
export class FilterFactory {
  static createHomeFilter() {
    return new TableFilter({
      tableId: "inventoryTable",
      containerId: "cont1",
      rowSelector: "tbody tr.row-container",
      excludeColumns: [], // Все столбцы фильтруются
      onRowCountChanged: (visibleCount, totalCount) => {
        const counter = document.getElementById("row-counter");
        if (counter) {
          counter.textContent = `Кол-во строк: ${visibleCount} из ${totalCount}`;
        }
      },
    });
  }

  static createWriteOffFilter() {
    return new TableFilter({
      tableId: "writeOffTable",
      containerId: "table-section",
      rowSelector: "tbody tr.main-row",
      excludeColumns: [0, 2, 3, 4, 5, 7, 8, 9], // Фильтруем только 1 и 6 столбцы
      onFilterApplied: (filters, visibleCount) => {
        // Обновляем общую сумму
        let total = 0;
        document.querySelectorAll(".main-row").forEach((row) => {
          if (row.style.display !== "none") {
            total += parseFloat(row.getAttribute("data-total-cost"));
          }
        });

        // Вызываем существующую функцию
        if (typeof updateTotalSum === "function") {
          updateTotalSum(total);
        }

        // Управляем деталями ремонта
        document
          .querySelectorAll(".repair-details-row")
          .forEach((detailRow) => {
            const id = detailRow.id.replace("details-", "");
            const mainRow = document.querySelector(
              `.main-row[data-id="${id}"]`,
            );

            if (mainRow && mainRow.style.display === "none") {
              detailRow.style.display = "none";
            } else if (mainRow && mainRow.classList.contains("selected")) {
              detailRow.style.display = "table-row";
            } else {
              detailRow.style.display = "none";
            }
          });

        // Снимаем выделение со скрытых строк
        if (selectedRow && selectedRow.style.display === "none") {
          selectedRow.classList.remove("selected");
          selectedRow = null;
        }
      },
    });
  }

  static createCustomFilter(config) {
    return new TableFilter(config);
  }
}
