import {
  executeEntityAction,
  getCollectFormData,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { openEntityModal } from "../modals/modalLoader.js";
import { executeActionForCUD } from "../templates/cudRowsInTable.js";

(function () {
  /**
   * Открыть модальное окно RepairBasket
   * @param {Action} action - действие (CREATE, UPDATE, DELETE)
   */
  function openRepairBasketModal(action) {
    openEntityModal(action, "repairBasketModal");
  }

  window.openRepairBasketModal = openRepairBasketModal;
  window.returnFromBasket = returnFromBasket;
})();

/**
 * Обработчик работы модального окна RepairBasket
 * @param {HTMLElement} modalElement
 */
export function initRepairBasketModalHandlers(modalElement) {
  // 1. Инициализация обработчиков формы
  modalElement.addEventListener("submit", async function (e) {
    e.preventDefault();
    await handleRepairBasketFormSubmit(modalElement);
  });

  // 2. Инициализация динамических элементов (если нужны)
  //initDynamicElements(modalElement);
}

/**
 * Инициализация динамических элементов
 */
function initDynamicElements(modalElement) {
  // Пример: обновление заголовка модального окна
  const modalTitle = modalElement.querySelector("#RepairBasketModalTitle");
  const statusEntity = window.statusEntity;

  if (statusEntity === Action.UPDATE) {
    modalTitle.textContent = "Редактировать [entityName]";
  } else {
    modalTitle.textContent = "Добавить [entityName]";
  }
}

/**
 * Обработчик отправки формы
 */
async function handleRepairBasketFormSubmit(modalElement) {
  const form = modalElement.querySelector("#RepairBasketForm");
  const RepairBasketData = getCollectFormData(form, window.statusEntity);

  try {
    const result = await executeEntityAction({
      action: window.statusEntity,
      formData: RepairBasketData,
      url: "/src/BusinessLogic/Actions/processCUDRepairBasket.php",
      successMessage:
        "[EntityName] успешно " +
        (window.statusEntity === Action.CREATE ? "добавлен" : "обновлен"),
    });

    executeActionForCUD(
      window.statusEntity,
      result.resultEntity,
      "RepairBasketTableContainer",
      result.fields,
      "row-RepairBasket",
      "id"
    );

    // Закрываем модальное окно
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    modalInstance.hide();
  } catch (error) {
    console.error("Ошибка:", error);
  }
}

// Функция возврата элемента из корзины
async function returnFromBasket(id) {
  if (confirm("Вы уверены, что хотите вернуть этот элемент из корзины?")) {
    //const formData = new FormData();
    //formData.append("ID_TMC", id);

    const data = 
    {
      ID_TMC: id
    };

    try {
      /*const response = await fetch(
        "/src/BusinessLogic/ActionsTMC/processRepairInBasket.php",
        {
          method: "POST",
          body: formData,
        }
      );
      const data = await response.json();*/

      const result = await executeEntityAction({
            action: Action.UPDATE,
            formData: data,
            url: "/src/BusinessLogic/Actions/processCUDRepairInBasket.php",
            successMessage: "ТМЦ успешно сохранен",
        });



      if (result.resultEntity) {
        // Удаляем строку из таблицы
        document.getElementById(`basket-item-${id}`).remove();

        const countElements = document.querySelectorAll(
          "#repairBasketModal .report-header p"
        );

        // Обновляем информацию о количестве элементов
        const rows = document.querySelectorAll("#repairBasketModal tbody tr");
        const count = Array.from(rows).filter((row) =>
          row.id.startsWith("basket-item-")
        ).length;

        if (count === 0) {
          document.querySelector("#repairBasketModal table").remove();
          document.querySelector(
            "#repairBasketModal .report-header"
          ).innerHTML += "<p>Корзина пуста</p>";
        } else {
          if (countElements.length > 1) {
            // Первый параграф - количество позиций
            countElements[1].textContent = `Количество позиций: ${result.resultEntity.totalCount}`;
            // Второй параграф - общая сумма
            countElements[2].innerHTML = `Общая сумма ремонта: <strong>${result.resultEntity.formattedTotalCost} руб.</strong>`;
          }
        }
      } else {
        showNotification(TypeMessage.error, result.message);
      }
    } catch (error) {
      console.error("Error:", error);
      showNotification(TypeMessage.error, error);
    }
  }
}

/*
function returnFromBasket(id) {
  if (confirm("Вы уверены, что хотите вернуть этот элемент из корзины?")) {
    const formData = new FormData();
    formData.append("ID_TMC", id);

    fetch("/src/BusinessLogic/ActionsTMC/processRepairInBasket.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Удаляем строку из таблицы
          document.getElementById(`basket-item-${id}`).remove();

          // Обновляем информацию о количестве элементов
          const rows = document.querySelectorAll("#basketContent tbody tr");
          const count = Array.from(rows).filter((row) =>
            row.id.startsWith("basket-item-")
          ).length;

          if (count === 0) {
            document.querySelector("#basketContent table").remove();
            document.querySelector("#basketContent .report-header").innerHTML +=
              "<p>Корзина пуста</p>";
          }
        } else {
          showNotification(TypeMessage.error, "Ошибка: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Ошибка:", error);
        showNotification(TypeMessage.error, "Ошибка возврата из корзины");
      });
  }
}*/
