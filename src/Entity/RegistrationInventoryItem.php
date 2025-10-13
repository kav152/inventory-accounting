<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';
require_once __DIR__ . '/../BusinessLogic/Action.php';

class RegistrationInventoryItem extends BaseEntity
{
    public int $IDRegItem;
    public string $CreationDate;
    public int $CreatedUser;
    public int $CurrentUser;
    public string $ChangeDate;
    public User $User;


    public function __construct(array $data = [])
    {
        $this->setIdFieldName('IDRegItem');
        if (!empty($data)) {
            $this->IDRegItem = (int)$data['IDRegItem'] ?? 0;
            $this->CreationDate = $data['CreationDate'] ?? date('Y-m-d H:i:s');
            $this->CreatedUser = $data['CreatedUser'];
            $this->CurrentUser = $data['CurrentUser'];
            $this->ChangeDate = $data['ChangeDate'] ?? date('Y-m-d H:i:s');
        }
    }

    public function getName(): string
    {
        return "RegistrationInventoryItem";
    }
    public function getId(): int
    {
        return $this->IDRegItem;
    }
    public function setId(int $id): void {
        $this->IDRegItem = $id;
    }

    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'IDRegItem',
          /*  'CreationDate',*/
            'CreatedUser',
            'CurrentUser',
           /* 'ChangeDate'*/
        ];
    }

    public function getReadOnlyFields(): array
    {
        return ['CreationDate']; // Поле не будет обновляться
    }

    public function getAutoDateFields(): array
    {
        return [
          /*  'CreationDate',*/
           /* 'ChangeDate'*/
        ];
    }
}
