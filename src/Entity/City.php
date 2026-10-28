<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class City extends BaseEntity
{
    public int $IDCity = 0;
    public $NameCity;
    public $Address;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDCity = (int) ($data['IDCity'] ?? $data['idcity'] ?? 0);
            $this->NameCity = $data['NameCity'] ?? $data['namecity'] ?? null;
            $this->Address = $data['Address'] ?? $data['address'] ?? null;
        }
    }

    public function getId(): int
    {
        return $this->IDCity;
    }
    public function setId(int $id):void
    {
        $this->IDCity = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDCity';
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
            'NameCity',
            'Address',
        ];
    }
}
