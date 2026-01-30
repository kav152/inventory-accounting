<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDTypesTMC.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/TypesTMC.php';
require_once __DIR__ . '/../PropertyController.php';

class processCUDTypesTMC extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new PropertyController(), TypesTMC::class);
    }

    protected function prepareData($postData)
    {
        error_log("Данные TypesTMC: " . print_r($postData, true));
        
        return [
            'NameTypesTMC' => $postData['NameTypesTMC'] ?? ''
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $typesTMC = parent::create($data);
        return $typesTMC;
    }
    

    protected function prepareResultEntity($typesTMC)
    {
        return [
            'id' => $typesTMC->getId(),
            'NameTypesTMC' => $typesTMC->NameTypesTMC,
            'NameImage' => $typesTMC->NameImage            
        ];
    }
}

// Использование
$handler = new processCUDTypesTMC();
$handler->handleRequest();