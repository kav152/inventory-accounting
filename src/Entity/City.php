<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class City extends BaseEntity
{
    public int $IDCity;
    public $NameCity;
<<<<<<< HEAD
=======
    public $Address;
>>>>>>> feature/local-updates-2026-08

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDCity = (int)$data['IDCity'];
<<<<<<< HEAD
            $this->NameCity = $data['NameCity'];
=======
            $this->NameCity = $data['NameCity'] ?? null;
            $this->Address = $data['Address'] ?? null;
>>>>>>> feature/local-updates-2026-08
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
>>>>>>> feature/local-updates-2026-08
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

>>>>>>> feature/local-updates-2026-08
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
>>>>>>> feature/local-updates-2026-08
