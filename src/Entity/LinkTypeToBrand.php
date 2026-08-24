<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';
require_once __DIR__ . '/../Logging/Logger.php';

class LinkTypeToBrand extends BaseEntity
{
    public int $IDTypesTMC;
    public int $IDBrandTMC;
    public BrandTMC $BrandTMC;
    public TypesTMC $TypesTMC;
    private Logger $logger;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDTypesTMC = (int) $data['IDTypesTMC'];
            $this->IDBrandTMC = (int) $data['IDBrandTMC'];
        }
    }

    public function getId():int
    {
        return 0;
    }
    public function setId(int $id):void
    {
       // $this->id[YourEntity] = $id;
    }

    public function getIdFieldName(): string
    {
        return 'No_id_LinkTypeToBrand';
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
            'IDTypesTMC',
            'IDBrandTMC'
        ];
    }
}