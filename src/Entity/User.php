<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';

// Модели - нужно перенсти их в другой класс
class User extends BaseEntity
{
    public int $IDUser = 0;
    public $Surname = '';
    public $Name = '';
    public $Patronymic = '';
    public $Password = '';
    public int $Status = 1;
    public bool $isActive = true;
    public string $FIO = '';

    // Конструктор для гидратации данных из массива
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->IDUser = (int) ($data['IDUser'] ?? $data['idUser'] ?? 0);
            $this->Surname = $data['Surname'] ?? '';
            $this->Name = $data['Name'] ?? '';
            $this->Patronymic = $data['Patronymic'] ?? '';
            $this->Password = $data['Password'] ?? '';
            $this->Status = (int) ($data['Status'] ?? 0);
            $this->isActive = (bool) (is_string($data['isActive'] ?? null)
                ? (int) $data['isActive']
                : ($data['isActive'] ?? false));

            $nameInitial = mb_substr((string) $this->Name, 0, 1);
            $patronymicInitial = mb_substr((string) $this->Patronymic, 0, 1);
            $this->FIO = trim("{$this->Surname} {$nameInitial}.{$patronymicInitial}.");
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
        $this->isActive = true;
        $this->Surname = '';
        $this->Name = '';
        $this->Patronymic = '';
        $this->Password = '';
        $this->FIO = '';
    }
}
