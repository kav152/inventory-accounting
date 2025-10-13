<?php
class ActionProperty
{
    const TYPE_TMC = 'type_tmc';
    const BRAND = 'brand';
    const MODEL = 'model';

    public static function isValid($value)
    {
        return in_array($value, 
        [
            self::TYPE_TMC,
            self::BRAND,
            self::MODEL
        ]);
    }
}