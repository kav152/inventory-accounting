<?php
require_once __DIR__ . '/IProperty.php';
require_once __DIR__.'/../Repositories/BaseEntity.php';

class BrandTMC extends BaseEntity implements IProperty
{
    public int $IDBrandTMC;
    public string $NameBrand;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDBrandTMC = (int)$data['IDBrandTMC'];
            $this->NameBrand = $data['NameBrand'];
        }

        $this->setIdFieldName('IDBrandTMC');
    }

    // Реализация абстрактного метода BaseEntity

    public function getName(): string { return $this->NameBrand; }
    public function getId(): int { return $this->IDBrandTMC ?? 0; }
    
    public function getPersistableProperties(): array {
        return [
            'NameBrand'
        ];
    }

    public function setId(int $id): void {
        $this->IDBrandTMC = $id;
    }
    
}