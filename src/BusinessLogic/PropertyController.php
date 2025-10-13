<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/PropertyController.log');
require_once __DIR__ . '/../Repositories/BrandTMCRepository.php';
require_once __DIR__ . '/../Repositories/ModelTMCRepository.php';
require_once __DIR__ . '/../Repositories/LinkBrandToModelRepository.php';
require_once __DIR__ . '/../Repositories/LinkTypeToBrandRepository.php';
require_once __DIR__ . '/../Repositories/TypeTMCRepository.php';
require_once __DIR__ . '/../Entity/IProperty.php';
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';

class PropertyController
{
    private Container $container;
    private Logger $logger;

    public function __construct()
    {
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });

        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/PropertyController.log');
        });
        $this->logger = $this->container->get(Logger::class);
    }

    public function getTypeTMC():Collection
    {
        $typeTMCRepository = $this->container->get(TypeTMCRepository::class);
        return $typeTMCRepository->getAll();
    }

    public function getBrandsByTypeTMC(int $propertyId): Collection
    {        
        $sql = "LEFT JOIN BrandTMC on LinkTypeToBrand.IDBrandTMC = BrandTMC.IDBrandTMC
                where LinkTypeToBrand.IDTypesTMC = {$propertyId}";
                
        $brandTMCRepository = $this->container->get(BrandTMCRepository::class);
        $linkTypeToBrandRepository = $this->container->get(LinkTypeToBrandRepository::class);
        $linkTypeToBrandRepository->addRelationship(
            'BrandTMC',
            $brandTMCRepository,
            'IDBrandTMC',
            'IDBrandTMC'
        );
        $brands = $linkTypeToBrandRepository->findBy($sql);
        return $brands ?? new Collection(BrandTMC::class, []);
    }

    public function getModelsByBrand(int $propertyId): Collection
    {
        $sql = "LEFT JOIN ModelTMC on LinkBrandToModel.IDModel = ModelTMC.IDModel
                where IDBrandTMC = {$propertyId}";

        $modelTMCRepository = $this->container->get(ModelTMCRepository::class);
        $linkBrandToModelRepository = $this->container->get(LinkBrandToModelRepository::class);
        $linkBrandToModelRepository->addRelationship(
            'ModelTMC',
            $modelTMCRepository,
            'IDModel',
            'IDModel'
        );
        $models = $linkBrandToModelRepository->findBy($sql);
        return $models ?? new Collection(ModelTMC::class, []);
    }

    public function addBrand(string $valueProp, int $propertyId): ?IProperty
    {
        $brand = new BrandTMC(['NameBrand' => $valueProp]);        
        $brandTMCRepository = $this->container->get(BrandTMCRepository::class);
        $brandResult = $brandTMCRepository->save($brand);
        $link = new LinkTypeToBrand([
            'IDTypesTMC' => $propertyId,
            'IDBrandTMC' => $brandResult->IDBrandTMC
        ]);        
        $linkBrand = $this->container->get(LinkTypeToBrandRepository::class)->save($link, 'create'); 
        $linkBrand->BrandTMC = $brandResult;
        $this->logger->log('addBrand', 'Возвращение данных', ['linkBrand' => $linkBrand]);
        
        return $linkBrand;
    }

    public function addModel(string $valueProp, int $propertyId): ?IProperty
    {
        $model = new ModelTMC(['NameModel' => $valueProp]);
        $modelTMCRepository = $this->container->get(ModelTMCRepository::class);
        $modelResult = $modelTMCRepository->save($model);
        $link = new LinkBrandToModel([
            'IDBrandTMC' => $propertyId,
            'IDModel' => $modelResult->IDModel
        ]);

        $this->logger->log('addModel', 'Проверка входящих данных', ['link' => $link]);
        $linkModel = $this->container->get(LinkBrandToModelRepository::class)->save($link, 'create');
        $linkModel->ModelTMC = $modelResult;
        $this->logger->log('addBrand', 'Возвращение данных', ['linkModel' => $linkModel]);
       return $linkModel;
    }
}
