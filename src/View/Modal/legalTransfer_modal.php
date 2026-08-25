<?php
/** @var iterable $locations */
/** @var iterable|null $users */
$users = $users ?? [];
?>

<div class="modal fade" id="legalTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content location-modal-content">
            <div class="modal-header location-modal-header">
                <div>
                    <h5 class="modal-title">Юр. лица — передача с объекта на объект</h5>
                    <p class="modal-subtitle mb-0">Выберите объекты, проверьте юр. лица и отметьте ТМЦ для передачи</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="legalTransferForm">
                    <div class="legal-transfer-grid mb-3">
                        <div class="legal-side">
                            <label class="form-label fw-bold">Объект откуда</label>
                            <select id="legalFromLocation" class="form-select" required>
                                <option value="">Выберите объект</option>
                                <?php foreach ($locations as $location):
                                    $legal = trim((string) ($location->FormsJointStockCompanies ?? ''));
                                ?>
                                    <option value="<?= (int) $location->IDLocation ?>"
                                        data-legal="<?= htmlspecialchars($legal) ?>"
                                        data-name="<?= htmlspecialchars($location->NameLocation ?? '') ?>">
                                        <?= htmlspecialchars($location->NameLocation ?? '') ?>
                                        <?= $legal !== '' ? ' — ' . htmlspecialchars($legal) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="legal-preview mt-2" id="legalFromPreview">
                                <span class="legal-preview-label">Юр. лицо</span>
                                <strong id="legalFromText" class="is-empty">—</strong>
                            </div>
                        </div>

                        <div class="legal-side-arrow"><i class="bi bi-arrow-right"></i></div>

                        <div class="legal-side">
                            <label class="form-label fw-bold">Объект куда</label>
                            <select id="legalToLocation" class="form-select" required>
                                <option value="">Выберите объект</option>
                                <?php foreach ($locations as $location):
                                    $legal = trim((string) ($location->FormsJointStockCompanies ?? ''));
                                ?>
                                    <option value="<?= (int) $location->IDLocation ?>"
                                        data-legal="<?= htmlspecialchars($legal) ?>"
                                        data-name="<?= htmlspecialchars($location->NameLocation ?? '') ?>">
                                        <?= htmlspecialchars($location->NameLocation ?? '') ?>
                                        <?= $legal !== '' ? ' — ' . htmlspecialchars($legal) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="legal-preview mt-2 legal-preview-to" id="legalToPreview">
                                <span class="legal-preview-label">Юр. лицо</span>
                                <strong id="legalToText" class="is-empty">—</strong>
                            </div>
                        </div>
                    </div>

                    <div class="legal-cross-banner mb-3" id="legalCrossBanner" hidden>
                        Передача между разными юр. лицами
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ответственный на объекте назначения</label>
                        <select id="legalTransferUser" name="user" class="form-select" required>
                            <option value="">Выберите ответственного</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= (int) $user->IDUser ?>"><?= htmlspecialchars($user->FIO ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">ТМЦ на объекте <span id="legalItemsSourceName" class="text-muted">—</span></h6>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="toolbar-count" id="legalItemsCount">0</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="legalSelectAllBtn">Выбрать все</button>
                        </div>
                    </div>

                    <div class="table-responsive legal-items-wrap">
                        <table class="table locations-table align-middle mb-0" id="legalItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:42px"><input type="checkbox" id="legalCheckAll" title="Выбрать все"></th>
                                    <th>ИД</th>
                                    <th>Наименование</th>
                                    <th>Сер. номер</th>
                                    <th>Бренд</th>
                                    <th>Статус</th>
                                    <th>Юр. лицо</th>
                                </tr>
                            </thead>
                            <tbody id="legalItemsBody">
                                <tr class="legal-empty-row">
                                    <td colspan="7" class="text-center text-muted py-4">Сначала выберите объект «откуда»</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer location-modal-footer px-3 pb-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary px-4" id="legalTransferSubmitBtn">
                    <i class="bi bi-arrow-left-right me-1"></i> Передать выбранные
                </button>
            </div>
        </div>
    </div>
</div>
