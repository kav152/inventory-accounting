import { executeEntityAction } from '../templates/entityActionTemplate.js';
import { Action } from '../../src/constants/actions.js';
import { executeActionForCUDSelect } from '../templates/cudRowsInSelect.js';
import { showNotification } from '../modals/setting.js';
import { TypeMessage } from '../../src/constants/typeMessage.js';

/**
 * Универсальная функция создания сущности для expandable sections
 * @param {string} sectionId - ID секции
 * @param {string} selectId - ID связанного select элемента
 * @param {Array} fields - Массив полей с настройками
 * @param {string} entityUrl - URL для создания сущности
 * @param {string} entityName - Название сущности (для сообщений)
 * @param {*} patofIDSelectId - ссылка на select родительской сущности
 */
export async function createEntity(sectionId, selectId, fields, entityUrl, entityName, patofIDSelectId = null) {
    // Собираем данные из полей ввода
    const formData = {};
    let hasErrors = false;

    fields.forEach(field => {
        const input = document.getElementById(`${sectionId}_${field.name}`);
        if (!input) {
            console.error(`Поле не найдено: ${sectionId}_${field.name}`);
            hasErrors = true;
            return;
        }

        // Валидация обязательных полей
        if (field.required !== false && !input.value.trim()) {
            showNotification(
                TypeMessage.error,
                `Заполните поле "${field.label}"`
            );
            input.focus();
            hasErrors = true;
            return;
        }

        // Преобразование типов данных
        let value = input.value.trim();
        if (field.type === 'number') {
            value = parseFloat(value) || 0;
        }

        formData[field.name] = value;
    });

    if (hasErrors) return;

    // Добавляем patofID если указан соответствующий select
    if (patofIDSelectId) {
        const patofSelect = document.getElementById(patofIDSelectId);
        if (patofSelect && patofSelect.value) {
            formData.patofID = patofSelect.value;
        } else {
            showNotification(
                TypeMessage.error,
                `Ошибка: не выбран родительский элемент`
            );
            return;
        }
    }

    try {
        const result = await executeEntityAction({
            action: Action.CREATE,
            formData: formData,
            url: entityUrl,
            successMessage: `Значение '${entityName}' успешно добавлено`,
        });

        // Определяем поля для отображения в select
        const displayFields = fields.map(field => field.name);
        const displaySeparator = fields.length > 1 ? ', ' : '';

        //console.log(displayFields);
        //console.log(result.resultEntity);

        // Обновляем select
        executeActionForCUDSelect(
            Action.CREATE,
            result.resultEntity,
            selectId,
            displayFields,
            displaySeparator,
            true
        );

        // Закрываем секцию и очищаем поля
        cancelSection(sectionId, selectId);

    } catch (error) {
        console.error(`Ошибка при создании ${entityName}:`, error);
        showNotification(
            TypeMessage.error,
            `Ошибка при создании ${entityName}: ${error.message}`
        );
    }
}

/**
 * Универсальная функция отмены создания сущности
 * @param {string} sectionId - ID секции
 * @param {string} selectId - ID связанного select элемента
 */
export function cancelSection(sectionId, selectId) {
    const section = document.getElementById(sectionId);
    const select = document.getElementById(selectId);

    // Находим иконку по data-атрибуту
    const toggleIcon = document.querySelector(`.toggle-section-icon[data-section-id="${sectionId}"]`);

    if (section) {
        section.classList.remove('expanded');
        section.classList.add('collapsed');
    }

    if (toggleIcon) {
        toggleIcon.className = 'bi bi-plus toggle-section-icon';
    }

    if (select) {
        select.disabled = false;
    }

    // Очищаем все поля ввода в секции
    const inputs = section.querySelectorAll('input');
    inputs.forEach(input => input.value = '');
}