<?php
/**
 * Полная схема LocalDB для локальной работы форм (создать/передать/сервис и т.д.).
 * Запуск: php scripts/fix_localdb_schema.php
 */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Database/DatabaseFactory.php';

DatabaseFactory::setConfig();
$pdo = DatabaseFactory::create()->getConnection();
$pdo->exec("IF DB_ID(N'ConstructionAccounting_new12') IS NULL CREATE DATABASE ConstructionAccounting_new12");
$pdo->exec('USE ConstructionAccounting_new12');

function dropTable(PDO $pdo, string $name): void
{
    $pdo->exec("IF OBJECT_ID(N'dbo.{$name}', N'U') IS NOT NULL DROP TABLE dbo.[{$name}]");
}

// порядок с учётом FK-подобных связей (у нас FK нет, но для ясности)
foreach ([
    'LinkBrigadesToItem',
    'LinkTypeToBrand',
    'LinkBrandToModel',
    'HistoryOperations',
    'CommentsHistory',
    'RepairItem',
    'RegistrationInventoryItem',
    'InventoryItem',
    'Brigades',
    'BrandTMC',
    'ModelTMC',
    'TypesTMC',
    'Location',
    'City',
    'LegalEntity',
    // User не трогаем, если уже есть — ниже создадим при отсутствии
] as $t) {
    dropTable($pdo, $t);
}

