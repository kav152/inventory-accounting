<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/Location.log');
require_once __DIR__.'/../Repositories/BaseEntity.php';
class Location extends BaseEntity
{
    public int $IDLocation;
    public $NameLocation;
    public $IDCity;
    public $Address;
    public $isMainWarehouse;
    public $FormsJointStockCompanies;
    public bool $IsRepair;
    public $Phone;
    public $Contacts;
    public $Email;
    public $Location2;
    public ?City $City = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDLocation = (int)($data['IDLocation'] ?? 0);
            $this->NameLocation = $data['NameLocation'] ?? null;
            $this->IDCity = $data['IDCity'] ?? null;
            $this->Address = $data['Address'] ?? null;
            $this->isMainWarehouse = $data['isMainWarehouse'] ?? null;
            $this->FormsJointStockCompanies = $data['FormsJointStockCompanies']
                ?? $data['formsjointstockcompanies']
                ?? $data['LocationLegalEntity']
                ?? '';
            $this->IsRepair = (bool)($data['IsRepair'] ?? false);
            $this->Phone = $data['Phone'] ?? null;
            $this->Contacts = $data['Contacts'] ?? null;
            $this->Email = $data['Email'] ?? null;
            $this->Location2 = $data['Location2'] ?? null;
        }
    }

    public function getId(): int {
        return $this->IDLocation ?? 0;
    }

    public function setId(int $id): void {
        $this->IDLocation = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDLocation';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
        return [];
    }

    public function getPersistableProperties(): array
    {
        return [
            'NameLocation',
            'IDCity',
            'Address',
            'isMainWarehouse',
            'FormsJointStockCompanies',
            'IsRepair',
            'Phone',
            'Contacts',
            'Email',
            'Location2',
        ];
    }
}
