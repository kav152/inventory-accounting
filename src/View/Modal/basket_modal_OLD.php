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

// Получаем элементы корзины (inBasket = 1)
$basketItems = $repairContainer->getBasketItems();

// Вычисляем общую сумму ремонта
$totalRepairCost_Basket = 0;
$totalCount = 0;
foreach ($basketItems as $item) {
    $totalRepairCost_Basket += $item->RepairCost;
    $totalCount += 1;
}
?>

<div id="repairBasketModal" class="report-modal">
    <div class="report-content" id="basketContent">
        <span class="close" onclick="closeBasketModal()">&times;</span>
        <h4>Корзина</h4>
        <div class="report-header mb-4">                    
            <p>Дата формирования: <?= date('d.m.Y') ?></p>
            <p>Количество позиций: <?= $totalCount ?></p>
            <p>Общая сумма ремонта: <?= number_format($totalRepairCost, 2, ',', ' ') ?> руб.</p>
        </div>
        
        <?php if ($totalCount > 0): ?>
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
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($basketItems as $item): ?>
                <tr id="basket-item-<?= $item->ID_TMC ?>">
                    <td><?= $item->ID_TMC ?></td>
                    <td><?= htmlspecialchars($item->InventoryItem->NameTMC) ?></td>
                    <td><?= htmlspecialchars($item->InventoryItem->BrandTMC->NameBrand ?? '') ?></td>
                    <td><?= htmlspecialchars($item->InventoryItem->SerialNumber ?? '') ?></td>
                    <td><?= htmlspecialchars($item->RegistrationInventoryItem->User->FIO ?? '') ?></td>
                    <td><?= htmlspecialchars($item->InventoryItem->Location->NameLocation ?? '') ?></td>
                    <td><?= number_format($item->RepairCost, 2, ',', ' ') ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="returnFromBasket(<?= $item->ID_TMC ?>)">
                            Вернуть
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-info">
                    <td colspan="6" class="text-end"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($totalRepairCost_Basket, 2, ',', ' ') ?> руб.</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <p>Корзина пуста</p>
        <?php endif; ?>
        
        <div class="mt-4 no-print">
            <button class="btn btn-secondary" onclick="closeBasketModal()">Закрыть</button>
        </div>
    </div>
</div>

<script>
// Функция открытия модального окна корзины
function openBasketModal() {
    document.getElementById("basketModal").style.display = "block";
}

// Функция закрытия модального окна корзины
function closeBasketModal() {
    document.getElementById("basketModal").style.display = "none";
    // Перезагружаем страницу для обновления данных
    location.reload();
}

// Функция возврата элемента из корзины
function returnFromBasket(id) {
    if (confirm('Вы уверены, что хотите вернуть этот элемент из корзины?')) {
        const formData = new FormData();
        formData.append("ID_TMC", id);
        
        fetch('/src/BusinessLogic/ActionsTMC/returnFromBasket.php', {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Удаляем строку из таблицы
                document.getElementById(`basket-item-${id}`).remove();
                
                // Обновляем информацию о количестве элементов
                const rows = document.querySelectorAll('#basketContent tbody tr');
                const count = Array.from(rows).filter(row => row.id.startsWith('basket-item-')).length;
                
                if (count === 0) {
                    document.querySelector('#basketContent table').remove();
                    document.querySelector('#basketContent .report-header').innerHTML += '<p>Корзина пуста</p>';
                }
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка возврата из корзины');
        });
    }
}

// Закрытие модального окна при клике вне его
window.addEventListener('click', function(event) {
    if (event.target == document.getElementById('basketModal')) {
        closeBasketModal();
    }
});
</script>