<?php
/**
 * Совместимый URL /api/sendToService.php → основной обработчик.
 * Старые закладки/кэш браузера иногда бьют сюда (иначе 404).
 */
require_once __DIR__ . '/../src/BusinessLogic/Actions/processCUDSendToService.php';
