<?php

?>
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Справка по работе с системой</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="helpAccordion">
                    <!-- Раздел 1: Работа с пользователями -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                <i class="bi bi-people me-2"></i>1. Работа с пользователями
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Добавление пользователя:</h6>
                                <ol>
                                    <li>Перейдите в раздел "Администрирование"</li>
                                    <li>На вкладке "Пользователи" нажмите кнопку "Добавить"</li>
                                    <li>Заполните все обязательные поля:
                                        <ul>
                                            <li>Фамилия</li>
                                            <li>Имя</li>
                                            <li>Отчество</li>
                                            <li>Пароль</li>
                                            <li>Роль (Администратор/Кладовщик)</li>
                                        </ul>
                                    </li>
                                    <li>Нажмите "Сохранить пользователя"</li>
                                    <li>Новый пользователь появиться в списке, обновите страницу.</li>
                                    <li>Активируйте пользователя переместив ползунок. Нажмите кнопку "Сохранить статусы пользователя".</li>
                                </ol>
                                
                                <h6>Редактирование пользователя:</h6>
                                <ol>
                                    <li>Выделите строку с пользователем в таблице</li>
                                    <li>Нажмите кнопку "Редактировать"</li>
                                    <li>Внесите необходимые изменения</li>
                                    <li>Нажмите "Сохранить пользователя"</li>
                                </ol>
                                
                                <h6>Изменение статуса пользователя:</h6>
                                <ul>
                                    <li>Для активации/деактивации пользователя используйте переключатель в столбце "Активен"</li>
                                    <li>Для сохранения изменений статусов нажмите кнопку "Сохранить статусы пользователей"</li>
                                    <li>Нельзя отключить себя самого</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Внимание:</strong> только Администраторы могут добалять/редактировать пользователей
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 2: Локации -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                <i class="bi bi-geo-alt me-2"></i>2. Работа с локациями
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Добавление локации:</h6>
                                <ol>
                                    <li>Перейдите в раздел "Администрирование"</li>
                                    <li>Откройте вкладку "Локации"</li>
                                    <li>Нажмите кнопку "Добавить"</li>
                                    <li>Введите название локации</li>
                                    <li>Нажмите "Сохранить локацию"</li>
                                </ol>
                                
                                <h6>Редактирование локации:</h6>
                                <ol>
                                    <li>Выделите строку с локацией в таблице</li>
                                    <li>Нажмите кнопку "Редактировать"</li>
                                    <li>Измените карточку локации</li>
                                    <li>Нажмите "Сохранить локацию"</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Внимание:</strong> только Администраторы могут добалять/редактировать локации
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 3: Сервисные центры -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                <i class="bi bi-tools me-2"></i>3. Работа с сервисными центрами
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Добавление сервисного центра:</h6>
                                <ol>
                                    <li>Перейдите в раздел "Администрирование"</li>
                                    <li>Откройте вкладку "Сервисные центры"</li>
                                    <li>Нажмите кнопку "Добавить"</li>
                                    <li>Заполните информацию о сервисном центре:
                                        <ul>
                                            <li>Название</li>
                                            <li>Адрес</li>
                                            <li>Контактная информация</li>
                                        </ul>
                                    </li>
                                    <li>Нажмите "Сохранить сервисный центр"</li>
                                </ol>
                                
                                <h6>Редактирование сервисного центра:</h6>
                                <ol>
                                    <li>Выделите строку с сервисным центром в таблице</li>
                                    <li>Нажмите кнопку "Редактировать"</li>
                                    <li>Внесите изменения</li>
                                    <li>Нажмите "Сохранить сервисный центр"</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Внимание:</strong> только Администраторы могут добалять/редактировать сервисные центры
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 4: ТМЦ -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                <i class="bi bi-box-seam me-2"></i>4. Работа с ТМЦ (товарно-материальными ценностями)
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Создание нового ТМЦ:</h6>
                                <ol>
                                    <li>Нажмите "Создать ТМЦ" в боковом меню</li>
                                    <li>Заполните форму:
                                        <ul>
                                            <li>Наименование ТМЦ</li>
                                            <li>Серийный номер</li>
                                            <li>Бренд</li>
                                            <li>Категория</li>
                                            <li>Стоимость</li>
                                            <li>Дата поступления</li>
                                        </ul>
                                    </li>
                                    <li>Нажмите "Сохранить ТМЦ"</li>
                                </ol>
                                
                                <h6>Создание ТМЦ по аналогии:</h6>
                                <ol>
                                    <li>Выберите существующий ТМЦ как образец</li>
                                    <li>Нажмите "Создать ТМЦ по аналогии" в боковом меню</li>                                    
                                    <li>Внесите отличия (например, серийный номер)</li>
                                    <li>Нажмите "Сохранить ТМЦ"</li>
                                </ol>
                                
                                <h6>Редактирование ТМЦ:</h6>
                                <ol>
                                    <li>Выделите ТМЦ в основной таблице</li>
                                    <li>Нажмите "Редактировать ТМЦ" в боковом меню</li>
                                    <li>Внесите необходимые изменения</li>
                                    <li>Нажмите "Сохранить ТМЦ"</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Внимание:</strong> создавать ТМЦ могут только Администраторы
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 5: Передача ТМЦ -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                                <i class="bi bi-arrow-left-right me-2"></i>5. Передача ТМЦ
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Передача ТМЦ другому ответственному:</h6>
                                <ol>
                                    <li>Выделите один или несколько ТМЦ в таблице</li>
                                    <li>Нажмите "Передать ТМЦ" в боковом меню</li>
                                    <li>В открывшемся окне выберите:
                                        <ul>
                                            <li>Нового ответственного (если требуется)</li>
                                            <li>Новую локацию (если требуется)</li>
                                        </ul>
                                    </li>
                                    <li>Нажмите "Подтвердить передачу"</li>
                                </ol>
                                
                                <h6>Важные моменты:</h6>
                                <ul>
                                    <li>Можно передавать как один, так и несколько ТМЦ одновременно</li>
                                    <li>При передаче сохраняется история перемещений</li>
                                    <li>Администраторы могут передавать ТМЦ любому пользователю</li>
                                    <li>Кладовщики могут передавать только ТМЦ, за которые они ответственны</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Файлы:</strong> distribute_modal.php
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 6: Дополнительные операции -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                                <i class="bi bi-gear me-2"></i>6. Дополнительные операции
                            </button>
                        </h2>
                        <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Выдача ТМЦ в работу:</h6>
                                <ol>
                                    <li>Выделите ТМЦ в таблице</li>
                                    <li>Нажмите "В работу ТМЦ" в боковом меню</li>
                                    <li>Выберите бригаду/исполнителя</li>
                                    <li>Укажите срок выполнения работ</li>
                                    <li>Нажмите "Выдать в работу"</li>
                                </ol>
                                
                                <h6>Отправка в сервис:</h6>
                                <ol>
                                    <li>Выделите ТМЦ</li>
                                    <li>Нажмите "Отправить в сервис" в боковом меню</li>
                                    <li>Выберите сервисный центр</li>
                                    <li>Укажите причину отправки</li>
                                    <li>Нажмите "Отправить"</li>
                                </ol>
                                
                                <h6>Возврат из сервиса:</h6>
                                <ol>
                                    <li>Выделите ТМЦ со статусом "В сервисе"</li>
                                    <li>Нажмите "Вернуть из сервиса" в боковом меню</li>
                                    <li>Укажите результат ремонта</li>
                                    <li>Нажмите "Вернуть"</li>
                                </ol>
                                
                                <h6>Списание ТМЦ:</h6>
                                <ul>
                                    <li>Доступно только администраторам</li>
                                    <li>Перейдите в раздел "Списание/затраты на ремонт"</li>
                                    <li>Выберите ТМЦ для списания</li>
                                    <li>Укажите причину списания</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Раздел 7: Работа с таблицей -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven">
                                <i class="bi bi-table me-2"></i>7. Работа с таблицей ТМЦ
                            </button>
                        </h2>
                        <div id="collapseSeven" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Фильтрация данных:</h6>
                                <ul>
                                    <li>Нажмите на иконку фильтра в заголовке столбца</li>
                                    <li>Выберите значения для фильтрации</li>
                                    <li>Используйте поле поиска для быстрого нахождения значений</li>
                                    <li>Для сброса фильтров нажмите "Выбрать все"</li>
                                </ul>
                                
                                <h6>Выделение строк:</h6>
                                <ul>
                                    <li>Для выделения одной строки щелкните по ней</li>
                                    <li>Для выделения нескольких строк используйте Ctrl+Click</li>
                                    <li>Для выделения диапазона используйте Shift+Click</li>
                                </ul>
                                
                                <h6>Цветовые статусы:</h6>
                                <ul>
                                    <li><span class="badge bg-success">Зеленый</span> - ТМЦ доступен</li>
                                    <li><span class="badge bg-warning text-dark">Желтый</span> - ТМЦ в работе</li>
                                    <li><span class="badge bg-danger">Красный</span> - ТМЦ в сервисе</li>
                                    <li><span class="badge bg-secondary">Серый</span> - ТМЦ списан</li>
                                    <li><span class="badge bg-info">Голубой</span> - ТМЦ ожидает подтверждения</li>
                                </ul>
                                
                                <h6>Уведомления:</h6>
                                <p>В правом нижнем углу отображаются уведомления:</p>
                                <ul>
                                    <li>Принять ТМЦ - новые поступления</li>
                                    <li>Подтвердить ремонт - ТМЦ вернулись из сервиса</li>
                                    <li>Выдано в работу - ТМЦ находятся у исполнителей</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" onclick="printHelp()">
                    <i class="bi bi-printer"></i> Распечатать
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.accordion-button {
    font-weight: 600;
}
.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}
.accordion-body h6 {
    color: #0d6efd;
    margin-top: 1rem;
}
.help-icon {
    font-size: 1.2rem;
    margin-right: 0.5rem;
}
</style>

<script>
function printHelp() {
    const modalContent = document.querySelector('#helpModal .modal-content').cloneNode(true);
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Справка по системе учета ТМЦ</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { color: #0d6efd; }
                    h2 { color: #198754; margin-top: 20px; }
                    h3 { color: #6c757d; }
                    .section { margin-bottom: 30px; }
                    .alert { background: #e7f1ff; padding: 10px; border-left: 4px solid #0d6efd; margin: 10px 0; }
                    code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h1>Справка по системе учета ТМЦ</h1>
                <p><strong>Версия:</strong> 1.0 | <strong>Дата:</strong> ${new Date().toLocaleDateString()}</p>
                ${modalContent.querySelector('.modal-body').innerHTML}
                <div class="no-print">
                    <hr>
                    <p><small>Документ сгенерирован автоматически из системы учета ТМЦ</small></p>
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 500);
}
</script>