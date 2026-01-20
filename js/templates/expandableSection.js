import { createEntity, cancelSection } from './entityCreationTemplate.js';

// Конфигурация для различных типов сущностей
const entityConfig = {
    typeTMCSection: {
        fields: [
            { name: 'NameTypesTMC', label: 'Тип ТМЦ', type: 'text', required: true }
        ],
        url: '/src/BusinessLogic/Actions/processCUDTypesTMC.php',
        entityName: 'Тип ТМЦ',
        patofIDSelectId: null // Для типа ТМЦ не нужно
    },
    brandTMCSection: {
        fields: [
            { name: 'NameBrand', label: 'Бренд', type: 'text', required: true }
        ],
        url: '/src/BusinessLogic/Actions/processCUDBrandTMC.php', 
        entityName: 'Бренд',
        patofIDSelectId: 'typeTMCSelect' // Для бренда patofID берем из typeTMCSelect        
    },
    modelTMCSection: {
        fields: [
            { name: 'NameModel', label: 'Модель', type: 'text', required: true }            
        ],
        url: '/src/BusinessLogic/Actions/processCUDModelTMC.php',
        entityName: 'Модель',
        patofIDSelectId: 'brandSelect' // Для модели patofID берем из brandSelect        
    },
    citySection:{
        fields: [
            { name: 'NameCity', label: 'Город', type: 'text', required: true }            
        ],
        url: '/src/BusinessLogic/Actions/processCUDCity.php',
        entityName: 'Город',
        patofIDSelectId: null // не нужно
    }
};


/**
 * Единая функция для переключения секций
 * @param {*} sectionId 
 * @param {*} selectId 
 * @returns 
 */
function toggleSection(sectionId, selectId) {
    const section = document.getElementById(sectionId);
    const select = document.getElementById(selectId);
    
    if (!section || !select) {
        console.error('Не найдены секция или select:', sectionId, selectId);
        return;
    }

    // Находим иконку по data-атрибуту
    const toggleIcon = document.querySelector(`.toggle-section-icon[data-section-id="${sectionId}"]`);
    
    if (section.classList.contains('collapsed')) {
        section.classList.remove('collapsed');
        section.classList.add('expanded');
        if (toggleIcon) {
            toggleIcon.className = 'bi bi-dash toggle-section-icon';
        }
        select.disabled = true;
    } else {
        section.classList.remove('expanded');
        section.classList.add('collapsed');
        if (toggleIcon) {
            toggleIcon.className = 'bi bi-plus toggle-section-icon';
        }
        select.disabled = false;
    }
}

// Инициализация обработчиков
document.addEventListener('DOMContentLoaded', function() {
    // Делегирование событий для всех кнопок переключения
    document.addEventListener('click', function(e) {
        // Обработка кнопок переключения секций
        if (e.target.closest('.toggle-section-btn')) {
            const button = e.target.closest('.toggle-section-btn');
            const sectionId = button.getAttribute('data-section-id');
            const selectId = button.getAttribute('data-select-id');
            toggleSection(sectionId, selectId);
        }

        // Обработка кнопок отмены
        if (e.target.closest('.cancel-entity-btn')) {
            const button = e.target.closest('.cancel-entity-btn');
            const sectionId = button.getAttribute('data-section-id');
            const selectId = button.getAttribute('data-select-id');
            cancelSection(sectionId, selectId);
        }

        // Обработка кнопок создания
        if (e.target.closest('.create-entity-btn')) {
            const button = e.target.closest('.create-entity-btn');
            const sectionId = button.getAttribute('data-section-id');
            const selectId = button.getAttribute('data-select-id');
            
            const config = entityConfig[sectionId];
            if (config) {
                createEntity(sectionId, selectId, config.fields, config.url, config.entityName, config.patofIDSelectId);
            } else {
                console.error('Конфигурация не найдена для секции:', sectionId);
                showNotification(TypeMessage.error, 'Конфигурация для создания сущности не найдена');
            }
        }
    });
});