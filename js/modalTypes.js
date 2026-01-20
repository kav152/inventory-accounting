import { initUserModalHandlers } from '../js/modals/userModal.js';
import { initCardTMCModalHandlers } from '../js/modals/cardItemModal.js';
import { initDistributeHandlers } from '../js/modals/distributeModal.js';
import { initWorkModalHandlers } from '../js/modals/workModal.js';
import { initSendToServiceModalHandlers } from '../js/modals/serviceModal.js';
import { initLocationModalHandlers } from '../js/modals/locationModal.js';
import { EntityModalConfig, ModalConfigRegistry } from '../js/index.js';
import { initRepairBasketModalHandlers } from '../js/modals/repairBasketModal.js';
import { initCardWriteOffHandlers } from '../js/writeOffFunctions.js';



/**
 * Глобальный реестр конфигураций модальных окон
 * @type {ModalConfigRegistry}
 */
const modalRegistry = new ModalConfigRegistry();


// Регистрация конфигурации для пользователей
modalRegistry.register({
    modalType: 'userModal',
    modalId: 'userModal',
    handler: initUserModalHandlers,
    entityType: 'user',
    tableContainerId: 'usersTableContainer',
    rowClass: 'row-user',
    entityName: 'пользователь',
    title: 'Управление пользователями',
    actions: ['create', 'update', 'delete']
});

modalRegistry.register({
    modalType: 'cardItemModal',
    modalId: 'cardItemModal',
    handler: initCardTMCModalHandlers,
    entityType: 'inventoryItem',
    tableContainerId: 'inventoryTable',
    rowClass: 'row-container',
    entityName: 'ТМЦ',
    title: 'Карточка ТМЦ',
    actions: ['create', 'update', 'delete']
});

modalRegistry.register({
    modalType: 'distributeModal',
    modalId: 'distributeModal',
    handler: initDistributeHandlers,
    entityType: 'inventoryItem_distributeModal',
    tableContainerId: 'inventoryTable',
    rowClass: 'row-container',
    entityName: 'ТМЦ',
    title: 'Распределить ТМЦ',
    actions: ['update']
});

modalRegistry.register({
    modalType: 'workModal',
    modalId: 'workModal',
    handler: initWorkModalHandlers,
    entityType: 'inventoryItem_workModal',
    tableContainerId: 'inventoryTable',
    rowClass: 'row-container',
    entityName: 'ТМЦ',
    title: 'Распределить ТМЦ',
    actions: ['update']
});

modalRegistry.register({
    modalType: 'serviceModal',
    modalId: 'serviceModal',
    handler: initSendToServiceModalHandlers,
    entityType: 'inventoryItem_serviceModal',
    tableContainerId: 'inventoryTable',
    rowClass: 'row-container',
    entityName: 'ТМЦ',
    title: 'Распределить ТМЦ',
    actions: ['update']
});

modalRegistry.register({
    modalType: 'locationModal',
    modalId: 'locationModal',
    handler: initLocationModalHandlers,
    entityType: 'location',
    tableContainerId: 'locationTableContainer',
    rowClass: 'row-location',
    entityName: 'Локация',
    title: 'Работа с локациями',
    actions: ['create', 'update', 'delete']
});

modalRegistry.register({
    modalType: 'locationServiceModal',
    modalId: 'locationModal',
    handler: initLocationModalHandlers,
    entityType: 'location',
    tableContainerId: 'serviceCentersTableContainer',
    rowClass: 'row-serviceCenters',
    entityName: 'Локация',
    title: 'Работа с локациями',
    actions: ['create', 'update', 'delete']
});
modalRegistry.register({
    modalType: 'repairBasketModal',
    modalId: 'repairBasketModal',
    handler: initRepairBasketModalHandlers,
    entityType: 'RepairItem',
    tableContainerId: 'RepairItemTableContainer',
    rowClass: 'row-repair',
    entityName: 'Списания',
    title: 'Управление списанием',
    actions: ['create', 'update', 'delete']
});

modalRegistry.register({
    modalType: 'edit_write_off',
    modalId: 'edit_write_off',
    handler: initCardWriteOffHandlers, // создайте этот обработчик
    entityType: 'RepairItem',
    tableContainerId: 'writeOffTable',
    rowClass: 'main-row',
    entityName: 'Ремонты',
    title: 'Редактирование ремонтов',
    actions: ['update']
});


/**
 * Получить ID модального окна по его типу (для обратной совместимости)
 * @param {string} modalType - тип модального окна
 * @returns {string|null} ID модального окна или null если не найден
 * @deprecated Используйте modalRegistry.getByModalType(modalType)?.modalId
 */
export function getModalIdByType(modalType) {
    const config = modalRegistry.getByModalType(modalType);
    return config ? config.modalId : null;
}

/**
 * Инициализировать обработчики для модального окна (для обратной совместимости)
 * @param {string} modalType - тип модального окна
 * @param {HTMLElement} modalElement - DOM элемент модального окна
 * @deprecated Используйте modalRegistry.getByModalType(modalType)?.handler(modalElement)
 */
export function initModalHandlers(modalType, modalElement) {
    const config = modalRegistry.getByModalType(modalType);
    if (config && typeof config.handler === 'function') {
        config.handler(modalElement);
    } else {
        console.warn(`Обработчик для модального окна '${modalType}' не найден`);
    }
}

// Экспорт реестра и классов
export { modalRegistry, EntityModalConfig, ModalConfigRegistry };
