import {
  executeEntityAction,
} from "../templates/entityActionTemplate.js";
import { Action } from "../../src/constants/actions.js";
import { showNotification } from "./setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";

export function initRepairBasketModalHandlers(modalElement) {
  // handlers attached via window.* below
  void modalElement;
}

window.returnFromBasket = returnFromBasket;
window.clearBasket = clearBasket;

function refreshBasketHeader(resultEntity) {
  const countElements = document.querySelectorAll(
    "#repairBasketModal .report-header p",
  );
  const rows = document.querySelectorAll(
    "#repairBasketModal tbody tr[id^='basket-item-']",
  );
  const count = rows.length;

  if (count === 0) {
    const tableWrap =
      document.querySelector("#repairBasketModal .table-responsive") ||
      document.querySelector("#repairBasketModal table")?.parentElement;
    tableWrap?.remove();
    document.querySelector("#repairBasketModal table")?.remove();
    document.getElementById("clearBasketBtn")?.remove();
    const header = document.querySelector("#repairBasketModal .report-header");
    if (header && !header.textContent.includes("Корзина пуста")) {
      header.innerHTML += "<p>Корзина пуста</p>";
    }
    return;
  }

  if (countElements.length > 1 && resultEntity) {
    countElements[1].textContent = `Количество позиций: ${resultEntity.totalCount ?? count}`;
    if (countElements[2]) {
      countElements[2].innerHTML = `Общая сумма ремонта: <strong>${resultEntity.formattedTotalCost ?? "0,00"} руб.</strong>`;
    }
  }
}

/** Очистить всю корзину ремонта */
async function clearBasket() {
  const rows = document.querySelectorAll(
    "#repairBasketModal tbody tr[id^='basket-item-']",
  );
  if (rows.length === 0) {
    showNotification(TypeMessage.notification, "Корзина уже пуста");
    return;
  }

  if (!confirm("Очистить корзину? Все позиции будут возвращены из корзины.")) {
    return;
  }

  try {
    const result = await executeEntityAction({
      action: Action.DELETE,
      formData: {},
      url: "/src/BusinessLogic/Actions/processCUDRepairInBasket.php",
      successMessage: "Корзина очищена",
    });

    if (result.success) {
      document
        .querySelector("#repairBasketModal .table-responsive")
        ?.remove();
      document.querySelector("#repairBasketModal table")?.remove();
      document.getElementById("clearBasketBtn")?.remove();
      const header = document.querySelector("#repairBasketModal .report-header");
      if (header) {
        header.innerHTML = `
          <p>Дата формирования: ${new Date().toLocaleDateString("ru-RU")}</p>
          <p>Количество позиций: 0</p>
          <p>Общая сумма ремонта: <strong>0,00 руб.</strong></p>
          <p>Корзина пуста</p>`;
      }
    } else {
      showNotification(
        TypeMessage.error,
        result.message || "Не удалось очистить корзину",
      );
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification(TypeMessage.error, error?.message || error);
  }
}

/**
 * Вернуть из корзины.
 * @param {number|string} tmcId
 * @param {number|string} [repairId]
 */
async function returnFromBasket(tmcId, repairId = 0) {
  if (!confirm("Вернуть эту запись из корзины в архив?")) {
    return;
  }

  const data = {
    ID_TMC: Number(tmcId) || 0,
    ID_Repair: Number(repairId) || 0,
  };

  try {
    const result = await executeEntityAction({
      action: Action.UPDATE,
      formData: data,
      url: "/src/BusinessLogic/Actions/processCUDRepairInBasket.php",
      successMessage: "Запись возвращена из корзины",
    });

    const rowId =
      data.ID_Repair > 0
        ? `basket-item-${data.ID_Repair}`
        : `basket-item-${data.ID_TMC}`;
    document.getElementById(rowId)?.remove();
    // старый формат строки по ID_TMC
    if (data.ID_Repair > 0) {
      document.getElementById(`basket-item-${data.ID_TMC}`)?.remove();
    }

    refreshBasketHeader(result.resultEntity);
  } catch (error) {
    console.error("Error:", error);
    showNotification(TypeMessage.error, error?.message || String(error));
  }
}
