<?php
include_once __DIR__ . '/../Templates/expandable_section.php';
?>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="locationModalTitle">Добавить локацию</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="locationForm">
                    <input type="hidden" id="id" name="id" value="<?= htmlspecialchars($location->IDLocation ?? '') ?>">
                    <input type="hidden" id="IsRepair" name="IsRepair"
                        value="<?= htmlspecialchars($location->IsRepair ?? 0) ?>">
                    <input type="hidden" id="isMainWarehouse" name="isMainWarehouse"
                        value="<?= htmlspecialchars($location->isMainWarehouse ?? 0) ?>">

                    <?php if (($location->IsRepair ?? 0) == 1): ?>
                        <!-- Вариант для IsRepair = 1 (Ремонтная локация) -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="FormsJointStockCompanies" class="form-label">Форма общества</label>
                                    <input type="text" id="FormsJointStockCompanies" name="FormsJointStockCompanies"
                                        class="form-control" placeholder="Форма акционерного общества"
                                        value="<?= htmlspecialchars($location->FormsJointStockCompanies ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <label for="NameLocation" class="form-label">Наименование</label>
                                    <input type="text" id="NameLocation" name="NameLocation" class="form-control"
                                        placeholder="Наименование локации"
                                        value="<?= htmlspecialchars($location->NameLocation ?? '') ?>" required>
                                </div>
                            </div>                            
                        </div>

                        <div class="mb-3 d-none">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isMainWarehouse" name="isMainWarehouse"
                                    value="1" <?= ($location->isMainWarehouse ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isMainWarehouse">
                                    Основной склад
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Вариант для IsRepair = 0 (Склад) -->
                        <div class="mb-3">
                            <label for="NameLocation" class="form-label">Наименование локации *</label>
                            <input type="text" id="NameLocation" name="NameLocation" class="form-control"
                                placeholder="Наименование локации"
                                value="<?= htmlspecialchars($location->NameLocation ?? '') ?>" required>
                        </div>

                        <div class="mb-3 d-none">
                            <label for="FormsJointStockCompanies" class="form-label">Форма акционерного общества</label>
                            <input type="text" id="FormsJointStockCompanies" name="FormsJointStockCompanies"
                                class="form-control" placeholder="Форма акционерного общества"
                                value="<?= htmlspecialchars($location->FormsJointStockCompanies ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isMainWarehouseCheckbox" name="isMainWarehouseCheckbox"
                                    value="1" <?= ($location->isMainWarehouse ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isMainWarehouse">
                                    Основной склад
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="Address" class="form-label">Адрес *</label>
                        <input type="text" id="Address" name="Address" class="form-control" placeholder="Адрес"
                            value="<?= htmlspecialchars($location->Address ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="IDCity" class="form-label">Город *</label>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="citySelect" name="IDCity" class="form-select" required>
                                <option value="">Выберите город</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city->IDCity ?>" 
                                        <?= $city->IDCity == $location->IDCity ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city->NameCity) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary toggle-section-btn"
                                data-section-id="citySection" data-select-id="citySelect">
                                <i class="bi bi-plus toggle-section-icon" data-section-id="citySection"></i>
                            </button>
                        </div>

                        <?php renderExpandableSection(
                            'citySection',
                            'citySelect',
                            [['name' => 'NameCity', 'label' => 'Город', 'type' => 'text']],
                            'город',
                            'Добавить город'
                        ); ?>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить локацию</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locationForm = document.getElementById('locationForm');
        const isRepairInput = document.getElementById('IsRepair');

        // Обработка отправки формы
        locationForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // Конвертация чекбокса
            data.isMainWarehouse = data.isMainWarehouse === '1' ? 1 : 0;
            data.IsRepair = parseInt(data.IsRepair);

            // Отправка данных
            fetch('/path/to/your/location/endpoint', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Закрытие модального окна и обновление таблицы
                        const modal = bootstrap.Modal.getInstance(document.getElementById('locationModal'));
                        modal.hide();

                        // Обновление данных в таблице
                        if (window.locationTable) {
                            window.locationTable.ajax.reload();
                        }

                        // Показать уведомление об успехе
                        showNotification('Локация успешно сохранена', 'success');
                    } else {
                        showNotification(result.message || 'Ошибка сохранения', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Ошибка сети', 'error');
                });
        });

        // Функция для отображения уведомлений
        function showNotification(message, type) {
            // Реализация показа уведомлений (можно использовать toast из Bootstrap)
            alert(message); // Временная реализация
        }

        // Если нужно динамически менять отображение при изменении IsRepair
        // (например, при создании новой локации)
        if (window.setLocationModalType) {
            window.setLocationModalType = function (isRepair) {
                isRepairInput.value = isRepair;

                // Обновление заголовка
                const title = document.getElementById('locationModalTitle');
                title.textContent = isRepair == 1 ? 'Добавить ремонтную локацию' : 'Добавить склад';

                // Динамическое скрытие/показ полей через JavaScript
                // Эта часть может быть реализована в зависимости от требований
            };
        }
    });
</script>