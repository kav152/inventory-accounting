/**
 * Модуль конфигураций модальных окон
 * @module ModalConfig
 */

export { EntityModalConfig } from './modals/EntityModalConfig.js';
export { ModalConfigRegistry } from './modals/ModalConfigRegistry.js';

// Реэкспорт из modalTypes.js для удобства
export { 
    modalRegistry, 
    getModalIdByType, 
    initModalHandlers 
} from './modalTypes.js';