<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/CommentsHistory.log');
class CommentsHistory extends BaseEntity
{
    public int $IDComment;
    public string $ValueComment;
    public function __construct($param1 = null, string $valueComment = null)
    {
        // Конструктор для массива данных
        if (is_array($param1)) {
            $this->initializeFromArray($param1);
        }
        // Конструктор для InventoryItem и комментария
        if ($valueComment !== null) {
            $this->initializeFromItem($valueComment);
        }
    }

    private function initializeFromArray(array $data): void
    {
        $this->IDComment = (int) $data['IDComment'];
        $this->ValueComment = $data['ValueComment'];
    }

    private function initializeFromItem(string $valueComment): void
    {
        $this->ValueComment = $valueComment;
    }

    public function getId(): ?int
    {
        return $this->IDComment ?? 0;
    }
    public function setId(int $id): void {
        $this->IDComment = $id;
    }

    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'ValueComment'
        ];
    }

    
}