<?php
include_once __DIR__ . '/../Templates/expandable_section.php';
?>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content location-modal-content">
            <div class="modal-header location-modal-header">
                <div>
                    <h5 class="modal-title" id="locationModalTitle">Добавить локацию</h5>
                    <p class="modal-subtitle mb-0">Юр. лицо, адрес, контакты и дополнительная локация</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="locationForm" class="location-form">
                    <input type="hidden" id="id" name="id" value="<?= htmlspecialchars($location->IDLocation ?? '') ?>">
                    <input type="hidden" id="IsRepair" name="IsRepair"
                        value="<?= htmlspecialchars($location->IsRepair ?? 0) ?>">
                    <input type="hidden" id="isMainWarehouse" name="isMainWarehouse"
                        value="<?= htmlspecialchars($location->isMainWarehouse ?? 0) ?>">

                    <?php if (($location->IsRepair ?? 0) == 1): ?>
                        <div class="form-section">
                            <div class="section-title">Сервисный центр</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="FormsJointStockCompanies" class="form-label">Юр. лицо</label>
                                    <input type="text" id="FormsJointStockCompanies" name="FormsJointStockCompanies"
                                        class="form-control" placeholder="ООО / АО / ИП …"
                                        value="<?= htmlspecialchars($location->FormsJointStockCompanies ?? '') ?>">
                                </div>
                                <div class="col-md-8">
                                    <label for="NameLocation" class="form-label">Наименование *</label>
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
                                <label class="form-check-label" for="isMainWarehouse">Основной склад</label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-section">
                            <div class="section-title">Основная информация</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="FormsJointStockCompanies" class="form-label">Юр. лицо</label>
                                    <input type="text" id="FormsJointStockCompanies" name="FormsJointStockCompanies"
                                        class="form-control" placeholder="ООО / АО / ИП …"
                                        value="<?= htmlspecialchars($location->FormsJointStockCompanies ?? '') ?>">
                                </div>
                                <div class="col-md-8">
                                    <label for="NameLocation" class="form-label">Наименование локации *</label>
                                    <input type="text" id="NameLocation" name="NameLocation" class="form-control"
                                        placeholder="Наименование локации"
                                        value="<?= htmlspecialchars($location->NameLocation ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isMainWarehouseCheckbox"
                                        name="isMainWarehouseCheckbox"
                                        value="1" <?= ($location->isMainWarehouse ?? 0) == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isMainWarehouseCheckbox">Основной склад</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-section">
                        <div class="section-title">Город и адреса</div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="IDCity" class="form-label">Город *</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select id="citySelect" name="IDCity" class="form-select" required>
                                        <option value="">Выберите город</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city->IDCity ?>"
                                                data-address="<?= htmlspecialchars($city->Address ?? '') ?>"
                                                <?= $city->IDCity == $location->IDCity ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($city->NameCity) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary toggle-section-btn"
                                        data-section-id="citySection" data-select-id="citySelect" title="Добавить город">
                                        <i class="bi bi-plus-lg toggle-section-icon" data-section-id="citySection"></i>
                                    </button>
                                </div>
                                <?php renderExpandableSection(
                                    'citySection',
                                    'citySelect',
                                    [
                                        ['name' => 'NameCity', 'label' => 'Город', 'type' => 'text'],
                                        ['name' => 'Address', 'label' => 'Адрес города', 'type' => 'text'],
                                    ],
                                    'город',
                                    'Добавить город'
                                ); ?>
                            </div>
                            <div class="col-md-6">
                                <label for="Address" class="form-label">Адрес локации *</label>
                                <input type="text" id="Address" name="Address" class="form-control"
                                    placeholder="Адрес локации"
                                    value="<?= htmlspecialchars($location->Address ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="Location2" class="form-label">Локация 2</label>
                                <input type="text" id="Location2" name="Location2" class="form-control"
                                    placeholder="Дополнительная локация / адрес 2"
                                    value="<?= htmlspecialchars($location->Location2 ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Адрес города</label>
                                <input type="text" id="CityAddressPreview" class="form-control" readonly
                                    placeholder="Подставится из выбранного города"
                                    value="<?= htmlspecialchars($location->City?->Address ?? '') ?>">
                                <div class="form-text">При смене города подставляется сохранённый адрес города</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Контакты</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="Phone" class="form-label">Телефоны</label>
                                <input type="text" id="Phone" name="Phone" class="form-control"
                                    placeholder="+7 ..."
                                    value="<?= htmlspecialchars($location->Phone ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="Contacts" class="form-label">Контакты</label>
                                <input type="text" id="Contacts" name="Contacts" class="form-control"
                                    placeholder="ФИО / должность"
                                    value="<?= htmlspecialchars($location->Contacts ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="Email" class="form-label">Почта</label>
                                <input type="email" id="Email" name="Email" class="form-control"
                                    placeholder="mail@example.com"
                                    value="<?= htmlspecialchars($location->Email ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer location-modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary px-4">Сохранить локацию</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
