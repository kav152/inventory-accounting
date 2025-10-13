<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class InventoryItem extends BaseEntity
{
    public int $ID_TMC;
    public string $NameTMC;
    public int $Status;
    public $SerialNumber;
    public int $IDTypesTMC;
    public int $IDBrandTMC;
    public int $IDModel;
    public int $IDLocation;
    public ModelTMC $ModelTMC;
    public BrandTMC $BrandTMC;
    public ?Location $Location = null;
    public User $User;
    public string $ValueStatus;
    public ?int $CurrentUser;

    public function __construct(array $data = [])
    {
        $this->setIdFieldName('ID_TMC');
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
            $this->CurrentUser = isset($data['CurrentUser']) ? (int)$data['CurrentUser'] : null;                       
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

    public function getId(): ?int {
        return $this->ID_TMC ?? 0;
    }

    public function setId(int $id): void {
        $this->ID_TMC = $id;
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