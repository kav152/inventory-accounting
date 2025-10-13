<?php
require_once __DIR__.'/../Repositories/BaseEntity.php';

class LinkBrigadesToItem extends BaseEntity
{
    public int $IDBrigade;
    public int $ID_TMC;
    public int $ID_LBT;
    public InventoryItem $InventoryItem;
    public Brigades $Brigades;


    public function __construct(array $data = null, int $ID_TMC =null, int $IDBrigade = null)
    {
        $this->setIdFieldName('ID_TMC');
        // Конструктор для массива данных
        if (is_array($data)) {
            $this->initializeFromArray($data);
        }
        // Конструктор для InventoryItem и комментария
        if ($ID_TMC !== null && $IDBrigade !== null) {
            $this->initializeFromItem($ID_TMC, $IDBrigade);
        }
    }

    private function initializeFromArray(array $data): void
    {
        $this->ID_TMC= isset($data['ID_TMC']) ? (int)$data['ID_TMC'] : 0;
        $this->ID_LBT= isset($data['ID_LBT']) ? (int)$data['ID_LBT'] : 0;
        $this->IDBrigade= isset($data['IDBrigade']) ? (int)$data['IDBrigade'] : 0;
    }

    private function initializeFromItem(int $ID_TMC, int $IDBrigade): void
    {
        $this->ID_TMC = $ID_TMC;
        $this->IDBrigade = $IDBrigade;
        $this->ID_LBT = 0;
    }

    public function getId(): ?int {
        return $this->ID_LBT ?? 0;
    }



    /**
     * Получение сохраняемых свойств
     * @return string[]
     */
    public function getPersistableProperties(): array
    {
        return [
            'ID_TMC',
            'IDBrigade'
        ];
    }
}