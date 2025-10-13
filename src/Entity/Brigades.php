<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class Brigades extends BaseEntity
{
    public int $IDBrigade;
    public string $NameBrigade;
    public int $IDResponsibleIssuing;
    public string $NameBrigadir;

    public function __construct(array $data = [])
    {
        $this->setIdFieldName('IDBrigade');
        if(!empty($data))
        {
            $this->IDBrigade= isset($data['IDBrigade']) ? (int)$data['IDBrigade'] : 0;
            $this->NameBrigade = (string)($data['NameBrigade'] ?? '');
            $this->IDResponsibleIssuing= isset($data['IDResponsibleIssuing']) ? (int)$data['IDResponsibleIssuing'] : -10;
            $this->NameBrigadir = (string)($data['NameBrigadir'] ?? '');
        }
    }

    public function getId(): ?int {
        return $this->IDBrigade ?? 0;
    }

    public function setId(int $id): void {
        $this->IDBrigade = $id;
    }

    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'NameBrigade',
            'IDResponsibleIssuing',
            'NameBrigadir'
        ];
    }
}