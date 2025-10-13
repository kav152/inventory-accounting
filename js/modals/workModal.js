// Обработчик открытия модального окна "В работу ТМЦ"
(function () {
  function openWorkModal() {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );
    if (selectedRows.length === 0) {
      showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для передачи в работу"
      );
      return;
    }
    validStatuses = [StatusItem.Released];
    openModalAction("work", selectedRows, validStatuses);
    window.removingSelection();
  }

  function initWorkModalHandlers(modalElement) {
    // Обработчики для кнопок расширения/сворачивания
    modalElement
      .querySelector("#btnExpand")
      .addEventListener("click", function () {
        modalElement.querySelector("#createBrigadeSection").style.display =
          "block";
      });
    // Обработчики для кнопок расширения/сворачивания

    document.getElementById("btnExpand").addEventListener("click", function () {
      document.getElementById("mainSection").classList.remove("col-md-8");
      document.getElementById("mainSection").classList.add("col-md-8");
      document.getElementById("createBrigadeSection").style.display = "block";
    });

    document
      .getElementById("btnCollapse")
      .addEventListener("click", function () {
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
            option.textContent = `${formData.get(
              "brigade_name"
            )} (${formData.get("brigadir")})`;
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
          showNotification(
            TypeMessage.error,
            "Произошла ошибка при создании бригады"
          );
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
          !window.selectedTMCIds ||
          window.selectedTMCIds.length === 0
        ) {
          showNotification(
            TypeMessage.notification,
            "Не выбрано ни одного ТМЦ для передачи"
          );
          return;
        }

        const modalElement = document.getElementById("workModal");
        const modal = bootstrap.Modal.getInstance(modalElement);

        const formData = new FormData(this);
        formData.append("tmc_ids", JSON.stringify(window.selectedTMCIds));
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
                tmc_ids: window.selectedTMCIds,
                brigade_id: brigadeId,
              }),
            }
          );

          const data = await response.json();

          if (data.success) {
            // Обновляем статусы в таблице
            updateInventoryStatus(
              window.selectedTMCIds,
              StatusItem.AtWorkTMC
            );
            // Обновляем счетчики
            updateCounters({
              brigadesToItemsCount: window.selectedTMCIds.length,
            });

            modal.hide();
            //location.reload();
          } else {
            showNotification(TypeMessage.error, data.message);
          }
        } catch (error) {
          console.error("Error:", error);
          showNotification(
            TypeMessage.error,
            "Произошла ошибка при передаче ТМЦ в работу"
          );
        }
      });
  }
  
  window.openWorkModal = openWorkModal;  
  window.initWorkModalHandlers = initWorkModalHandlers;
})();
