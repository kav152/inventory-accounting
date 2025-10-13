<?php
require_once __DIR__ . '/IProperty.php';
require_once __DIR__ . '/../Repositories/BaseEntity.php';
class LinkBrandToModel extends BaseEntity implements IProperty
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

    public function getName(): string { return $this->ModelTMC->NameModel; }
    public function getId(): int { return $this->IDModel ?? 0; }

    public function getPersistableProperties(): array
    {
        return [
            'IDModel',
            'IDBrandTMC'
        ];
    }

    public function setId(int $id): void
    {  
              
    }
    
}