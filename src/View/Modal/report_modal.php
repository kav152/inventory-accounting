<?php
//session_start();
if (!isset($_SESSION['IDUser'])) {
    exit('Unauthorized');
}

require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../../BusinessLogic/ItemRepairController.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
DatabaseFactory::setConfig();

$container = new ItemController();
$repairContainer = new ItemRepairController();
$repairItems = $repairContainer->writeOffItems();

// Группируем данные по ID_TMC
$groupedItems = [];
foreach ($repairItems as $item) {
    $id = $item->ID_TMC;
    if (!isset($groupedItems[$id])) {
        $groupedItems[$id] = [
            'main' => $item,
            'repairs' => []
        ];
    }
    $groupedItems[$id]['repairs'][] = $item;
}

// Вычисляем общую сумму ремонта
$totalRepairCost = 0;
foreach ($groupedItems as $item) {
    foreach ($item['repairs'] as $repair) {
        $totalRepairCost += $repair->RepairCost;
    }
}
?>

<div id="reportModal" class="report-modal">
    <div class="report-content" id="reportContent">
        <span class="close">&times;</span>
        <h4>Отчет по списанию/ремонтам ТМЦ</h4>
        <div class="report-header mb-4">                    
            <p>Дата формирования: <?= date('d.m.Y') ?></p>
            <p>Общее количество позиций: <?= count($groupedItems) ?></p>
            <p>Общая сумма ремонта: <?= number_format($totalRepairCost, 2, ',', ' ') ?> руб.</p>
        </div>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Регистр</th>
                    <th>Наименование</th>
                    <th>Бренд</th>
                    <th>Серийный номер</th>
                    <th>Ответственный</th>
                    <th>Локация</th>
                    <th>Сумма ремонта (руб.)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedItems as $item): 
                    $mainItem = $item['main'];
                    $repairs = $item['repairs'];
                    $totalCost = 0;
                    foreach ($repairs as $repair) {
                        $totalCost += $repair->RepairCost;
                    }
                ?>
                    <tr>
                        <td><?= $mainItem->ID_TMC ?></td>
                        <td><?= htmlspecialchars($mainItem->InventoryItem->NameTMC) ?></td>
                        <td><?= htmlspecialchars($mainItem->InventoryItem->BrandTMC->NameBrand ?? '') ?></td>
                        <td><?= htmlspecialchars($mainItem->InventoryItem->SerialNumber ?? '') ?></td>
                        <td><?= htmlspecialchars($mainItem->RegistrationInventoryItem->User->FIO ?? '') ?></td>
                        <td><?= htmlspecialchars($mainItem->InventoryItem->Location->NameLocation ?? '') ?></td>
                        <td><?= number_format($totalCost, 2, ',', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-info">
                    <td colspan="6" class="text-end"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($totalRepairCost, 2, ',', ' ') ?> руб.</strong></td>
                </tr>
            </tfoot>
        </table>
        <div class="mt-4 no-print">
            <button class="btn btn-primary" onclick="printReport()">Печать</button>
            <button class="btn btn-success" onclick="exportToPDF()">Экспорт в PDF</button>
        </div>
    </div>
</div>

<script>
function printReport() {
    // Создаем новое окно для печати
    const printWindow = window.open('', '_blank');
    
    // Получаем содержимое отчета
    const reportContent = document.getElementById('reportContent').innerHTML;
    
    // Удаляем кнопки из содержимого для печати
    const contentWithoutButtons = reportContent.replace(/<div class="mt-4 no-print">[\s\S]*?<\/div>/, '');
    
    // Записываем в новое окно стили и содержимое
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Отчет по списанию ТМЦ</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                @media print {
                    .no-print { display: none; }
                }
                .report-modal { display: block !important; }
                .report-content { 
                    width: 100% !important; 
                    margin: 0 !important; 
                    padding: 0 !important; 
                    border: none !important; 
                }
            </style>
        </head>
        <body>
            ${contentWithoutButtons}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Запускаем печать после загрузки контента
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function exportToPDF() {
    alert('Функция экспорта в PDF будет реализована в будущем');
}

// Закрытие модального окна
document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('reportModal').style.display = 'none';
});

window.addEventListener('click', function(event) {
    if (event.target == document.getElementById('reportModal')) {
        document.getElementById('reportModal').style.display = 'none';
    }
});
</script>