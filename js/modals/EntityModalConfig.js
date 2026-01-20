/**
 * Класс конфигурации модального окна сущности
 * Объединяет настройки модального окна и параметры сущности
 */
class EntityModalConfig {
    /**
     * Создает экземпляр конфигурации модального окна
     * @param {Object} config - объект конфигурации
     * @param {string} config.modalType - тип модального окна (ключ)
     * @param {string} config.modalId - ID DOM элемента модального окна
     * @param {Function} config.handler - функция инициализации обработчиков
     * @param {string} config.entityType - тип сущности ('user', 'customer')
     * @param {string} config.tableContainerId - ID контейнера таблицы
     * @param {string} config.rowClass - CSS класс строк таблицы
     * @param {string} config.entityName - отображаемое имя сущности
     * @param {string} [config.title] - заголовок модального окна
     * @param {Array<string>} [config.actions] - поддерживаемые действия
     */
    constructor(config) {
        /**
         * Тип модального окна (используется как ключ)
         * @type {string}
         */
        this.modalType = config.modalType;

        /**
         * ID DOM элемента модального окна
         * @type {string}
         */
        this.modalId = config.modalId;

        /**
         * Функция инициализации обработчиков модального окна
         * @type {Function}
         */
        this.handler = config.handler;

        /**
         * Тип сущности для бизнес-логики
         * @type {string}
         */
        this.entityType = config.entityType;

        /**
         * ID контейнера таблицы сущностей
         * @type {string}
         */
        this.tableContainerId = config.tableContainerId;

        /**
         * CSS класс для строк таблицы этой сущности
         * @type {string}
         */
        this.rowClass = config.rowClass;

        /**
         * Человеко-читаемое имя сущности
         * @type {string}
         */
        this.entityName = config.entityName;

        /**
         * Заголовок модального окна
         * @type {string}
         */
        this.title = config.title || `Управление ${config.entityName}`;

        /**
         * Список поддерживаемых действий
         * @type {Array<string>}
         */
        this.actions = config.actions || ['create', 'update', 'delete'];

        /**
         * Внутренний ID для отслеживания (приватное свойство)
         * @type {number}
         * @private
         */
        this._internalId = Date.now();
    }

    /**
     * Проверяет, поддерживается ли указанное действие для этой конфигурации
     * @param {string} action - действие для проверки ('create', 'update', 'delete')
     * @returns {boolean} true если действие поддерживается
     * @example
     * const config = modalRegistry.getByEntityType('user');
     * if (config.supportsAction('delete')) {
     *   // выполнить удаление
     * }
     */
    supportsAction(action) {
        return this.actions.includes(action.toLowerCase());
    }

    /**
     * Возвращает объект с настройками для отображения
     * @returns {Object} объект с display-конфигурацией
     * @property {string} modalId - ID модального окна
     * @property {string} entityName - имя сущности
     * @property {string} title - заголовок
     * @property {Array<string>} supportedActions - поддерживаемые действия
     */
    getDisplayConfig() {
        return {
            modalId: this.modalId,
            entityName: this.entityName,
            title: this.title,
            supportedActions: this.actions
        };
    }

    /**
     * Проверяет валидность конфигурации
     * @returns {Object} результат валидации
     * @property {boolean} isValid - true если конфигурация валидна
     * @property {Array<string>} errors - список ошибок
     */
    validate() {
        const errors = [];
        
        // Проверка обязательных полей
        if (!this.modalType || typeof this.modalType !== 'string') {
            errors.push('modalType обязателен и должен быть строкой');
        }
        
        if (!this.modalId || typeof this.modalId !== 'string') {
            errors.push('modalId обязателен и должен быть строкой');
        }
        
        if (!this.handler || typeof this.handler !== 'function') {
            errors.push('handler обязателен и должен быть функцией');
        }
        
        if (!this.entityType || typeof this.entityType !== 'string') {
            errors.push('entityType обязателен и должен быть строкой');
        }
        
        // Проверка корректности actions
        if (!Array.isArray(this.actions)) {
            errors.push('actions должен быть массивом');
        } else {
            const validActions = ['create', 'update', 'delete'];
            const invalidActions = this.actions.filter(action => !validActions.includes(action));
            if (invalidActions.length > 0) {
                errors.push(`Недопустимые действия: ${invalidActions.join(', ')}. Допустимы: ${validActions.join(', ')}`);
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors
        };
    }

    /**
     * Обновляет конфигурацию новыми значениями
     * @param {Object} newConfig - новые значения конфигурации
     * @returns {EntityModalConfig} this для цепочки вызовов
     * @throws {Error} если переданы недопустимые свойства
     */
    updateConfig(newConfig) {
        const allowedProperties = [
            'title', 'actions', 'tableContainerId', 'rowClass', 'entityName'
        ];
        
        Object.keys(newConfig).forEach(key => {
            if (allowedProperties.includes(key)) {
                this[key] = newConfig[key];
            } else {
                console.warn(`Свойство "${key}" не может быть обновлено после создания`);
            }
        });
        
        return this;
    }

    /**
     * Возвращает строковое представление конфигурации
     * @returns {string}
     */
    toString() {
        return `EntityModalConfig[${this.modalType}]: ${this.entityName} (${this.modalId})`;
    }

    /**
     * Геттер для получения внутреннего ID
     * @returns {number}
     */
    get internalId() {
        return this._internalId;
    }
}

export { EntityModalConfig };