<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class City extends BaseEntity
{
    public int $IDCity;
    public $NameCity;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDCity = (int)$data['IDCity'];
            $this->NameCity = $data['NameCity'];
        }
    }

    public function getId():int
    {
        return $this->IDCity;
    }
    public function setId(int $id):void
    {
        $this->IDCity = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDCity';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
        return []; // НАСТРОИТЬ
    }
    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'NameCity',
        ];
    }
}