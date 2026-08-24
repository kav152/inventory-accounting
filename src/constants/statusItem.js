export class StatusItem {
    static CreateItems = -1;
    static NotDistributed = 0;
    static Released = 1;
    static Repair = 2;
    static WrittenOff = 3;
    static ConfirmItem = 4;
    static AtWorkTMC = 5;
    static OutputTMC = 6;
    static ConfirmRepairTMC = 21;

    static statusClasses = {
        [-1]: 'status-CreateItems',
        [0]: 'status-NotDistributed',
        [1]: 'status-Released',
        [2]: 'status-Repair',
        [3]: 'status-WrittenOff',
        [4]: 'status-ConfirmItem',
        [5]: 'status-AtWorkTMC',
        [6]: 'status-OutputTMC',
        [21]: 'status-ConfirmRepairTMC'
    };

    static descriptions = {
        [StatusItem.CreateItems]: 'Создание объекта',
        [StatusItem.NotDistributed]: 'Не распределено',
        [StatusItem.Released]: 'Выдано на объект',
        [StatusItem.Repair]: 'В ремонте',
        [StatusItem.WrittenOff]: 'Списано',
        [StatusItem.ConfirmItem]: 'Подтвердить ТМЦ',
        [StatusItem.AtWorkTMC]: 'В работе',
        [StatusItem.OutputTMC]: 'Вернуть с работы',
        [StatusItem.ConfirmRepairTMC]: 'Подтвердить ремонт'
    };

    static getDescription(value) {
        return this.descriptions[value] || null;
    }

    static getStatusClasses(value) {
        return this.statusClasses[value] || null;
    }

    static getByDescription(description) {
        const entries = Object.entries(this.descriptions);
        for (const [key, value] of entries) {
            if (value === description) return parseInt(key);
        }
        return null;
    }

    static isValid(value) {
        return value in this.descriptions;
    }
}

window.StatusItem = StatusItem;

