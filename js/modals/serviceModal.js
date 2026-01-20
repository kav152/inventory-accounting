import { ServiceStatus } from '../../src/constants/statusService.js';

(function () {
  // Отправить в сервис
  function sendToService(NameClassContainer, serviceStatus) {
    const selectedRows = document.querySelectorAll(
      `.${NameClassContainer}.selected`
    );
    if (selectedRows.length === 0) {
      showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для отправки в сервис"
      );
      return;
    }

    let nameColumn = "";
    let nameBt = "";
    let title = "";
    let validStatuses = [];

    // Очищаем контейнер
    //const container = document.getElementById("serviceItemsContainer");
    //container.innerHTML = "";
    switch (serviceStatus) {
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

    console.log(selectedRows);
    // serviceModal
    // sendToService
    openModalAction("serviceModal", selectedRows, validStatuses);
  }

  window.sendToService = sendToService;
  //window.initSendToServiceModalHandlers = initSendToServiceModalHandlers;
})();

/**
 * Обработчик serviceModal
 * @param {*} modalElement 
 */
export function initSendToServiceModalHandlers(modalElement) {
  document
    .getElementById("btnSubmitService")
    .addEventListener("click", async function () {
      const inputs = document.querySelectorAll(
        "#selectedServiceItemsContainer .repair-reason-input"
      );
      // .repair-reason-input
      let allFilled = true;
      const items = [];
      let statusService = document
        .getElementById("serviceModal")
        .getAttribute("data-status");

      inputs.forEach((textarea) => {
        const reason = textarea.value.trim();
        const id = textarea.dataset.id; // или textarea.getAttribute("data-id")
        items.push({ id: id, reason: reason });

        if (ServiceStatus.sendService == statusService) {
          if (!reason.trim()) {
            allFilled = false;
          }
        }
      });

      if (!allFilled) {
        if (ServiceStatus.sendService == statusService) {
          showNotification(
            TypeMessage.notification,
            "Заполните причину ремонта для выбранных ТМЦ"
          );
          return;
        }
        if (ServiceStatus.returnService == statusService) {
        }
      }

      try {
        const response = await fetch(
          "/src/BusinessLogic/ActionsTMC/processSendToService.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              items: items,
              statusService: statusService,
            }),
          }
        );

        const data = await response.json();

        if (data.success) {
          updateInventoryStatus(
            window.selectedTMCIds,
            ServiceStatus.sendService == statusService
              ? StatusItem.ConfirmRepairTMC
              : ServiceStatus.returnService == statusService
                ? StatusItem.Released
                : -1
          );

          hideRowsInAtWorkModal(items);

          // ОБНОВЛЕНИЕ СЧЕТЧИКОВ: только для статуса "Отправить в сервис"
          if (statusService === ServiceStatus.sendService) {            
            updateCounters({ confirmRepairCount: items.length, });
          }

          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();
        }
      } catch (error) {
        console.error(error);
        showNotification(TypeMessage.error, error);
      }
    });
}

/**
 * Скрывает строки в модальном окне atWorkModal по ID
 * @param {Array} items - массив объектов с id и reason
 */
function hideRowsInAtWorkModal(items) {
  // Проверяем, открыто ли модальное окно atWorkModal
  const atWorkModal = document.getElementById('atWorkModal');
  const modalInstance = bootstrap.Modal.getInstance(atWorkModal);
  
  // Если модальное окно не открыто, выходим
  if (!modalInstance || !modalInstance._isShown) {
    return;
  }

  // Скрываем строки в atWorkModal
  items.forEach(item => {
    const row = atWorkModal.querySelector(`.row-container1[data-id="${item.id}"]`);
    if (row) {
      row.style.display = 'none';
    }
  });

  // Обновляем счетчики в группах atWorkModal
  updateAtWorkGroupCounters();
}