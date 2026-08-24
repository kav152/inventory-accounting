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
        // Не фильтруем: № счета, № УПД, сумма, действия
        excludeColumns: [7, 8, 9, 10],
        onFilterApplied: (filters, visibleCount) => {
            let total = 0;
            document.querySelectorAll('.main-row').forEach(row => {
                if (row.style.display !== 'none') {
                    total += parseFloat(row.getAttribute('data-total-cost') || 0);
                }
            });

            const formattedSum = new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(total);

            const summaryEl = document.getElementById('total-summary');
            if (summaryEl) {
                summaryEl.innerHTML = `
                    <div class="summary-icon"><i class="bi bi-cash-coin"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Общая сумма ремонта ТМЦ</span>
                        <strong class="summary-amount">${formattedSum} ₽</strong>
                    </div>`;
            }
            const heroSum = document.getElementById('hero-total-sum');
            if (heroSum) heroSum.textContent = `${formattedSum} ₽`;
        }
    }
};

export function initFilter(tableType, customConfig = {}) {
    const config = { ...FilterConfigs[tableType], ...customConfig };
    return new TableFilter(config);
}
