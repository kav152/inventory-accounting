<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class InventoryItem extends BaseEntity
{
    public int $ID_TMC;
    public string $NameTMC;
    public int $Status;
    public $SerialNumber;
    public ?int $IDTypesTMC;
    public ?int $IDBrandTMC;
    public ?int $IDModel;
    public ?int $IDLocation;
    public ?ModelTMC $ModelTMC = null;
    public ?BrandTMC $BrandTMC = null;
    public ?Location $Location = null;
    public ?User $User = null;
    public ?RegistrationInventoryItem $RegistrationInventoryItem = null;

    public function __construct(array $data = [])
    {
        if (!empty($data))
        {
            $this->ID_TMC = isset($data['ID_TMC']) ? (int)$data['ID_TMC'] : 0;
            $this->NameTMC = (string)($data['NameTMC'] ?? '');
            $this->Status = (int)($data['Status'] ?? 0);
            $this->SerialNumber = $data['SerialNumber'] ?? null;
            $this->IDTypesTMC = (int)($data['IDTypesTMC'] ?? 0);
            $this->IDBrandTMC = (int)($data['IDBrandTMC'] ?? 0);
            $this->IDModel = (int)($data['IDModel'] ?? 0);
            $this->IDLocation = (int)($data['IDLocation'] ?? 0);            
        }        
    }

    public function mountEmptyDocument()
    {
        $this->IDTypesTMC = 0;
        $this->IDBrandTMC = 0;
        $this->IDModel = 0;
        $this->IDLocation = 0;        
        $this->Status = -1;
    }

    public function getId(): int {
        return $this->ID_TMC ?? 0;
    }

    public function setId(int $id): void {
        $this->ID_TMC = $id;
    }
     public function getIdFieldName(): string
    {
        return 'ID_TMC';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
        return [];
    }

    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'NameTMC',
            'Status',
            'SerialNumber',
            'IDTypesTMC',
            'IDBrandTMC',
            'IDModel',
            'IDLocation'
        ];
    }
}