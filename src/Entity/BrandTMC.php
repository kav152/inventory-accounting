<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class BrandTMC extends BaseEntity
{
    public int $IDBrandTMC;
    public $NameBrand;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDBrandTMC = (int)($data['IDBrandTMC'] ?? 00);
            $this->NameBrand = $data['NameBrand'];
        }        
    }

    public function getId():int
    {
        return $this->IDBrandTMC ?? 0;
    }
    public function setId(int $id):void
    {
        $this->IDBrandTMC = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDBrandTMC';
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
            'NameBrand',
        ];
    }
    
}