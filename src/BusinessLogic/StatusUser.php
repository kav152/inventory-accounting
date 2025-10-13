<?php
class StatusUser
{
    public static $descriptions = [
        0 => 'Администратор',
        1 => 'Кладовщик',
        2 => 'Менеджер',
        3 => 'Бригадир',
        4 => 'Удален',
        5 => 'Списано',
    ];

    /**
     * Получить описание по значению enum descriptions
     */
    public static function getDescription($value): ?string {
        return self::$descriptions[$value] ?? null;
    }
}
