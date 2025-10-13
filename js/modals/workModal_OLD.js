// Обработчик открытия модального окна "В работу ТМЦ"
(function () {
  function openWorkModal() {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );
    if (selectedRows.length === 0) {
      showNotification(TypeMessage.notification,"Выберите хотя бы один ТМЦ для передачи в работу");
      return;
    }

    const modalTable = document.querySelector("#selectedWorkItemsTable");
    modalTable.innerHTML = "";

    // Собираем данные о выбранных ТМЦ
    window.selectedWorkTMCIds = [];

    selectedRows.forEach((row) => {
      const cells = row.cells;
      window.selectedWorkTMCIds.push(cells[0].textContent);

      modalTable.innerHTML += `
            <tr>
                <td>${cells[0].textContent}</td>
                <td>${cells[1].textContent}</td>
                <td>${cells[2].textContent}</td>
                <td>${cells[6].textContent}</td>
            </tr>
        `;
    });

    // Показываем модальное окно
    const modal = new bootstrap.Modal(document.getElementById("workModal"));
    modal.show();
  }

  // Обработчики для кнопок расширения/сворачивания

  document.getElementById("btnExpand").addEventListener("click", function () {
    document.getElementById("mainSection").classList.remove("col-md-8");
    document.getElementById("mainSection").classList.add("col-md-8");
    document.getElementById("createBrigadeSection").style.display = "block";
  });

  document.getElementById("btnCollapse").addEventListener("click", function () {
    document.getElementById("createBrigadeSection").style.display = "none";
  });

  document
    .getElementById("btnCancelCreate")
    .addEventListener("click", function () {
      document.getElementById("createBrigadeSection").style.display = "none";
    });

  // Обработчик создания бригады
  document
    .getElementById("createBrigadeForm")
    .addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      try {
        const response = await fetch("/src/BusinessLogic/createBrigade.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          // Добавляем новую бригаду в выпадающий список
          const select = document.getElementById("brigadeSelect");
          const option = document.createElement("option");
          option.value = data.id;
          option.textContent = `${formData.get("brigade_name")} (${formData.get(
            "brigadir"
          )})`;
          option.selected = true;
          select.appendChild(option);

          // Закрываем секцию создания
          document.getElementById("createBrigadeSection").style.display =
            "none";

          // Очищаем форму
          this.reset();
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error,"Произошла ошибка при создании бригады");
      }
    });

  // Обработчик передачи ТМЦ в работу
  document
    .getElementById("workForm")
    .addEventListener("submit", async function (e) {
      e.preventDefault();

      const brigadeId = document.getElementById("brigadeSelect").value;
      if (!brigadeId) {
        showNotification(TypeMessage.notification, "Выберите бригаду");
        return;
      }

      if (
        !window.selectedWorkTMCIds ||
        window.selectedWorkTMCIds.length === 0
      ) {
        showNotification(TypeMessage.notification, "Не выбрано ни одного ТМЦ для передачи");
        return;
      }

      const modalElement = document.getElementById("workModal");
      const modal = bootstrap.Modal.getInstance(modalElement);

      const formData = new FormData(this);
      formData.append("tmc_ids", JSON.stringify(window.selectedWorkTMCIds));
      formData.append("brigade_id", JSON.stringify(brigadeId));
      /*
      
      */

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processWorkTMC.php",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              tmc_ids: window.selectedWorkTMCIds,
              brigade_id: brigadeId,
            }),
          }
        );

        const data = await response.json();

        if (data.success) {
          // Обновляем статусы в таблице
          updateInventoryStatus(window.selectedWorkTMCIds, StatusItem.AtWorkTMC);
          // Обновляем счетчики
          updateCounters({brigadesToItemsCount: window.selectedWorkTMCIds.length});

          modal.hide();
          //location.reload();
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Error:", error);
        showNotification(TypeMessage.error, "Произошла ошибка при передаче ТМЦ в работу");
      }
    });

  // Отправить в сервис
  function sendToService(NameClassContainer, status) {
    const selectedRows = document.querySelectorAll(
      `.${NameClassContainer}.selected`
    );
    if (selectedRows.length === 0) {
      showNotification(TypeMessage.notification, "Выберите ТМЦ для отправки в сервис");
      return;
    }
    let nameColumn = "";
    let nameBt = "";
    let title = "";
    let validStatuses = [];

    //console.log(`Статус сервера - ${status}`);
    
    // Очищаем контейнер
    const container = document.getElementById("serviceItemsContainer");
    container.innerHTML = "";
    switch (status) {
      case ServiceStatus.sendService:
        nameColumn = "Причина ремонта";
        nameBt = "Отправить";
        title = "Отправить в сервис";
        validStatuses = [StatusItem.Released, StatusItem.AtWorkTMC];
        break;
      case ServiceStatus.returnService:
        nameColumn = "Коментарии ";
        nameBt = "Вернуть";
        title = "Вернуть из сервиса";
        validStatuses = [StatusItem.Repair];
        break;
    }

    // Заполняем контейнер выбранными ТМЦ
    selectedRows.forEach((row) => {
      const status = parseInt(row.getAttribute("data-status"));
      if (validStatuses.includes(status)) {
        const id = row.getAttribute("data-id");
        const name = row.cells[1].textContent; // Наименование из второго столбца

        const tr = document.createElement("tr");
        tr.innerHTML = `
                                <td>${id}</td>
                                <td>${name}</td>
                                <td>
                                    <textarea class="repair-reason-input" 
                                            data-id="${id}" 
                                            required></textarea>
                                </td>
                            `;
        container.appendChild(tr);
      }
    });
    document.getElementById("serviceModal")?.setAttribute("data-status", status);
    document.getElementById("colReason").textContent = nameColumn;
    document.getElementById("title").textContent = title;
    document.getElementById("btnSubmitService").textContent = nameBt;

    // Показываем модальное окно отправки в сервис
    const serviceModal = new bootstrap.Modal(document.getElementById("serviceModal"));
    serviceModal.show();
  }

  window.openWorkModal = openWorkModal;
  window.sendToService = sendToService;
})();