$pdo->exec("
IF OBJECT_ID(N'dbo.[User]', N'U') IS NULL
CREATE TABLE dbo.[User] (
    IDUser INT IDENTITY(1,1) PRIMARY KEY,
    Surname NVARCHAR(100) NOT NULL,
    Name NVARCHAR(100) NOT NULL,
    Patronymic NVARCHAR(100) NULL,
    Password NVARCHAR(255) NOT NULL,
    Status INT NOT NULL DEFAULT 0,
    isActive BIT NOT NULL DEFAULT 1
);
");

$pdo->exec("
CREATE TABLE dbo.City (
    IDCity INT IDENTITY(1,1) PRIMARY KEY,
    NameCity NVARCHAR(200) NULL,
    Address NVARCHAR(300) NULL
);
CREATE TABLE dbo.LegalEntity (
    IDLegalEntity INT IDENTITY(1,1) PRIMARY KEY,
    NameLegalEntity NVARCHAR(200) NOT NULL,
    isActive BIT NOT NULL DEFAULT 1
);
CREATE TABLE dbo.TypesTMC (
    IDTypesTMC INT IDENTITY(1,1) PRIMARY KEY,
    NameTypesTMC NVARCHAR(200) NOT NULL,
    NameImage NVARCHAR(200) NULL
);
CREATE TABLE dbo.BrandTMC (
    IDBrandTMC INT IDENTITY(1,1) PRIMARY KEY,
    NameBrand NVARCHAR(200) NULL
);
CREATE TABLE dbo.ModelTMC (
    IDModel INT IDENTITY(1,1) PRIMARY KEY,
    NameModel NVARCHAR(200) NULL
);
CREATE TABLE dbo.LinkTypeToBrand (
    IDTypesTMC INT NOT NULL,
    IDBrandTMC INT NOT NULL
);
CREATE TABLE dbo.LinkBrandToModel (
    IDBrandTMC INT NOT NULL,
    IDModel INT NOT NULL
);
CREATE TABLE dbo.Location (
    IDLocation INT IDENTITY(1,1) PRIMARY KEY,
    NameLocation NVARCHAR(200) NULL,
    IDCity INT NULL,
    Address NVARCHAR(300) NULL,
    isMainWarehouse BIT NULL DEFAULT 0,
    FormsJointStockCompanies NVARCHAR(200) NULL,
    IsRepair BIT NOT NULL DEFAULT 0,
    Phone NVARCHAR(50) NULL,
    Contacts NVARCHAR(200) NULL,
    Email NVARCHAR(100) NULL,
    Location2 NVARCHAR(200) NULL
);
CREATE TABLE dbo.InventoryItem (
    ID_TMC INT IDENTITY(1,1) PRIMARY KEY,
    NameTMC NVARCHAR(300) NOT NULL,
    Status INT NOT NULL DEFAULT 0,
    SerialNumber NVARCHAR(100) NULL,
    IDTypesTMC INT NULL,
    IDBrandTMC INT NULL,
    IDModel INT NULL,
    IDLocation INT NULL
);
CREATE TABLE dbo.RegistrationInventoryItem (
    IDRegItem INT NOT NULL PRIMARY KEY,
    CreationDate DATETIME NULL,
    CreatedUser INT NULL,
    CurrentUser INT NULL,
    [СhangeDate] DATETIME NULL
);
CREATE TABLE dbo.Brigades (
    IDBrigade INT IDENTITY(1,1) PRIMARY KEY,
    NameBrigade NVARCHAR(200) NULL,
    IDResponsibleIssuing INT NULL,
    NameBrigadir NVARCHAR(200) NULL
);
CREATE TABLE dbo.LinkBrigadesToItem (
    ID_LBT INT IDENTITY(1,1) PRIMARY KEY,
    IDBrigade INT NOT NULL,
    ID_TMC INT NOT NULL
);
CREATE TABLE dbo.RepairItem (
    ID_Repair INT IDENTITY(1,1) PRIMARY KEY,
    ID_TMC INT NULL,
    IDLocation INT NULL,
    InvoiceNumber NVARCHAR(200) NULL,
    RepairCost DECIMAL(18,2) NULL DEFAULT 0,
    UPD NVARCHAR(200) NULL,
    RepairDescription NVARCHAR(MAX) NULL,
    DateToService DATETIME NULL,
    DateReturnService DATETIME NULL,
    inBasket BIT NOT NULL DEFAULT 0
);
CREATE TABLE dbo.CommentsHistory (
    IDComment INT IDENTITY(1,1) PRIMARY KEY,
    ValueComment NVARCHAR(MAX) NULL
);
CREATE TABLE dbo.HistoryOperations (
    IDHistoryItem INT IDENTITY(1,1) PRIMARY KEY,
    HistoryData DATETIME NULL,
    IDComment INT NULL,
    IDUser INT NULL,
    ID_TMC INT NULL,
    IDLocation INT NULL
);
");

// users
$countUsers = (int) $pdo->query('SELECT COUNT(*) FROM [User]')->fetchColumn();
if ($countUsers === 0) {
    $hash = password_hash('123', PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO [User] (Surname, Name, Patronymic, Password, Status, isActive) VALUES (?,?,?,?,?,1)');
    foreach ([
        ['Большедворов', 'Константин', 'Викторович', 0],
        ['Ототюк', 'Андрей', 'Дмитриевич', 0],
        ['Кудрявцева', 'Людмила', 'Александровна', 1],
        ['Дубровская', 'Елена', 'Фёдоровна', 1],
    ] as $u) {
        $st->execute([$u[0], $u[1], $u[2], $hash, $u[3]]);
    }
}

$pdo->exec("INSERT INTO City (NameCity, Address) VALUES (N'Москва', N'ул. Примерная 1')");
$pdo->exec("INSERT INTO LegalEntity (NameLegalEntity, isActive) VALUES (N'ООО КСК', 1), (N'АО Строй', 1)");
$pdo->exec("INSERT INTO TypesTMC (NameTypesTMC, NameImage) VALUES
  (N'Электроинструмент', NULL),
  (N'Ручной инструмент', NULL),
  (N'Расходники', NULL)");
$pdo->exec("INSERT INTO BrandTMC (NameBrand) VALUES (N'Bosch'), (N'Makita'), (N'DeWalt'), (N'Hilti')");
$pdo->exec("INSERT INTO ModelTMC (NameModel) VALUES (N'GBH 2-26'), (N'DHP485'), (N'DCD791'), (N'TE 6-A36')");

// связи тип→бренд и бренд→модель
$types = $pdo->query('SELECT IDTypesTMC FROM TypesTMC ORDER BY IDTypesTMC')->fetchAll(PDO::FETCH_COLUMN);
$brands = $pdo->query('SELECT IDBrandTMC FROM BrandTMC ORDER BY IDBrandTMC')->fetchAll(PDO::FETCH_COLUMN);
$models = $pdo->query('SELECT IDModel FROM ModelTMC ORDER BY IDModel')->fetchAll(PDO::FETCH_COLUMN);

$ltb = $pdo->prepare('INSERT INTO LinkTypeToBrand (IDTypesTMC, IDBrandTMC) VALUES (?,?)');
$lbm = $pdo->prepare('INSERT INTO LinkBrandToModel (IDBrandTMC, IDModel) VALUES (?,?)');
foreach ($brands as $b) {
    $ltb->execute([$types[0], $b]);
}
foreach ($brands as $i => $b) {
    $lbm->execute([$b, $models[$i] ?? $models[0]]);
}

$pdo->exec("INSERT INTO Location (NameLocation, IDCity, Address, isMainWarehouse, FormsJointStockCompanies, IsRepair, Phone)
VALUES
 (N'Склад №1', 1, N'Складская 1', 1, N'ООО КСК', 0, N''),
 (N'Объект Север', 1, N'Северная 10', 0, N'ООО КСК', 0, N''),
 (N'Офис', 1, N'Центр 5', 0, N'АО Строй', 0, N''),
 (N'Сервис Центр', 1, N'Ремонтная 2', 0, N'ООО КСК', 1, N'+7')");

$userId = (int) $pdo->query('SELECT TOP 1 IDUser FROM [User] WHERE Status=0 ORDER BY IDUser')->fetchColumn();
$locs = $pdo->query('SELECT IDLocation FROM Location WHERE IsRepair=0 ORDER BY IDLocation')->fetchAll(PDO::FETCH_COLUMN);
$serviceLoc = (int) $pdo->query('SELECT TOP 1 IDLocation FROM Location WHERE IsRepair=1')->fetchColumn();

$items = [
    ['Перфоратор Bosch GBH 2-26', 'SN-BOS-001', 1, 0, 0, 0],
    ['Шуруповёрт Makita DHP485', 'SN-MAK-002', 1, 0, 1, 1],
    ['Дрель DeWalt DCD791', 'SN-DEW-003', 1, 0, 2, 0],
    ['Перфоратор Hilti TE 6', 'SN-HIL-004', 5, 0, 3, 1],
    ['УШМ Bosch', 'SN-BOS-005', 0, 0, 0, 2],
    ['Набор бит Makita', 'SN-MAK-006', 1, 1, 1, 0],
];

$ins = $pdo->prepare(
    'INSERT INTO InventoryItem (NameTMC, Status, SerialNumber, IDTypesTMC, IDBrandTMC, IDModel, IDLocation)
     VALUES (?,?,?,?,?,?,?)'
);
$reg = $pdo->prepare(
    'INSERT INTO RegistrationInventoryItem (IDRegItem, CreationDate, CreatedUser, CurrentUser, [СhangeDate])
     VALUES (?,?,?,?,?)'
);
$now = date('Y-m-d H:i:s');
foreach ($items as $it) {
    $ins->execute([
        $it[0],
        $it[2],
        $it[1],
        $types[$it[3]] ?? $types[0],
        $brands[$it[4]],
        $models[$it[4]],
        $locs[$it[5]] ?? $locs[0],
    ]);
    $id = (int) $pdo->lastInsertId();
    $reg->execute([$id, $now, $userId, $userId, $now]);
    echo "TMC {$id}: {$it[0]}\n";
}

$pdo->prepare('INSERT INTO Brigades (NameBrigade, IDResponsibleIssuing, NameBrigadir) VALUES (?,?,?)')
    ->execute(['Бригада 1', $userId, 'Иванов И.И.']);

echo "OK schema+seed. users=" . $pdo->query('SELECT COUNT(*) FROM [User]')->fetchColumn()
    . " items=" . $pdo->query('SELECT COUNT(*) FROM InventoryItem')->fetchColumn()
    . " types=" . $pdo->query('SELECT COUNT(*) FROM TypesTMC')->fetchColumn()
    . " serviceLoc={$serviceLoc}\n";
