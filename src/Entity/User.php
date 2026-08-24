<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';

// Модели - нужно перенсти их в другой класс
class User extends BaseEntity
{
    public int $IDUser;
    public $Surname;
    public $Name;
    public $Patronymic;
    public $Password;
    public int $Status;
    public bool $isActive;
    public string $FIO;

    // Конструктор для гидратации данных из массива
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDUser = (int)($data['IDUser'] ?? 0);
            $this->Surname = $data['Surname'] ?? '';
            $this->Name = $data['Name'] ?? '';
            $this->Patronymic = $data['Patronymic'] ?? '';
            $this->Password = $data['Password'] ?? '';
            $this->Status = (int)$data['Status'] ?? 0;
            $this->isActive = (bool)($data['isActive'] ?? false);

            $nameInitial = substr($this->Name, 0, 2);
            $patronymicInitial = substr($this->Patronymic, 0, 2);
            $this->FIO = "{$this->Surname} {$nameInitial}.{$patronymicInitial}.";
        }
    }
    public function getId(): int
    {
        return $this->IDUser ?? 0;
    }

    public function setId(int $id): void
    {
        $this->IDUser = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDUser';
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
        //return '123';
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
    public function mountEmptyDocument() 
    {
        $this->IDUser = 0;
        $this->Status = 1;
    }
}
