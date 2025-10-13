class TypeMessage {
    static error = 0;
    static success = 1;
    static notification = 2;

    static statusClasses = {
        [TypeMessage.error] : 'error',
        [TypeMessage.success] : 'success',
        [TypeMessage.notification] : 'notification'
    }

    static titleMessage = {
        [TypeMessage.error] : 'Ошибка',
        [TypeMessage.success] : 'Успешно',
        [TypeMessage.notification] : 'Уведомление'
    }
    static iconMessage = {
        [TypeMessage.error] : '<i class="bi bi-exclamation-triangle-fill"></i>',
        [TypeMessage.success] : '<i class="bi bi-check-circle-fill"></i>',
        [TypeMessage.notification] : '<i class="bi bi-exclamation-triangle-fill"></i>'
    }

    /**
     * Получить название класса
     * @param {*} value 
     * @returns 
     */
    static getStatusClasses(value) {
        return this.statusClasses[value] || null;
    }

    /**
     * Получить название сообщения
     * @param {*} value 
     * @returns 
     */
    static getTitleMessage(value) {
        return this.titleMessage[value] || null;
    }

    /**
     * Получить название иконки
     * @param {*} value 
     * @returns 
     */
    static getIconMessage(value) {
        return this.iconMessage[value] || null;
    }


}