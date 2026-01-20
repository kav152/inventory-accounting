import { TypeMessage } from "../../src/constants/typeMessage.js";
import { showNotification } from "../modals/setting.js";
import { Action } from "../../src/constants/actions.js";

/**
 * Универсальный шаблон для выполнения CUD операций с сущностями
 * @param {Object} config - Конфигурация операции
 * @param {string} config.action - Действие (CREATE, UPDATE, DELETE)
 * @param {Object} config.formData - Данные формы
 * @param {string} config.url - URL для обработки
 * @param {Function} [config.successCallback] - Колбэк при успешном выполнении
 * @param {Function} [config.errorCallback] - Колбэк при ошибке
 * @param {string} config.successMessage - Сообщение об успехе
 * @returns {Promise<Object>} Результат операции
 */
export async function executeEntityAction(config) {
  const {
    action,
    formData,
    url,
    successCallback,
    errorCallback,
    successMessage = "Операция выполнена успешно",
  } = config;

  try {
    // Добавляем действие в данные
    const requestData = {
      ...formData,
      statusEntity: action,
    };

    //console.log("requestData:");
    //console.log(requestData);
    //console.log(url);

    // Выполняем запрос
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(requestData),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      if (successMessage) {
        showNotification(TypeMessage.success, successMessage);
      }
      if (successCallback) {
        successCallback(result);
      }
      return result;
    } else {
      throw new Error(result.message || "Ошибка при выполнении операции");
    }
  } catch (error) {
    console.error(`Ошибка при выполнении действия ${action}:`, error);
    const errorMessage =
      error.message || `Ошибка при выполнении действия ${action}`;

    if (errorCallback) {
      errorCallback(error);
    } else {
      showNotification(TypeMessage.error, errorMessage);
    }
    throw error;
  }
}
/**
 * Собирает данные формы в объект с указанным статусом действия
 * @param {HTMLFormElement} form - HTML-форма, из которой собираются данные
 * @param {Action} statusEntity - Статус действия (Create/Update/Delete)
 * @returns {Object} Объект с данными формы и статусом действия
 */
export function getCollectFormData(form, statusEntity) {
  try {
    const formData = new FormData(form);
    const data = {
      statusEntity: statusEntity,
    };

    // Преобразуем FormData в объект
    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    return data;
  } catch (error) {
    console.error(`Ошибка при формировании коллекции:`, error);
  }
}