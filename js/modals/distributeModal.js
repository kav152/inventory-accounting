// Обработчик клика на "Передать ТМЦ"
(function () {
  let distributeModalInstance = null;

  function openDistributeModal(StatusUser) {
    const selectedRows = document.querySelectorAll(
      "#inventoryTable tbody tr.row-container.selected"
    );
    if (selectedRows.length === 0) {
      window.showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для передачи"
      );
      return;
    }
    //validStatuses = [StatusItem.Released, StatusItem.AtWorkTMC];
    let validStatuses = [StatusItem.Released];
    if (StatusUser == 0) {
      validStatuses = [StatusItem.Released, StatusItem.NotDistributed]; // Добавляем в конец массива
    }

    console.log(`StatusUser ${StatusUser}`);
    window.openModalAction("distribute", selectedRows, validStatuses);
    window.removingSelection();
  }

  function initDistributeHandlers(modalElement) {
    const form = document.getElementById("distributeForm");
    if (!form) return;

    form.onsubmit = async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append("tmc_ids", JSON.stringify(window.selectedTMCIds));

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processDistributeTMC.php",
          {
            method: "POST",
            body: formData,
          }
        );

        const data = await response.json();

        if (data.success) {
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();

          showNotification(TypeMessage.success, data.message);

          // Обновляем статусы в таблице
          if (typeof updateInventoryStatus === "function") {
            updateInventoryStatus(
              window.selectedTMCIds,
              StatusItem.ConfirmItem
            );
          }
        } else {
          showNotification(TypeMessage.error, data.message);
        }
      } catch (error) {
        console.error("Ошибка отправки:", error);
        showNotification(TypeMessage.error, "Ошибка сети");
      }
    };
  }

  window.openDistributeModal = openDistributeModal;
  window.initDistributeHandlers = initDistributeHandlers;
})();
