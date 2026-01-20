<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';
class LinkBrandToModel extends BaseEntity
{
    public int $IDModel;
    public int $IDBrandTMC;
    public BrandTMC $BrandTMC;
    public ModelTMC $ModelTMC;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDBrandTMC = (int)$data['IDBrandTMC'];
            $this->IDModel = $data['IDModel'];
        }
    }

    public function getId():int
    {
        return $this->IDModel;
    }
    public function setId(int $id):void
    {
        $this->IDModel = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDModel';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
        return []; // НАСТРОИТЬ
    }

    public function getPersistableProperties(): array
    {
        return [
            'IDBrandTMC',
            'IDModel'            
        ];
    }
}