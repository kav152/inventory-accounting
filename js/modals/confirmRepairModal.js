(function () {
  // Обработчики для кнопок "В ремонт" и "Списать"
  document.querySelectorAll(".itemRepair-row").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const isRepair = this.classList.contains("btn-repair");
      const formRow = document.getElementById(`repairForm${id}`);

      // Скрываем все открытые формы
      document.querySelectorAll(".repair-form").forEach((row) => {
        row.style.display = "none";
      });

      // Показываем нужную форму
      formRow.style.display = "table-row";

      // Показываем соответствующую форму
      const repairForm = formRow.querySelector(".repair-data-form");
      const writeOffForm = formRow.querySelector(".write-off-form");
    });
  });

  async function sendForRepair(idTMC, action) {

    console.log(action);
    const repairFormContainer = document.getElementById(`repairForm${idTMC}`);
    // Извлекаем саму форму внутри контейнера
    const form = repairFormContainer.querySelector("form.repair-data-form");
    const formData = new FormData(form);
    formData.append("action", action);

   /* for (let [key, value] of formData.entries()) {
      console.log(`${key}: ${value}`);
    }*/

    try {
      const response = await fetch(
        "/src/BusinessLogic/ActionsTMC/processConfirmRepair.php",
        {
          method: "POST",
          body: formData,
        }
      );

      const result = await response.json();

      if (result.success) {
        //alert("Файл успешно загружен!");
        // Дополнительные действия после успешной отправки
        //console.log(result.file_path);

        // Удаляем строки с данным id
        const itemRow = document.querySelector(
          `tr.itemRepair-row[data-id="${idTMC}"]`
        );
        const formRow = document.getElementById(`repairForm${idTMC}`);

        if (itemRow) itemRow.remove();
        if (formRow) formRow.remove();


        updateInventoryStatus([idTMC], action == 'writeOff' ? StatusItem.WrittenOff : StatusItem.Repair);

        // Обновляем счетчик в верхней панели
        const badge = document.getElementById("confirmRepairBadge");
        const notification = document.getElementById(
          "confirmRepairNotification"
        );
        const count = parseInt(badge.textContent)-1;
        //console.log("Кол-во");
        //console.log(count);
        badge.textContent = count;
        notification.textContent = `Подтвердить ремонт ${count} ТМЦ`;

        // Проверяем, остались ли еще строки в таблице
        const remainingRows = document.querySelectorAll("tr.itemRepair-row");
        if (remainingRows.length === 0) {
          // Закрываем модальное окно
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("confirmRepairModal")
          );
          modal.hide();
          
        }

        /* === */
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error("Ошибка:", error);
      showNotification(
        TypeMessage.error,
        `Ошибка при отправке: ${error.message}`
      );
    }

    //console.log(`ИД:${idTMC}. ${action}. ${IDLocation}`);
  }

  window.sendForRepair = sendForRepair;
})();
