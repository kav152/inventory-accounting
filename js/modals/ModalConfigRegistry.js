import { EntityModalConfig } from './EntityModalConfig.js';

/**
 * Реестр для управления конфигурациями модальных окон
 * Предоставляет централизованное хранилище для всех конфигураций
 */
class ModalConfigRegistry {
    /**
     * Создает экземпляр реестра конфигураций
     */
    constructor() {
        /**
         * Хранилище конфигураций по типу модального окна
         * @type {Map<string, EntityModalConfig>}
         * @private
         */
        this._configsByModalType = new Map();

        /**
         * Хранилище конфигураций по типу сущности
         * @type {Map<string, EntityModalConfig>}
         * @private
         */
        this._configsByEntityType = new Map();

        /**
         * Счетчик для статистики
         * @type {Object}
         * @private
         */
        this._stats = {
            registered: 0,
            errors: 0
        };
    }

    /**
     * Регистрирует новую конфигурацию в реестре
     * @param {Object} configData - данные конфигурации
     * @returns {boolean} true если регистрация прошла успешно
     * @example
     * modalRegistry.register({
     *   modalType: 'userModal',
     *   modalId: 'userModal',
     *   handler: initUserModalHandlers,
     *   entityType: 'user',
     *   tableContainerId: 'usersTableContainer',
     *   rowClass: 'row-user',
     *   entityName: 'пользователя'
     * });
     */
    register(configData) {
        try {
            const entityConfig = new EntityModalConfig(configData);
            const validation = entityConfig.validate();
            
            if (!validation.isValid) {
                console.error('Ошибка валидации конфигурации:', validation.errors);
                this._stats.errors++;
                return false;
            }

            // Проверка на дубликаты
            if (this._configsByModalType.has(entityConfig.modalType)) {
                console.warn(`Конфигурация с modalType '${entityConfig.modalType}' уже существует и будет перезаписана`);
            }

            /*if (this._configsByEntityType.has(entityConfig.entityType)) {
                console.warn(`Конфигурация с entityType '${entityConfig.entityType}' уже существует и будет перезаписана`);
            }*/

            // Регистрация
            this._configsByModalType.set(entityConfig.modalType, entityConfig);
            this._configsByEntityType.set(entityConfig.entityType, entityConfig);
            this._stats.registered++;

            console.log(`Зарегистрирована конфигурация: ${entityConfig.toString()}`);
            return true;

        } catch (error) {
            console.error('Ошибка при регистрации конфигурации:', error);
            this._stats.errors++;
            return false;
        }
    }

    /**
     * Получает конфигурацию по типу модального окна
     * @param {string} modalType - тип модального окна
     * @returns {EntityModalConfig|null} конфигурация или null если не найдена
     * @example
     * const config = modalRegistry.getByModalType('userModal');
     * if (config) {
     *   console.log(config.getDisplayConfig());
     * }
     */
    getByModalType(modalType) {
        return this._configsByModalType.get(modalType) || null;
    }

    /**
     * Получает конфигурацию по типу сущности
     * @param {string} entityType - тип сущности
     * @returns {EntityModalConfig|null} конфигурация или null если не найдена
     * @example
     * const config = modalRegistry.getByEntityType('user');
     * if (config) {
     *   console.log(config.entityName);
     * }
     */
    getByEntityType(entityType) {
        return this._configsByEntityType.get(entityType) || null;
    }

    /**
     * Получает все зарегистрированные конфигурации
     * @returns {Array<EntityModalConfig>} массив конфигураций
     */
    getAll() {
        return Array.from(this._configsByModalType.values());
    }

    /**
     * Получает конфигурации по списку типов модальных окон
     * @param {Array<string>} modalTypes - массив типов модальных окон
     * @returns {Array<EntityModalConfig>} массив найденных конфигураций
     */
    getByModalTypes(modalTypes) {
        return modalTypes
            .map(type => this.getByModalType(type))
            .filter(config => config !== null);
    }

    /**
     * Проверяет существование конфигурации по типу модального окна
     * @param {string} modalType - тип модального окна
     * @returns {boolean} true если конфигурация существует
     */
    hasModalType(modalType) {
        return this._configsByModalType.has(modalType);
    }

    /**
     * Проверяет существование конфигурации по типу сущности
     * @param {string} entityType - тип сущности
     * @returns {boolean} true если конфигурация существует
     */
    hasEntityType(entityType) {
        return this._configsByEntityType.has(entityType);
    }

    /**
     * Удаляет конфигурацию по типу модального окна
     * @param {string} modalType - тип модального окна
     * @returns {boolean} true если конфигурация была удалена
     */
    unregisterByModalType(modalType) {
        const config = this._configsByModalType.get(modalType);
        if (config) {
            this._configsByModalType.delete(modalType);
            this._configsByEntityType.delete(config.entityType);
            this._stats.registered--;
            return true;
        }
        return false;
    }

    /**
     * Удаляет конфигурацию по типу сущности
     * @param {string} entityType - тип сущности
     * @returns {boolean} true если конфигурация была удалена
     */
    unregisterByEntityType(entityType) {
        const config = this._configsByEntityType.get(entityType);
        if (config) {
            this._configsByEntityType.delete(entityType);
            this._configsByModalType.delete(config.modalType);
            this._stats.registered--;
            return true;
        }
        return false;
    }

    /**
     * Очищает реестр, удаляя все конфигурации
     */
    clear() {
        const count = this._configsByModalType.size;
        this._configsByModalType.clear();
        this._configsByEntityType.clear();
        this._stats.registered = 0;
        console.log(`Реестр очищен. Удалено конфигураций: ${count}`);
    }

    /**
     * Возвращает статистику использования реестра
     * @returns {Object} объект со статистикой
     * @property {number} total - всего зарегистрировано
     * @property {number} errors - количество ошибок
     * @property {number} byModalType - количество по modalType
     * @property {number} byEntityType - количество по entityType
     */
    getStats() {
        return {
            total: this._stats.registered,
            errors: this._stats.errors,
            byModalType: this._configsByModalType.size,
            byEntityType: this._configsByEntityType.size
        };
    }

    /**
     * Возвращает список всех зарегистрированных типов модальных окон
     * @returns {Array<string>} массив типов
     */
    getRegisteredModalTypes() {
        return Array.from(this._configsByModalType.keys());
    }

    /**
     * Возвращает список всех зарегистрированных типов сущностей
     * @returns {Array<string>} массив типов
     */
    getRegisteredEntityTypes() {
        return Array.from(this._configsByEntityType.keys());
    }

    /**
     * Поиск конфигураций по критериям
     * @param {Function} predicate - функция-предикат (config => boolean)
     * @returns {Array<EntityModalConfig>} массив подходящих конфигураций
     * @example
     * // Найти все конфигурации, поддерживающие удаление
     * const deletable = modalRegistry.find(config => config.supportsAction('delete'));
     */
    find(predicate) {
        return this.getAll().filter(predicate);
    }

    /**
     * Возвращает строковое представление реестра
     * @returns {string}
     */
    toString() {
        const stats = this.getStats();
        return `ModalConfigRegistry: ${stats.total} конфигураций, ${stats.byModalType} по modalType, ${stats.byEntityType} по entityType`;
    }
}

export { ModalConfigRegistry };