<?php
require_once __DIR__ . '/IProperty.php';
require_once __DIR__ . '/../Repositories/BaseEntity.php';
require_once __DIR__ . '/../Logging/Logger.php';

class LinkTypeToBrand extends BaseEntity implements IProperty
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

    public function getName(): string
    {
        return $this->BrandTMC->NameBrand;
    }
    public function getId(): int
    {
        return $this->IDBrandTMC ?? 0;
    }

    public function getPersistableProperties(): array
    {
        return [
            'IDTypesTMC',
            'IDBrandTMC'
        ];
    }

    public function setId(int $id): void
    {  
              
    }
}