(function () {
  
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

      const response = await fetch(
        `/src/View/Modal/modal_handler.php?${params}`
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

      // Инициализируем модальное окно
      const modalId = getModalIdByType(type);
      const modalElement = document.getElementById(modalId);

      if (!modalElement) throw new Error("Модальное окно не найдено");
      const modalInstance = new bootstrap.Modal(modalElement);

      modalInstance.show();
      // Заполняем таблицу выбранными ТМЦ (если нужно)
      if (selectedRows) {
        fillSelectedItemsTable(type, selectedRows, validStatuses);
      }
      getModalParams(type, validStatuses);
      // Инициализируем обработчики
      initModalHandlers(type, modalElement);
    } catch (error) {
      showNotification(TypeMessage.error, error);
      console.error(error);
    }
  }

  // Типы индификторов модального окна
  function getModalIdByType(type) {
    const modalIds = {
      work: "workModal",
      at_work: "atWorkModal",
      confirm: "confirmModal",
      confirmRepair: "confirmRepairModal",
      distribute: "distributeModal",
      sendToService: "serviceModal",
      create: "cardItemModal",
      create_analog: "cardItemModal",
      edit: "cardItemModal",
      edit_write_off: "edit_write_off",
    };
    return modalIds[type] || null;
  }
  //Индификаторы tbody таблицы ТМЦ
  function fillSelectedItemsTable(type, selectedRows, validStatuses = []) {
    const tableSelectors = {
      work: "#selectedWorkItemsTable",
      at_work: "#selectedItemsTable",
      confirm: "#selectedItemsTable",
      confirmRepair: "#selectedItemsTable",
      distribute: "#selectedItemsTable",
      sendToService: "#selectedServiceItemsContainer",
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

    selectedRows.forEach((row) => {
      const cells = row.cells;
      const status = parseInt(row.getAttribute("data-status"));

      if (validStatuses.includes(status)) {
        window.selectedTMCIds.push(cells[0].textContent);

        table.innerHTML += fillInTable(type, cells);
      }
    });
  }

  // Заполнить таблицу в зависимости от типа модального окна
  function fillInTable(type, cells = []) {
    let html = null;
    switch (type) {
      case "work":
      case "at_work":
      case "confirm":
      case "confirmRepair":
      case "distribute":
        html = `
            <tr>
                <td>${cells[0].textContent}</td>
                <td>${cells[1].textContent}</td>
                <td>${cells[2].textContent}</td>
                <td>${cells[6].textContent}</td>
            </tr>
        `;
        break;
      case "sendToService":
        html = `
            <tr>
                <td>${cells[0].textContent}</td>
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
    return html;
  }

  function initModalHandlers(type, modalElement) {
    switch (type) {
      case "work":
        initWorkModalHandlers(modalElement);
        break;
      case "at_work":
        initAtWorkModalHandlers(modalElement);
        break;
      case "confirm":
        initConfirmModalHandlers(modalElement);
        break;
      case "confirmRepair":
        initConfirmRepairModalHandlers(modalElement);
        break;
      case "distribute":
        initDistributeHandlers(modalElement);
        break;
      case "sendToService":
        initSendToServiceModalHandlers(modalElement);
      case "create":
      case "create_analog":
      case "edit":
        initCardTMCModalHandlers(modalElement);
      case "edit_write_off":
        initCardWriteOffHandlers(modalElement);
    }
  }

  function getModalParams(type, validStatuses) {
    switch (type) {
      case "sendToService":
        getServiceModalParams(validStatuses);
      default:
        return {};
    }
  }

  // Функция для определения параметров модального окна сервиса
  function getServiceModalParams(validStatuses) {
    let params = {};
    if (validStatuses.includes(StatusItem.Repair)) {
      params = {
        nameColumn: "Комментарии",
        nameBt: "Вернуть",
        title: "Вернуть из сервиса",
        statusService: ServiceStatus.returnService,
      };
      console.log('StatusItem.Repair ');
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

  window.openModalAction = openModalAction;
})();
