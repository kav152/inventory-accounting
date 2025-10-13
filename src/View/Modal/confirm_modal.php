<?php if ($confirmCount > 0): ?>

    <?php
    include __DIR__ . '/message_modal.php';
    ?>
    <!-- Модальное окно подтверждения ТМЦ (ConfirmItem) aria-hidden="true" -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Принять ТМЦ на склад</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Серийный номер</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmItems as $item): ?>
                                <tr id="itemRow<?= $item->ID_TMC ?>">
                                    <td><?= $item->ID_TMC ?></td>
                                    <td><?= $item->NameTMC ?></td>
                                    <td><?= $item->SerialNumber ?></td>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <button class="btn btn-success"
                                                onclick="processItem(<?= $item->ID_TMC ?>, 'accept')">
                                                Принять
                                            </button>
                                            <button class="btn btn-danger"
                                                onclick="processItem(<?= $item->ID_TMC ?>, 'reject')">
                                                Отказать
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>