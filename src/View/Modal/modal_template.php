<?php
/**
 * Шаблон для модальных окон
 * 
 * @param string $id ID модального окна
 * @param string $title Заголовок модального окна
 * @param string $body Содержимое тела модального окна
 * @param string $size Размер модального окна (lg, xl или пусто)
 */
?>
<div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog <?= !empty($size) ? 'modal-' . $size : '' ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $title ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= $body ?>
            </div>
        </div>
    </div>
</div>