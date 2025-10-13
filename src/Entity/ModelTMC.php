<?php

require_once __DIR__ . '/IProperty.php';
require_once __DIR__.'/../Repositories/BaseEntity.php';

class ModelTMC extends BaseEntity implements IProperty
{
    public int $IDModel;
    public string $NameModel;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDModel = (int)$data['IDModel'];
            $this->NameModel = $data['NameModel'];
        }
    }

    public function getName(): string { return $this->NameModel; }
    public function getId(): int { return $this->IDModel ?? 0; }

    // Реализация абстрактного метода BaseEntity
    public function getPersistableProperties(): array {
        return [
            'NameModel'
        ];
    }

    public function setId(int $id): void {
        $this->IDModel = $id;
    }
}