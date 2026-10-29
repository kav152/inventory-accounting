<?php
/**
 * Авто-миграции для прод-БД, где код уже ждёт колонки/таблицы, а SQL ещё не накатили.
 */
class SchemaEnsure
{
    /** @var array<string, bool> */
    private static array $done = [];

    public static function ensureLocationColumns(PDO $pdo): void
    {
        $key = 'Location';
        if (!empty(self::$done[$key])) {
            return;
        }
        self::$done[$key] = true;

        $columns = [
            'FormsJointStockCompanies' => 'NVARCHAR(500) NULL',
            'Location2' => 'NVARCHAR(500) NULL',
            'Phone' => 'NVARCHAR(100) NULL',
            'Contacts' => 'NVARCHAR(500) NULL',
            'Email' => 'NVARCHAR(255) NULL',
        ];

        foreach ($columns as $name => $definition) {
            self::addColumnIfMissing($pdo, 'Location', $name, $definition);
        }
    }

    public static function ensureLegalEntityTable(PDO $pdo): void
    {
        $key = 'LegalEntity';
        if (!empty(self::$done[$key])) {
            return;
        }
        self::$done[$key] = true;

        try {
            $pdo->exec(
                "IF OBJECT_ID(N'dbo.LegalEntity', N'U') IS NULL
                 BEGIN
                     CREATE TABLE dbo.LegalEntity (
                         IDLegalEntity INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                         NameLegalEntity NVARCHAR(500) NOT NULL,
                         isActive BIT NOT NULL CONSTRAINT DF_LegalEntity_isActive DEFAULT 1
                     );
                 END"
            );
        } catch (Throwable $e) {
            error_log('SchemaEnsure LegalEntity: ' . $e->getMessage());
        }
    }

    public static function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
    {
        try {
            $sql = "IF COL_LENGTH('dbo.{$table}', '{$column}') IS NULL
                        ALTER TABLE dbo.[{$table}] ADD [{$column}] {$definition};";
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("SchemaEnsure {$table}.{$column}: " . $e->getMessage());
        }
    }

    /**
     * Список колонок таблицы (нижний регистр → реальное имя).
     * @return array<string, string>
     */
    public static function tableColumns(PDO $pdo, string $table): array
    {
        try {
            $stmt = $pdo->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = :t"
            );
            $stmt->execute([':t' => $table]);
            $map = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = (string) ($row['COLUMN_NAME'] ?? '');
                if ($name !== '') {
                    $map[strtolower($name)] = $name;
                }
            }
            return $map;
        } catch (Throwable $e) {
            error_log('SchemaEnsure tableColumns: ' . $e->getMessage());
            return [];
        }
    }
}
