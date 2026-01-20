<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class TypesTMC extends BaseEntity
{
    public int $IDTypesTMC;
    public $NameTypesTMC;
    public $NameImage;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDTypesTMC = (int)$data['IDTypesTMC'];
            $this->NameTypesTMC = $data['NameTypesTMC'];
            $this->NameImage = $data['NameImage'];
        }
    }

    public function getId():int
    {
        return $this->IDTypesTMC;
    }
    public function setId(int $id):void
    {
        $this->IDTypesTMC = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDTypesTMC';
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
            'NameTypesTMC',
        ];
    }
}