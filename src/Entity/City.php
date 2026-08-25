<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class City extends BaseEntity
{
    public int $IDCity;
    public $NameCity;
<<<<<<< HEAD
=======
    public $Address;
>>>>>>> source/feature/local-updates-2026-08

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDCity = (int)$data['IDCity'];
<<<<<<< HEAD
            $this->NameCity = $data['NameCity'];
=======
            $this->NameCity = $data['NameCity'] ?? null;
            $this->Address = $data['Address'] ?? null;
>>>>>>> source/feature/local-updates-2026-08
        }
    }

    public function getId():int
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
<<<<<<< HEAD

=======
>>>>>>> source/feature/local-updates-2026-08
    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
<<<<<<< HEAD
        return []; // НАСТРОИТЬ
    }
    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
=======
        return [];
    }

>>>>>>> source/feature/local-updates-2026-08
    public function getPersistableProperties(): array
    {
        return [
            'NameCity',
<<<<<<< HEAD
        ];
    }
}
=======
            'Address',
        ];
    }
}
>>>>>>> source/feature/local-updates-2026-08
