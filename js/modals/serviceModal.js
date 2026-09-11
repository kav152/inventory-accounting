import { ServiceStatus } from "../../src/constants/statusService.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { Action } from "../../src/constants/actions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { showNotification } from "./setting.js";
import { executeEntityAction } from "../templates/entityActionTemplate.js";
import { updateInventoryStatus } from "../updateFunctions.js";

(function () {
  function sendToService(NameClassContainer, serviceStatus, clickEvent = null) {
    clickEvent?.preventDefault?.();

    let selectedRows = Array.from(
      document.querySelectorAll(`.${NameClassContainer}.selected`),
    );

    // если строка не выделена, но открыта карточка справа — берём её
    if (selectedRows.length === 0) {
      const cardId = document
        .querySelector("#cardContainer[data-id], #resultContainer [data-id]")
        ?.getAttribute("data-id");
      if (cardId) {
        const row = document.querySelector(
          `.${NameClassContainer}[data-id="${cardId}"]`,
        );
        if (row) {
          document
            .querySelectorAll(`.${NameClassContainer}.selected`)
            .forEach((r) => r.classList.remove("selected"));
          row.classList.add("selected");
          selectedRows = [row];
        }
      }
    }

    if (selectedRows.length === 0) {
      showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для отправки в сервис",
      );
      return;
    }

    const status = Number(serviceStatus);
    let validStatuses = [];
    switch (status) {
      case ServiceStatus.sendService:
        validStatuses = [StatusItem.Released, StatusItem.AtWorkTMC];
        break;
      case ServiceStatus.returnService:
        validStatuses = [StatusItem.Repair, StatusItem.ConfirmRepairTMC];
        break;
      default:
        showNotification(TypeMessage.error, "Неизвестный тип операции сервиса");
        return;
    }

    if (typeof window.openModalAction !== "function") {
      showNotification(
        TypeMessage.error,
        "Модуль модальных окон не загружен. Обновите страницу (Ctrl+F5).",
      );
      return;
    }

    window.openModalAction("serviceModal", selectedRows, validStatuses);
  }

  window.sendToService = sendToService;
})();

export function initSendToServiceModalHandlers(modalElement) {
  document
    .getElementById("btnSubmitService")
    .addEventListener("click", async function () {
      const inputs = document.querySelectorAll(
        "#selectedServiceItemsContainer .repair-reason-input",
      );
      let allFilled = true;
      let datesOk = true;
      const items = [];
      const statusService = document
        .getElementById("serviceModal")
        .getAttribute("data-status");

      inputs.forEach((textarea) => {
        const id = textarea.dataset.id;
        const reason = textarea.value.trim();
        const dateInput = document.querySelector(
          `#selectedServiceItemsContainer .service-date-input[data-id="${id}"]`,
        );
        const operationDate = (dateInput?.value || "").trim();
        items.push({ id, reason, operationDate });
        if (ServiceStatus.sendService == statusService && !reason) {
          allFilled = false;
        }
        if (!operationDate) {
          datesOk = false;
          dateInput?.classList.add("error");
        } else {
          dateInput?.classList.remove("error");
        }
      });

      if (!datesOk) {
        showNotification(
          TypeMessage.notification,
          "Укажите дату для каждого выбранного ТМЦ",
        );
        return;
      }

      if (!allFilled && ServiceStatus.sendService == statusService) {
        showNotification(
          TypeMessage.notification,
          "Заполните причину ремонта для выбранных ТМЦ",
        );
        return;
      }

      try {
        const result = await executeEntityAction({
          action: Action.UPDATE,
          formData: { items, statusService },
          url: "/src/BusinessLogic/Actions/processCUDSendToService.php",
          successMessage: "ТМЦ успешно переданы",
        });

        const ok =
          !!result?.resultEntity &&
          (result.resultEntity.success === undefined ||
            result.resultEntity.success === true);

        if (!ok) {
          const messages = result?.resultEntity?.messages;
          showNotification(
            TypeMessage.error,
            Array.isArray(messages)
              ? messages.join("; ")
              : messages || "Ошибка при отправке в сервис",
          );
          return;
        }

        const newStatus =
          ServiceStatus.sendService == statusService
            ? StatusItem.Repair
            : ServiceStatus.returnService == statusService
              ? StatusItem.Released
              : -1;

        updateInventoryStatus(window.selectedTMCIds, newStatus);
        hideRowsInAtWorkModal(items);

        if (statusService == ServiceStatus.sendService) {
          if (typeof window.updateCounters === "function") {
            window.updateCounters({ confirmRepairCount: items.length });
          }
        }

        const modal = bootstrap.Modal.getInstance(modalElement);
        modalElement.addEventListener(
          "hidden.bs.modal",
          function onHidden() {
            restoreAtWorkModal();
            modalElement.removeEventListener("hidden.bs.modal", onHidden);
          },
          { once: true },
        );
        modal?.hide();
      } catch (error) {
        console.error(error);
        showNotification(TypeMessage.error, error.message || String(error));
      }
    });
}

function hideRowsInAtWorkModal(items) {
  const atWorkModal = document.getElementById("atWorkModal");
  if (!atWorkModal) return;
  const modalInstance = bootstrap.Modal.getInstance(atWorkModal);
  if (!modalInstance || !modalInstance._isShown) return;

  items.forEach((item) => {
    const row = atWorkModal.querySelector(
      `.row-container1[data-id="${item.id}"]`,
    );
    if (row) row.style.display = "none";
  });
}

function restoreAtWorkModal() {
  const atWorkModal = document.getElementById("atWorkModal");
  if (atWorkModal && atWorkModal.classList.contains("show")) return;

  if (window.previousModal === "atWorkModal") {
    const modalInstance = bootstrap.Modal.getInstance(atWorkModal);
    if (modalInstance) {
      setTimeout(() => modalInstance.show(), 300);
    }
    window.previousModal = null;
  }
}
