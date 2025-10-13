<?php
class Action {
    const CREATE = 'create';
    const CREATE_ANALOG = 'create_analog';
    const EDIT = 'edit';
    const SEND_TMC = 'sendTMC';
    const WORK_TMC = 'workTMC';
    const RETURN_TMC = 'returnTMC';
    const SEND_SERVICE = 'sendService';
    const RETURN_SERVICE = 'returnService';

    // Проверка, что значение допустимо
    public static function isValid($value) {
        return in_array($value, [
            self::CREATE,
            self::CREATE_ANALOG,
            self::EDIT,
            self::SEND_TMC,
            self::WORK_TMC,
            self::RETURN_TMC,
            self::SEND_SERVICE,
            self::RETURN_SERVICE
        ]);
    }
}