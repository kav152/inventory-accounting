<?php
session_start();
require_once __DIR__ . '/work_modal.php';
require_once __DIR__ . '/../ModalLoader/WorkModalLoader.php';
require_once __DIR__ . '/../ModalLoader/AtWorkModalLoader.php';
require_once __DIR__ . '/../ModalLoader/ConfirmModalLoader.php';
require_once __DIR__ . '/../ModalLoader/ConfirmRepairModalLoader.php';
require_once __DIR__ . '/../ModalLoader/DistributeModalLoader.php';
require_once __DIR__ . '/../ModalLoader/SendToServiceModalLoader.php';
require_once __DIR__ . '/../ModalLoader/CardItemLoader.php';
require_once __DIR__ . '/../ModalLoader/EditWriteOffModalLoader.php';
require_once __DIR__ . '/../ModalLoader/UserModalLoader.php';
require_once __DIR__ . '/../Modal/message_modal.php';


$modalTypes = [
    'distribute' => DistributeModalLoader::class,
    'work' => WorkModalLoader::class,
    'at_work' => AtWorkModalLoader::class,
    'confirm' => ConfirmModalLoader::class,
    'confirmRepair' => ConfirmRepairModalLoader::class,
    'sendToService' => SendToServiceModalLoader::class,
    'сardItemLoader' => CardItemLoader::class,
    'create_analog' => CardItemLoader::class,
    'edit' => CardItemLoader::class,
    'edit_write_off' => EditWriteOffModalLoader::class,
    'userModal' => UserModalLoader::class
];

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    if (array_key_exists($type, $modalTypes)) {
        $loader = new $modalTypes[$type]();
        //error_log(`тип модалки - $type`);
        echo $loader->load($_GET);
    } else {
        //echo '<div class="alert alert-danger">Неизвестный тип модалки</div>';
        error_log(`Неизвестный тип модалки - $type`);
        $errorMessage = `Неизвестный тип модалки - $type`;
    }
}