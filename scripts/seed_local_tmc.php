<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Database/DatabaseFactory.php';

DatabaseFactory::setConfig();
$pdo = DatabaseFactory::create()->getConnection();

$pdo->exec('DELETE FROM RegistrationInventoryItem');
$pdo->exec('DELETE FROM InventoryItem');
$pdo->exec('DELETE FROM BrandTMC');
$pdo->exec('DELETE FROM ModelTMC');
$pdo->exec('DELETE FROM Location');

$pdo->exec("INSERT INTO BrandTMC (NameBrand) VALUES (N'Bosch'), (N'Makita'), (N'DeWalt'), (N'Hilti')");
$pdo->exec("INSERT INTO ModelTMC (NameModel) VALUES (N'GBH 2-26'), (N'DHP485'), (N'DCD791'), (N'TE 6-A36')");
$pdo->exec("INSERT INTO Location (NameLocation, FormsJointStockCompanies) VALUES
  (N'Склад №1', N'ООО КСК'),
  (N'Объект Север', N'ООО КСК'),
  (N'Офис', N'АО Строй')");

$brands = $pdo->query('SELECT IDBrandTMC FROM BrandTMC ORDER BY IDBrandTMC')->fetchAll(PDO::FETCH_COLUMN);
$models = $pdo->query('SELECT IDModel FROM ModelTMC ORDER BY IDModel')->fetchAll(PDO::FETCH_COLUMN);
$locs = $pdo->query('SELECT IDLocation FROM Location ORDER BY IDLocation')->fetchAll(PDO::FETCH_COLUMN);
$userId = (int) $pdo->query('SELECT TOP 1 IDUser FROM [User] WHERE Status=0 ORDER BY IDUser')->fetchColumn();

$items = [
    ['Перфоратор Bosch GBH 2-26', 'SN-BOS-001', 1, 0, 0],
    ['Шуруповёрт Makita DHP485', 'SN-MAK-002', 1, 1, 1],
    ['Дрель DeWalt DCD791', 'SN-DEW-003', 1, 2, 0],
    ['Перфоратор Hilti TE 6', 'SN-HIL-004', 5, 3, 1],
    ['УШМ Bosch', 'SN-BOS-005', 0, 0, 2],
    ['Набор бит Makita', 'SN-MAK-006', 1, 1, 0],
];

$ins = $pdo->prepare(
    'INSERT INTO InventoryItem (NameTMC, Status, SerialNumber, IDTypesTMC, IDBrandTMC, IDModel, IDLocation)
     VALUES (?,?,?,NULL,?,?,?)'
);
$reg = $pdo->prepare('INSERT INTO RegistrationInventoryItem (IDRegItem, CurrentUser) VALUES (?,?)');

foreach ($items as $it) {
    $ins->execute([$it[0], $it[2], $it[1], $brands[$it[3]], $models[$it[3]], $locs[$it[4]]]);
    $id = (int) $pdo->lastInsertId();
    $reg->execute([$id, $userId]);
    echo $id . ' ' . $it[0] . PHP_EOL;
}

echo 'total=' . $pdo->query('SELECT COUNT(*) FROM InventoryItem')->fetchColumn() . PHP_EOL;
