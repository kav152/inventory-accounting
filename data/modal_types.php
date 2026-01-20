<?php
require_once __DIR__ . '/../src/View/ModalLoader/UserModalLoader.php';
require_once __DIR__ . '/../src/View/ModalLoader/CardItemLoader.php';

return [
    'userModal' => [
        'loader' => UserModalLoader::class,
        'modalId' => 'userModal'
    ],
    'cardItemModal' => [
        'loader' => CardItemLoader::class,
        'modalId' => 'cardItemModal'
    ],
    'distributeModal' => [
        'loader' => DistributeModalLoader::class,
        'modalId' => 'distributeModal'
    ],
    'workModal' => [
        'loader' => WorkModalLoader::class,
        'modalId' => 'workModal'
    ],
    'serviceModal' => [
        'loader' => SendToServiceModalLoader::class,
        'modalId' => 'serviceModal'
    ],
    'locationModal' => [
        'loader' => LocationModalLoader::class,
        'modalId' => 'locationModal'
    ],
    'locationServiceModal' => [
        'loader' => LocationServiceModalLoader::class,
        'modalId' => 'locationServiceModal'
    ],
    'repairBasketModal' => [
        'loader' => RepairBasketModalLoader::class,
        'modalId' => 'repairBasketModal'
    ],
    'edit_write_off' => [
        'loader' => EditWriteOffModalLoader::class,
        'modalId' => 'edit_write_off'
    ],
];