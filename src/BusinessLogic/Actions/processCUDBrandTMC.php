<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDBrandTMC.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/BrandTMC.php';
require_once __DIR__ . '/../PropertyController.php';

class processCUDBrandTMC extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new PropertyController(), BrandTMC::class);
    }

    protected function prepareData($postData)
    {
        //error_log("Данные BrandTMC: " . print_r($postData, true));
        
        return [
            'NameBrand' => $postData['NameBrand'] ?? '',
        ];
    }

    protected function create($data, int $patofID = null)
    {
        //error_log('data function create' . print_r($data, true));
        //error_log('patofID: ' . $patofID);

        $brandTMC = parent::create($data);
        if ($patofID && $brandTMC->getId()) {
            $this->createLinkTypeToBrand($patofID, $brandTMC->getId());
        }
        return $brandTMC;
    }

    /**
     * Создает связь между типом ТМЦ и брендом
     * @param int $typeTMCId
     * @param int $brandTMCId
     * @throws Exception
     * @return void
     */
    private function createLinkTypeToBrand(int $typeTMCId, int $brandTMCId): void
    {
        try {
            $link = new LinkTypeToBrand([
                'IDTypesTMC' => $typeTMCId,
                'IDBrandTMC' => $brandTMCId
            ]);
            
            $controller = new PropertyController();
            $resultLink = $controller->create($link);
            if($resultLink)
                error_log("Cвязь c TypeToBrand не сформирована: TypeTMC=$typeTMCId, Brand=$brandTMCId");
            else            
                error_log("Создана связь TypeToBrand: TypeTMC=$typeTMCId, Brand=$brandTMCId");
            
        } catch (Exception $e) {
            error_log("Ошибка при создании связи TypeToBrand: " . $e->getMessage());
            throw new Exception("Не удалось создать связь типа ТМЦ с брендом");
        }
    }

    protected function prepareResultEntity($brandTMC)
    {
        return [
            'id' => $brandTMC->getId(),
            'NameBrand' => $brandTMC->NameBrand            
        ];
    }
}

// Использование
$handler = new processCUDBrandTMC();
$handler->handleRequest();