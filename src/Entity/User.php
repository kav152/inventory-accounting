<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';

// Модели - нужно перенсти их в другой класс
class User extends BaseEntity
{
    public int $IDUser;
    public string $Surname;
    public string $Name;
    public string $Patronymic;
    public string $Password;
    public int $Status;
    public bool $isActive;
    public string $FIO;

    // Конструктор для гидратации данных из массива
    public function __construct(array $data = [])
    {
        $this->setIdFieldName('IDUser');
        if (!empty($data)) {
            $this->IDUser = (int)$data['IDUser'];
            $this->Surname = $data['Surname'];
            $this->Name = $data['Name'];
            $this->Patronymic = $data['Patronymic'];
            $this->Password = $data['Password'];
            $this->Status = (int)$data['Status'];
            $this->isActive = (bool)$data['isActive'];
            $nameInitial = substr($this->Name,0,2);
            $patronymicInitial = substr($this->Patronymic,0,2);
            $this->FIO = "{$this->Surname} {$nameInitial}.{$patronymicInitial}.";

        }
    }
    public function getId(): int
    {
        return $this->IDUser ?? 0;
    }

    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [            
            'Surname',
            'Name',
            'Patronymic',
            'Password',
            'Status',
            'isActive'
        ];
    }

    public function setPassword(string $password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException("Пароль не может быть пустым");
        }

        $this->Password = $this->hashPassword($password);

    }
    
    /**
     * Проверяет соответствие пароля
     * @param string $password Пароль в открытом виде
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->Password);
    }

    /**
     * Хеширует пароль
     * @param string $password Пароль в открытом виде
     * @return string
     */
    private function hashPassword(string $password): string
    {
        return password_hash(
            $password, 
            PASSWORD_DEFAULT, 
            ['cost' => 12]
        );
    }

    /**
     * Проверяет, нужно ли обновить хеш пароля
     * @return bool
     */
    public function needsPasswordRehash(): bool
    {
        return password_needs_rehash($this->Password, PASSWORD_DEFAULT);
    }


}