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
/*
<td><?= number_format($item->RepairCost, 2, ',', ' ') ?></td>
<th>Сумма ремонта (руб.)</th>
<tfoot>
                            <tr class="table-info">
                                <td colspan="6" class="text-end"><strong>Итого:</strong></td>
                                <td><strong><?= number_format($totalRepairCost_Basket, 2, ',', ' ') ?> руб.</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
*/
?>

<div class="modal fade" id="repairBasketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Корзина ремонта</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="report-header mb-4">                    
                    <p>Дата формирования: <?= date('d.m.Y') ?></p>
                    <p>Количество позиций: <?= $totalCount ?></p>
                    <p>Общая сумма ремонта: <strong><?= number_format($totalRepairCost_Basket, 2, ',', ' ') ?> руб.</strong></p>
                </div>
                
                <?php if ($totalCount > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Регистр</th>
                                <th>Наименование</th>
                                <th>Бренд</th>
                                <th>Серийный номер</th>
                                <th>Ответственный</th>
                                <th>Локация</th>                                
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
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="returnFromBasket(<?= $item->ID_TMC ?>)">
                                        Вернуть
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>                        
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">Корзина пуста</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Функция открытия модального окна корзины
    function openBasketModal() {
        document.getElementById("repairBasketModal").style.display = "block";
    }

    // Функция закрытия модального окна корзины
    function closeBasketModal() {
        document.getElementById("repairBasketModal").style.display = "none";
        // Перезагружаем страницу для обновления данных
        location.reload();
    }

    
    // Закрытие модального окна при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('repairBasketModal')) {
            closeBasketModal();
        }
    });
</script>