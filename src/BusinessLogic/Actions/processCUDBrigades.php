<?php
require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/Brigades.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDBrigades extends CUDHandler
{
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemController(), Brigades::class);
    }

    protected function prepareData($postData)
    {
        //error_log("Данные Brigades: " . print_r($postData, true));

        return [
            // НАСТРОИТЬ ПОЛЯ ПОД КОНКРЕТНУЮ СУЩНОСТЬ
            'IDBrigade' => $postData['id'] ?? 0,
            'NameBrigade' => $postData['brigade_name'] ?? null,
            'IDResponsibleIssuing' => $postData['responsible'] ?? 0,
            'NameBrigadir' => $postData['brigadir'] ?? null,
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        $brigades = parent::create($data);
        return $brigades;
    }
    protected function delete($data):bool
    {
        //error_log('delete');
        //error_log(print_r($data));

        //$brigadesRepository = new BrigadesRepository(DatabaseFactory::create());
        //$brigades = $brigadesRepository->findById($id, 'IDBrigade');

        $brigades = parent::delete($data);
        return $brigades;
    }

    protected function prepareResultEntity($brigades)
    {
        return [
            'id' => $brigades->IDBrigade ?? 0,
            'NameBrigade' => $brigades->NameBrigade ?? "",
            'IDResponsibleIssuing' => $brigades->IDResponsibleIssuing ?? 0,
            'NameBrigadir' => $brigades->NameBrigadir ?? "",
        ];
    }
}

// Использование
$handler = new processCUDBrigades();
$handler->handleRequest();