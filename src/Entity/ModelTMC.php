<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class ModelTMC extends BaseEntity
{
    public int $IDModel;
    public string $NameModel;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDModel = (int)($data['IDModel'] ?? 0);
            $this->NameModel = $data['NameModel'];
        }
    }

    public function getId():int
    {
        return $this->IDModel;
    }
    public function setId(int $id):void
    {
        $this->IDModel = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDModel';
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
            'NameModel',
        ];
    }
}