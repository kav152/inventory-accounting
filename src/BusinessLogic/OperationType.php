<?php
class OperationType
{
    /**
     * Тип операции - создание ТМЦ
     * @var string
     */
    const CREATE = 'create';
    /**
     * Тип операции - распредление ТМЦ
     * @var string
     */
    const DISTRIBUTE = 'distribute';
    /**
     * Тип операции - подтверждение принятия ТМЦ
     * @var string
     */
    const CONFIRM = 'confirm';
    /**
     * Тип операции - подтвердить ремонт ТМЦ
     * @var string
     */
    const SEND_REPAIR = 'send_repair';
    /**
     * Тип операции - передать в бригаду
     * @var string
     */
    const ASSIGN_TO_BRIGADE = 'assign_to_brigade';
    /**
     * Тип операции - списание ТМЦ
     * @var string
     */
    const WRITE_OFF = 'write_off';
    /**
     * Тип операции - возвращение с ремонта
     * @var string
     */
    const RETURN_FROM_REPAIR = 'return_from_repair';
    /**
     * Тип операции - Принято решение отправить в ремонт /// отправить ТМЦ в ремонт
     * @var string
     */
    const ACCEPT_FOR_REPAIR = 'accept_for_repair';
    /**
     * Тип операции - возврат на склад
     * @var string
     */
    const Return_TMC_toWork = 'returnTMCtoWork';
    
    /**
     * Получить статус ТМЦ по типу операции с ним
     * @param string $operation
     * @return int|null
     */
    public static function getStatusTransition(string $operation): ?int
    {
        $map = [
            self::CREATE => StatusItem::NotDistributed,
            self::DISTRIBUTE => StatusItem::ConfirmItem,
            self::CONFIRM => StatusItem::Released,
            self::ASSIGN_TO_BRIGADE => StatusItem::AtWorkTMC,
            self::SEND_REPAIR => StatusItem::Repair,
            self::WRITE_OFF => StatusItem::WrittenOff,
            self::RETURN_FROM_REPAIR => StatusItem::Released,
            self::ACCEPT_FOR_REPAIR => StatusItem::ConfirmRepairTMC,
            self::Return_TMC_toWork => StatusItem::Released
        ];
        
        return $map[$operation] ?? null;
    }

    public static function getDescription(string $operation): string
    {
        $descriptions = [
            self::CREATE => 'Создание ТМЦ',
            self::DISTRIBUTE => 'Распределение ТМЦ',
            self::CONFIRM => 'Подтверждение получения',
            self::ASSIGN_TO_BRIGADE => 'Назначение в бригаду',
            self::SEND_REPAIR => 'Подтверждение ремонта',
            self::WRITE_OFF => 'Списание ТМЦ',
            self::RETURN_FROM_REPAIR => 'Возврат из ремонта',
            self::ACCEPT_FOR_REPAIR => 'Принятие в ремонт',
        ];
        
        return $descriptions[$operation] ?? 'Неизвестная операция';
    }
}