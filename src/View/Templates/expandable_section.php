<style>
    .expandable-section {
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .expandable-section.collapsed {
        max-height: 0;
        opacity: 0;
        margin: 0;
        padding: 0;
    }

    .expandable-section.expanded {
        max-height: 500px;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
    }
</style>


<?php
/**
 * Универсальный шаблон расширяемой секции для select элементов
 * 
 * @param string $sectionId - ID секции
 * @param string $selectId - ID связанного select элемента
 * @param array $fields - Массив полей для ввода
 * @param string $entityName - Название сущности (для заголовка)
 * @param string $createButtonText - Текст кнопки создания
 */
function renderExpandableSection($sectionId, $selectId, $fields, $entityName = '', $createButtonText = 'Создать')
{
    $fieldIds = [];
    foreach ($fields as $field) {
        $fieldIds[$field['name']] = $sectionId . '_' . $field['name'];
    }
    ?>
    <div class="expandable-section collapsed" id="<?= $sectionId ?>">
        <h6 class="border-bottom pb-2">Добавить <?= htmlspecialchars($entityName) ?></h6>
        <div class="row">
            <?php foreach ($fields as $field): ?>
                <div class="col-md-6">
                    <label for="<?= $fieldIds[$field['name']] ?>" class="form-label">
                        <?= htmlspecialchars($field['label']) ?>
                    </label>
                    <input type="<?= $field['type'] ?? 'text' ?>" class="form-control" id="<?= $fieldIds[$field['name']] ?>"
                        name="<?= $field['name'] ?>" data-section="<?= $sectionId ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-success btn-sm create-entity-btn" data-section-id="<?= $sectionId ?>"
                data-select-id="<?= $selectId ?>">
                <i class="bi bi-check-lg me-1"></i><?= htmlspecialchars($createButtonText) ?>
            </button>
            <button type="button" class="btn btn-secondary btn-sm cancel-entity-btn" data-section-id="<?= $sectionId ?>"
                data-select-id="<?= $selectId ?>">
                <i class="bi bi-x-lg me-1"></i>Отмена
            </button>
        </div>
    </div>
    <?php
}