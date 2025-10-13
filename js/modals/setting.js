(function () {
  // Функция для открытия панели администратора
  function openAdminPanel() {
    const modal = new bootstrap.Modal(
      document.getElementById("adminPanelModal")
    );
    modal.show();
  }

  // Функция для отображения сообщений в модальных окнах
  function showMessage(type, message) {
    const modalId = type === "error" ? "errorModal" : "successModal";
    const bodyId = modalId + "Body";

    console.log(`bodyId - ${bodyId}`);
    // Устанавливаем текст сообщения

    console.log(`message - ${message}`);
    document.getElementById(bodyId).textContent = message;

    // Показываем модальное окно
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();

    // Автоматическое скрытие через 5 секунд
    setTimeout(() => {
      modal.hide();
    }, 5000);
  }

  // Функция для сохранения сообщений в sessionStorage и перезагрузки страницы
  function showMessageAndReload(type, message) {
    sessionStorage.setItem(type + "Message", message);
    window.location.reload();
  }

  // Проверка сохраненных сообщений при загрузке страницы
  document.addEventListener("DOMContentLoaded", function () {
    // Проверка ошибок
    const errorMessage = sessionStorage.getItem("errorMessage");
    if (errorMessage) {
      showMessage("error", errorMessage);
      sessionStorage.removeItem("errorMessage");
    }

    // Проверка успешных сообщений
    const successMessage = sessionStorage.getItem("successMessage");
    if (successMessage) {
      showMessage("success", successMessage);
      sessionStorage.removeItem("successMessage");
    }

    // Закрытие модального окна при клике вне его области
    document.addEventListener("click", function (event) {
      const modals = document.querySelectorAll(".modal");
      modals.forEach((modal) => {
        if (event.target === modal) {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          modalInstance.hide();
        }
      });
    });
  });

  async function saveUsers() {
    const form = document.getElementById("users-form");
    const formData = new FormData(form);

    // Преобразуем FormData в объект для обработки
    const data = { users: {} };
    for (let [key, value] of formData.entries()) {
      // Извлекаем ID пользователя и имя поля из ключа
      const match = key.match(/^users\[(\d+)\]\[(\w+)\]$/);

      if (match) {
        const userId = match[1];
        const fieldName = match[2];

        // Инициализируем объект пользователя, если его еще нет
        if (!data.users[userId]) {
          data.users[userId] = { IDUser: userId }; // Добавляем IDUser
        }

        // Добавляем поле в объект пользователя
        data.users[userId][fieldName] = value;
      }
    }

    // Преобразуем объект в массив пользователей
    const usersArray = Object.values(data.users);

    /* for (let [key, value] of formData.entries()) {
    console.log(`${key}: ${value}`);
  }*/
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("adminPanelModal")
    );

    const response = await fetch(
      "/src/BusinessLogic/ActionsSetting/processUpdateUser.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ users: usersArray }),
      }
    );

    const dataResult = await response.json();

    if (dataResult.success) {
      showNotification(TypeMessage.success, dataResult.message);
      //modal.hide();
    } else {
      showNotification(TypeMessage.error, dataResult.message);
    }
  }

  // Функция для отображения уведомлений
  function showNotification(type, message) {
    const container = document.getElementById("notification-container");
    if (!container) return;

    // Создаем элемент уведомления
    const notification = document.createElement("div");
    notification.className = `notification notification-${TypeMessage.getStatusClasses(type)}`;

    // Иконка в зависимости от типа
    /*const icon =
      type === "error"
        ? '<i class="bi bi-exclamation-triangle-fill"></i>'
        : '<i class="bi bi-check-circle-fill"></i>';*/
    const icon = TypeMessage.getIconMessage(type);

    // Заголовок в зависимости от типа
    //const title = type === "error" ? "Ошибка" : "Успешно";
    const title = TypeMessage.getTitleMessage(type);

    notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close">&times;</button>
        `;

    // Добавляем уведомление в контейнер
    container.appendChild(notification);

    // Автоматическое скрытие через 5 секунд
    setTimeout(() => {
      closeNotification(notification);
    }, 5000);

    // Обработчик закрытия по клику
    notification
      .querySelector(".notification-close")
      .addEventListener("click", () => {
        closeNotification(notification);
      });
  }

  // Функция для закрытия уведомления с анимацией
  function closeNotification(notification) {
    if (!notification.classList.contains("fade-out")) {
      notification.classList.add("fade-out");
      setTimeout(() => {
        notification.remove();
      }, 5000);
    }
  }

  window.openAdminPanel = openAdminPanel;
  window.showNotification = showNotification;
  window.closeNotification = closeNotification;
  //window.showMessage = showMessage;
  window.saveUsers = saveUsers;
  //window.showMessageAndReload = showMessageAndReload;
})();
