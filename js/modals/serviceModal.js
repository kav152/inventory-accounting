import { ServiceStatus } from "../../src/constants/statusService.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { Action } from "../../src/constants/actions.js";
import { StatusItem } from "../../src/constants/statusItem.js";
import { showNotification } from "./setting.js";
import { executeEntityAction } from "../templates/entityActionTemplate.js";
import { updateInventoryStatus } from "../updateFunctions.js";

(function () {
  function sendToService(NameClassContainer, serviceStatus) {
    const selectedRows = document.querySelectorAll(
      `.${NameClassContainer}.selected`,
    );
    if (selectedRows.length === 0) {
      showNotification(
        TypeMessage.notification,
        "Выберите ТМЦ для отправки в сервис",
      );
      return;
    }

    let validStatuses = [];
    switch (serviceStatus) {
      case ServiceStatus.sendService:
        validStatuses = [StatusItem.Released, StatusItem.AtWorkTMC];
        break;
      case ServiceStatus.returnService:
        validStatuses = [StatusItem.Repair];
        break;
    }

    openModalAction("serviceModal", selectedRows, validStatuses);
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
      const items = [];
      const statusService = document
        .getElementById("serviceModal")
        .getAttribute("data-status");

      inputs.forEach((textarea) => {
        const reason = textarea.value.trim();
        items.push({ id: textarea.dataset.id, reason });
        if (ServiceStatus.sendService == statusService && !reason) {
          allFilled = false;
        }
      });

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
            ? StatusItem.ConfirmRepairTMC
            : ServiceStatus.returnService == statusService
              ? StatusItem.Released
              : -1;

        updateInventoryStatus(window.selectedTMCIds, newStatus);
        hideRowsInAtWorkModal(items);

        if (statusService == ServiceStatus.sendService) {
          const bump = {
            confirmRepairCount: items.length,
            brigadesToItemsCount: -items.length,
          };
          if (typeof window.updateCounters === "function") {
            window.updateCounters(bump);
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
