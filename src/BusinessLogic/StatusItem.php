<?php
class StatusItem {
    const CreateItems = -1;
    const NotDistributed = 0;
    /**
     * Выдано
     * @var int
     */
    const Released = 1;
    /**
     * В ремонте
     * @var int
     */
    const Repair = 2;
    /**
     * Списано
     * @var int
     */
    const WrittenOff = 3;
    /**
     * Подтвердить ТМЦ
     * @var int
     */
    const ConfirmItem = 4;
    /**
     * В работе
     * @var int
     */
    const AtWorkTMC = 5;
    /**
     * Вернуть с работы
     * @var int
     */
    const OutputTMC = 6;
    /**
     * Подтвердить ремонт
     * @var int
     */
    const ConfirmRepairTMC = 21;

    public static $statusClasses = [
        -1 => 'status-CreateItems',
        0 => 'status-NotDistributed',
        1 => 'status-Released',
        2 => 'status-Repair',
        3 => 'status-WrittenOff',
        4 => 'status-ConfirmItem',
        5 => 'status-AtWorkTMC',
        6 => 'status-OutputTMC',
        21 => 'status-ConfirmRepairTMC',
    ];

    private static $descriptions = [
        self::CreateItems => 'Создание объекта',
        self::NotDistributed => 'Не распределено',
        self::Released => 'Выдано на объект',
        self::Repair => 'В ремонте',
        self::WrittenOff => 'Списано',
        self::ConfirmItem => 'Подтвердить ТМЦ',
        self::AtWorkTMC => 'В работе',
        self::OutputTMC => 'Вернуть с работы',
        self::ConfirmRepairTMC => 'Подтвердить ремонт'
    ];

    /**
     * Получить описание по значению enum descriptions
     */
    public static function getDescription($value): ?string {
        return self::$descriptions[$value] ?? null;
    }

    /**
     * Получить описание класса по значению enum
     */
    public static function getStatusClasses($value): ?string {
        return self::$statusClasses[$value] ?? null;
    }

    /**
     * Получить значение enum по описанию (аналог GetEnumDescription из C#)
     */
    public static function getByDescription(string $description): ?int {
        $flipped = array_flip(self::$descriptions);
        return $flipped[$description] ?? null;
    }

    /**
     * Проверить существование значения
     */
    public static function isValid($value): bool {
        return array_key_exists($value, self::$descriptions);
    }
}
