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

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDLocation = (int)$data['IDLocation'];
            $this->NameLocation = $data['NameLocation'];
            $this->IDCity = $data['IDCity'];
            $this->Address = $data['Address'];
            $this->isMainWarehouse = $data['isMainWarehouse'];
            $this->FormsJointStockCompanies = $data['FormsJointStockCompanies'] ?? '';
            $this->IsRepair = $data['IsRepair'];
        }
    }

    public function getId(): ?int {
        return $this->IDLocation ?? 0;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getPersistableProperties(): array
    {
        return [
            'NameLocation',
            'IDCity',
            'Address',
            'isMainWarehouse',
            'FormsJointStockCompanies',
            'IsRepair'
        ];
    }
}