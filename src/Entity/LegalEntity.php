<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';

class LegalEntity extends BaseEntity
{
    public int $IDLegalEntity = 0;
    public string $NameLegalEntity = '';
    public bool $isActive = true;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDLegalEntity = (int) ($data['IDLegalEntity'] ?? 0);
            $this->NameLegalEntity = trim((string) ($data['NameLegalEntity'] ?? ''));
            $this->isActive = isset($data['isActive'])
                ? (bool) (is_string($data['isActive']) ? (int) $data['isActive'] : $data['isActive'])
                : true;
        }
    }

    public function getId(): int
    {
        return $this->IDLegalEntity ?? 0;
    }

    public function setId(int $id): void
    {
        $this->IDLegalEntity = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDLegalEntity';
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
            'NameLegalEntity',
            'isActive',
        ];
    }
}
