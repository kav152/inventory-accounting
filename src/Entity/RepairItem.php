<?php
require_once __DIR__ . '/../Repositories/BaseEntity.php';
class RepairItem extends BaseEntity
{
    public int $ID_Repair;
    public int $ID_TMC;
    public int $IDLocation;
    public float $RepairCost;
    public string $InvoiceNumber;
    public ?string $UPD;
    public string $RepairDescription;
    public string $DateToService;
    public ?string $DateReturnService;
    public bool $inBasket;
    public ?InventoryItem $InventoryItem;
    public ?Location $Location;
    public ?RegistrationInventoryItem $RegistrationInventoryItem;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->ID_Repair = isset($data['ID_Repair']) ? (int) $data['ID_Repair'] : 0;
            $this->ID_TMC = isset($data['ID_TMC']) ? (int) $data['ID_TMC'] : 0;
            $this->IDLocation = isset($data['IDLocation']) ? (int) $data['IDLocation'] : 0;
            $this->RepairCost = floatval($data['RepairCost'] ?? 0.0);
            $this->InvoiceNumber = $data['InvoiceNumber'] ?? '';

            $upd = $data['UPD'] ?? null;
            if (is_array($upd)) {
                $this->UPD = implode($data['UPD']) ?? '';
            } else
                $this->UPD = $data['UPD'] ?? '';

            $this->RepairDescription = $data['RepairDescription'] ?? '';
            // дату отправки не подставляем «сегодня» молча — только если передали явно
            if (array_key_exists('DateToService', $data) && $data['DateToService'] !== null && $data['DateToService'] !== '') {
                $formatted = self::formatDateForSQL($data['DateToService']);
                if ($formatted !== null) {
                    $this->DateToService = $formatted;
                }
            }

            if (array_key_exists('DateReturnService', $data)) {
                if ($data['DateReturnService'] === null || $data['DateReturnService'] === '') {
                    $this->DateReturnService = null;
                } else {
                    $this->DateReturnService = self::formatDateForSQL($data['DateReturnService']);
                }
            } else {
                $this->DateReturnService = null;
            }

            $this->inBasket = isset($data['inBasket']) ? ($data['inBasket'] != 0) : false;
        }
    }

    public function getId(): int
    {
        return $this->ID_Repair ?? 0;
    }

    public function setId(int $id): void
    {
        $this->ID_Repair = $id;
    }

    public function getIdFieldName(): string
    {
        return 'ID_Repair';
    }

    public function getTypeEntity(): string
    {
        return $this::class;
    }

    public function getPersistableProperties(): array
    {
        return [
            'ID_TMC',
            'IDLocation',
            'InvoiceNumber',
            'RepairCost',
            'UPD',
            'RepairDescription',
            'DateToService',
            'DateReturnService',
            'inBasket'
        ];
    }

    public function getReadOnlyFields(): array
    {
        return [];
    }

    public function getAutoDateFields(): array
    {
        // даты задаём из формы / конструктора, не через GETDATE()
        return [];
    }

    /**
     * Нормализация даты для SQL Server (datetime): всегда Y-m-d H:i:s.
     * Принимает дд.мм.гггг, ISO, DateTime из PDO.
     */
    public static function formatDateForSQL($dateString): ?string
    {
        if ($dateString === null || $dateString === '') {
            return null;
        }
        if ($dateString instanceof DateTimeInterface) {
            return $dateString->format('Y-m-d H:i:s');
        }

        $dateString = trim((string) $dateString);
        if ($dateString === '') {
            return null;
        }
        // убрать лишнее из маски/автозаполнения
        $dateString = preg_replace('#[^\d.\-/ :T]#u', '', $dateString) ?? $dateString;
        $dateString = trim($dateString);

        // ДД.ММ.ГГ или ДД.ММ.ГГГГ (и с пробелами)
        if (preg_match('#^(\d{1,2})\s*[.\-/]\s*(\d{1,2})\s*[.\-/]\s*(\d{2}|\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $dateString, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 100) {
                $year += ($year >= 70) ? 1900 : 2000;
            }
            if (!checkdate($month, $day, $year)) {
                return null;
            }
            $h = isset($m[4]) ? (int) $m[4] : 0;
            $i = isset($m[5]) ? (int) $m[5] : 0;
            $s = isset($m[6]) ? (int) $m[6] : 0;
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $h, $i, $s);
        }

        // уже ISO / SQL: 2025-12-19 или 2025-12-19 00:00:00.000
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:\.\d+)?)?#', $dateString, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            if (!checkdate($month, $day, $year)) {
                return null;
            }
            $h = isset($m[4]) ? (int) $m[4] : 0;
            $i = isset($m[5]) ? (int) $m[5] : 0;
            $s = isset($m[6]) ? (int) $m[6] : 0;
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $h, $i, $s);
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
}
