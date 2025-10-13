<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';
class RepairItem extends BaseEntity
{
    public int $ID_Repair;
    public int $ID_TMC;
    public int $IDLocation;
    public float $RepairCost;
    public string $InvoiceNumber;
    public ?string $UPD;
    public string $RepairDescription;
    public string $DateToService;
    public ?string $DateReturnService;
    public bool $inBasket;
    public ?InventoryItem $InventoryItem;
    public ?Location $Location;
    public ?RegistrationInventoryItem $RegistrationInventoryItem;

    public function __construct(array $data = [])
    {
        $this->setIdFieldName('ID_Repair');
        if (!empty($data)) {
            $this->ID_Repair = isset($data['ID_Repair']) ? (int) $data['ID_Repair'] : 0;
            $this->ID_TMC = isset($data['ID_TMC']) ? (int) $data['ID_TMC'] : 0;
            $this->IDLocation = isset($data['IDLocation']) ? (int) $data['IDLocation'] : 0;
            $this->RepairCost = floatval($data['RepairCost'] ?? 0);
            $this->InvoiceNumber = $data['InvoiceNumber'] ?? '';
            $this->UPD = $data['UPD'] ?? '';
            $this->RepairDescription = $data['RepairDescription'] ?? '';
            $this->DateToService = $data['DateToService'] ?? '';
            $this->DateReturnService = $data['DateReturnService'] ?? '';
            $this->inBasket = isset($data['inBasket']) ? ($data['inBasket'] != 0) : false;

        }
    }

    public function getId(): ?int
    {
        return $this->ID_Repair ?? 0;
    }

    public function setId(int $id): void
    {
        $this->ID_Repair = $id;
    }

    public function getPersistableProperties(): array
    {
        return [
            'ID_TMC',
            'IDLocation',
            'InvoiceNumber',
            'RepairCost',
            'UPD',
            'RepairDescription',
            'DateToService',
            'DateReturnService',
            'inBasket'
        ];
    }

    public function getReadOnlyFields(): array
    {
        return ['DateToService']; // Поле не будет обновляться
    }

    public function getAutoDateFields(): array
    {
        return [
            'DateToService',
            /*'DateReturnService'*/
        ];
    }
}