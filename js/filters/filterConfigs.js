import { TableFilter } from './tableFilter.js';

/**
 * Конфигурации фильтров для разных типов таблиц
 */
export const FilterConfigs = {
    HOME: {
        tableId: 'inventoryTable',
        containerId: 'cont1',
        rowSelector: 'tbody tr.row-container',
        excludeColumns: [],
        onRowCountChanged: (visibleCount, totalCount) => {
            const counter = document.getElementById('row-counter');
            if (counter) {
                counter.textContent = `Кол-во строк: ${visibleCount} из ${totalCount}`;
            }
        }
    },

    WRITE_OFF: {
        tableId: 'writeOffTable',
        containerId: 'idTableResponsive',
        rowSelector: 'tbody tr.main-row',
        excludeColumns: [8, 9],
        onFilterApplied: (filters, visibleCount) => {
            // Общая логика для списаний
            let total = 0;
            document.querySelectorAll('.main-row').forEach(row => {
                if (row.style.display !== 'none') {
                    total += parseFloat(row.getAttribute('data-total-cost') || 0);
                }
            });

            const summaryEl = document.getElementById('total-summary');
            if (summaryEl) {
                const formattedSum = new Intl.NumberFormat('ru-RU', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(total);
                summaryEl.textContent = `Общая сумма ремонта ТМЦ: ${formattedSum} руб.`;
            }
        }
    }
};

// Вспомогательная функция для инициализации
export function initFilter(tableType, customConfig = {}) {
    const config = { ...FilterConfigs[tableType], ...customConfig };
    return new TableFilter(config);
}