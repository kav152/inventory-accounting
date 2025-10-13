(function () {
  // Выпадающее меню
  function toggleSubMenu(button) {
    const sidebar = document.getElementById("sidebar");
    const toggleButton = document?.getElementById("toggle-btn");
    button.nextElementSibling.classList.toggle("show");
    button.classList.toggle("rotate");

    if (sidebar?.classList.contains("close")) {
      sidebar?.classList.toggle("close");
      toggleButton?.classList.toggle("rotate");
    }
  }

  // Боковая панель
  function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const toggleButton = document?.getElementById("toggle-btn");
    sidebar?.classList.toggle("close");
    toggleButton?.classList.toggle("rotate");

    Array.from(sidebar?.getElementsByClassName("show")).forEach((ul) => {
      ul.classList.remove("show");
      ul.previousElementSibling?.classList.remove("rotate");
    });
  }

  async function handleAction(event) {
    const id = event.currentTarget.dataset.id;
    event.preventDefault();

    // Обновляем URL без перезагрузки страницы
    //history.pushState(null, `?selected=${id}`);
    try {
      const response = await fetch(
        `/src/View/cardItem.php?id=${encodeURIComponent(id)}`
      );
      const data = await response.text();
      document.getElementById("resultContainer").innerHTML = data;
    } catch (error) {
      console.error("Ошибка:", error);
      document.getElementById(
        "resultContainer"
      ).innerHTML = `<div class="alert alert-danger">Ошибка загрузки данных: ${error.message}</div>`;
    }

    /*
    try {
      const response = await fetch("/src/View/cardItem.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded"},
        body: "id=" + encodeURIComponent(id),
      });

      const data = await response.text();
      //console.log(data);
      document.getElementById("resultContainer").innerHTML = data;
    } catch (error) {
      console.error("Ошибка:", error);
    }*/
  }

  let selectedId = null;
  let statusItem = null;

  // Обработчик клика по строке
  /*  document.querySelectorAll(".row-container").forEach((row) => {
    row.addEventListener("click", function (e) {
      e.preventDefault();
      selectedId = this.getAttribute("data-id");
      // Удаляем выделение у всех строк
      document
        .querySelectorAll(".row-container")
        .forEach((r) => r.classList.remove("selected"));
      // Выделяем текущую строку
      console.log(`Выделяем строку с ИД ${selectedId}`);
      this.classList.add("selected");
    });
  });*/

  function findById(idItem) {
    const row = document.querySelector(`.row-container[data-id="${idItem}"]`);
    if (!row) {
      console.warn(`Строка с ID ${idItem} не найдена`);
      return null;
    }
    // 2. Извлекаем данные из ячеек
    const cells = row.querySelectorAll(".rowGrid");
    return {
      id: cells[0].textContent,
      name: cells[1].textContent.trim(),
      serialNumber: cells[2].textContent.trim(),
      brand: cells[3].textContent.trim(),
      status: cells[4].textContent.trim(),
      responsible: cells[5].textContent.trim(),
      location: cells[6].textContent.trim(),
    };
  }

  // Добавьте проверку существования элемента
  /*function openModal(action) {
    const url = ActionUrls[action];
    statusItem = action;

    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );

    switch (action) {
      case Action.CREATE:
        selectedId = null;
        break;
      case Action.EDIT:
        if (selectedRows.length === 0) {
          showNotification(TypeMessage.notification, "Выберите ТМЦ для редактирования");
          return;
        }
        selectedId = selectedRows[0].cells[0].textContent;
        break;
      case Action.CREATE_ANALOG:
        if (selectedRows.length === 0) {
          showNotification(TypeMessage.notification, "Выберите ТМЦ в качестве аналога");
          return;
        }
        selectedId = selectedRows[0].cells[0].textContent;
        break;
      case Action.SEND_TMC:
        if (!selectedId) {
          showNotification(TypeMessage.notification, "Выберите ТМЦ для передачи");
          return;
        }
    }

    const modalBody = document.getElementById("modalBody");
    const rightModal = document.getElementById("rightModal");

    if (!modalBody || !rightModal) {
      console.error("Элементы модального окна не найдены");
      return;
    }

    // Убираем выделение со всех строк
    document.querySelectorAll(".row-container").forEach((r) => {
      r.classList.remove("selected");
    });

    fetch("/src/View/" + url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(
        selectedId
      )}&statusItem=${encodeURIComponent(statusItem)}`,
    })
      .then((response) => response.text())
      .then((html) => {
        modalBody.innerHTML = html; // Используем переменную modalBody
        rightModal.style.display = "block";

        // Запуск скриптов внутри модального окна
        const scripts = modalBody.querySelectorAll("script");
        scripts.forEach((script) => {
          const newScript = document.createElement("script");

          // Копируем атрибуты (src, async и т.д.)
          Array.from(script.attributes).forEach((attr) => {
            newScript.setAttribute(attr.name, attr.value);
          });

          // Копируем содержимое скрипта
          newScript.textContent = script.textContent;

          // Вставляем в DOM для выполнения
          document.body.appendChild(newScript).remove();
        });
      })
      .catch((error) => console.error("Ошибка:", error));

    // обработчик клика по документу
    document.addEventListener("click", closeOnOutsideClick);
    selectedId = null;
  }*/

  // функция для закрытия при клике вне
  function closeOnOutsideClick(event) {
    const modal = document.getElementById("rightModal");
    const isClickInside = modal.contains(event.target);

    if (!isClickInside && modal.style.display === "block") {
      closeModal();
      document.removeEventListener("click", closeOnOutsideClick);
    }
  }

  // Функция закрытия
  function closeModal() {
    document.getElementById("rightModal").style.display = "none";
    document.removeEventListener("click", closeOnOutsideClick); // Удаляем обработчик
  }

  async function saveModal(event) {
    event.preventDefault();

    // Валидация обязательных полей
    const requiredFields = {
      [PropertySelectID[PropertyTMC.TYPE_TMC]]: "Тип ТМЦ",
      [PropertySelectID[PropertyTMC.BRAND]]: "Бренд",
      [PropertySelectID[PropertyTMC.MODEL]]: "Модель",
      txtNameTMC: "Наименование",
    };

    for (const [fieldId, fieldName] of Object.entries(requiredFields)) {
      const field = document.getElementById(fieldId);
      if (!field.value || field.value === "0") {
        showNotification(TypeMessage.notification, `Поле "${fieldName}" обязательно для заполнения!`);
        return false;
      }
    }

    const data = {
      ID_TMC: document.getElementById("inventoryId").value,
      NameTMC: document.getElementById("txtNameTMC").value,
      Status: "0",
      SerialNumber: document.getElementById("txtSerialNum").value,
      IDTypesTMC: document.getElementById(
        PropertySelectID[PropertyTMC.TYPE_TMC]
      ).value,
      IDBrandTMC: document.getElementById(PropertySelectID[PropertyTMC.BRAND])
        .value,
      IDModel: document.getElementById(PropertySelectID[PropertyTMC.MODEL])
        .value,
      IDLocation: "0",
    };
    /*
    const formData = new FormData(); // Пустой FormData
    formData.append("ID_TMC", document.getElementById("inventoryId").value);
    formData.append("NameTMC", document.getElementById("txtNameTMC").value);
    formData.append("Status", "0");
    formData.append("SerialNumber", document.getElementById("txtSerialNum").value);
    formData.append("IDTypesTMC", document.getElementById(PropertySelectID[PropertyTMC.TYPE_TMC]).value);
    formData.append("IDBrandTMC", document.getElementById(PropertySelectID[PropertyTMC.BRAND]).value);
    formData.append("IDModel", document.getElementById(PropertySelectID[PropertyTMC.MODEL]).value);
    formData.append("IDLocation", "0");*/

    //console.log(formData);
    //url = `/src/BusinessLogic/createInventoryItem.php?type_id=${1}`;
    let url = "/src/BusinessLogic/createInventoryItem.php";

    try {
      const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        // Получаем текст ошибки от сервера
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }

      const result = await response.json();

      if (result) {
        console.log("Успех: ", result);
        closeModal();
        location.reload(); // Простое решение с перезагрузкой
        return true;
      } else {
        console.log("Ошибка:");
        return false;
      }
    } catch (error) {
      console.error("Произошла ошибка:", error);
      return false;
    }
  }

  const selectBox = document.getElementById("idTypeTMC");
  const image = document.getElementById("typeImage");
  const textField = document.getElementById("txtSerialNum");
  const checkBox = document.getElementById("checkSerialNum");

  async function autoResize(textarea) {
    console.log("autoResize");
    // Сброс высоты для пересчета
    textarea.style.height = "auto";

    // Установка новой высоты (scrollHeight содержит полную высоту контента)
    textarea.style.height = textarea.scrollHeight + "px";

    // Ограничение максимальной высоты (опционально)
    const maxHeight = 200; // Максимальная высота в пикселях
    if (textarea.scrollHeight > maxHeight) {
      textarea.style.overflowY = "auto";
      textarea.style.height = maxHeight + "px";
    } else {
      textarea.style.overflowY = "hidden";
    }
  }

  window.toggleSubMenu = toggleSubMenu;
  window.toggleSidebar = toggleSidebar;
  window.handleAction = handleAction;
  window.closeModal = closeModal;
  window.saveModal = saveModal;
  window.autoResize = autoResize;

  window.StatusHelper = {
    getDescription: (status) => {
      return StatusItem.getDescription(status) || "Неизвестный статус";
    },

    updateRowStatus: (row, newStatus) => {
      if (!row) return;

      // Обновляем ячейку статуса (предполагая, что это 5-я ячейка)
      if (row.cells.length > 4) {
        row.cells[4].textContent = this.getDescription(newStatus);
      }

      // Обновляем классы
      Object.values(StatusItem.statusClasses).forEach((className) => {
        row.classList.remove(className);
      });

      const newClass = StatusItem.getStatusClasses(newStatus);
      if (newClass) {
        row.classList.add(newClass);
      }

      // Обновляем data-атрибут
      row.dataset.status = newStatus;
    },
  };
})();
