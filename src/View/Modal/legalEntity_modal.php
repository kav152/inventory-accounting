<?php
$legal = $legal ?? new LegalEntity();
$isActive = (bool) ($legal->isActive ?? true);
?>

<div class="modal fade" id="legalEntityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;">
                <h5 class="modal-title" id="legalEntityModalTitle">Юр. лицо</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="legalEntityForm">
                <div class="modal-body">
                    <input type="hidden" id="id" name="id" value="<?= (int) ($legal->IDLegalEntity ?? 0) ?>">
                    <div class="mb-3">
                        <label for="NameLegalEntity" class="form-label fw-bold">Наименование *</label>
                        <input type="text" class="form-control" id="NameLegalEntity" name="NameLegalEntity"
                            placeholder="ООО / АО / ИП …"
                            value="<?= htmlspecialchars($legal->NameLegalEntity ?? '') ?>" required>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="isActive" value="0">
                        <input class="form-check-input" type="checkbox" id="isActive" name="isActive" value="1"
                            <?= $isActive ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Активно (показывать в списках)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
