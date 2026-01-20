<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/HistoryOperations.log');
class HistoryOperations extends BaseEntity
{
    public int $IDHistoryItem;
    public $HistoryData;
    public int $IDComment;
    public int $IDUser;
    public int $ID_TMC;
    public int $IDLocation;
    public ?CommentsHistory $CommentsHistory = null;
    public ?User $User = null;

    public function __construct($param1 = null, $idUser = null, string $valueComment = null)
    {
        // Конструктор для массива данных
        if (is_array($param1)) {
            $this->initializeFromArray($param1);
        }
        // Конструктор для InventoryItem и комментария
        if ($param1 instanceof InventoryItem && $valueComment !== null) {
            $this->initializeFromItem($param1, $idUser, $valueComment);
        }        
    }

    private function initializeFromArray(array $data): void
    {
        $this->IDHistoryItem = (int)($data['IDHistoryItem'] ?? 0);
        $this->HistoryData = $data['HistoryData'];
        $this->IDComment = $data['IDComment'];
        $this->IDUser = $data['IDUser'];
        $this->ID_TMC = $data['ID_TMC'];
        $this->IDLocation = $data['IDLocation'] ?? null;
    }

    private function initializeFromItem(InventoryItem $item, $idUser, string $valueComment): void
    {
        $this->HistoryData = 'GETDATA';
        $this->CommentsHistory = new CommentsHistory(null,$valueComment);
        $this->IDUser = $idUser;
        $this->ID_TMC = $item->ID_TMC;
        $this->IDLocation = $item->IDLocation;
    }



    public function getId():int
    {
        return $this->IDHistoryItem ?? 0;
    }
    public function setId(int $id):void
    {
        $this->IDHistoryItem = $id;
    }

    public function getIdFieldName(): string
    {
        return 'IDHistoryItem';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getReadOnlyFields(): array
    {
        return []; // НАСТРОИТЬ
    }


    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [            
            'IDComment',
            'IDUser',
            'ID_TMC',
            'IDLocation',
            'HistoryData'
        ];
    }

    public function getAutoDateFields(): array
    {
        return [
            'HistoryData'
        ];
    }
}