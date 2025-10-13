// Обработка действий с ТМЦ. Принять или отказать!
function processItem(id, action) {
  fetch(
    `/src/BusinessLogic/ActionsTMC/processConfirmTMC.php?id=${id}&action=${action}`
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Удаляем строку из таблицы
        document.getElementById(`itemRow${id}`).remove();

        // Обновляем счетчик уведомлений
        const badge = document.getElementById("confirmBadge");
        const notification = document.getElementById("confirmNotification");
        const count = parseInt(badge.textContent) - 1;
        needFullReload = true;

        if (count > 0) {
          badge.textContent = count;
          notification.textContent = `Принять ${count} ТМЦ`;
        } else {
          // Скрываем уведомление если элементов не осталось
          badge.remove();
          notification.remove();
          // Закрываем модальное окно
          bootstrap.Modal.getInstance(
            document.getElementById("confirmModal")
          ).hide();
        }
      } else {
        showNotification(TypeMessage.error, "Ошибка: " + data.message);
      }
    });
}
