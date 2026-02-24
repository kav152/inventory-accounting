<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/PropertyController.log');
require_once __DIR__ . '/../Repositories/BrandTMCRepository.php';
require_once __DIR__ . '/../Repositories/ModelTMCRepository.php';
require_once __DIR__ . '/../Repositories/LinkBrandToModelRepository.php';
require_once __DIR__ . '/../Repositories/LinkTypeToBrandRepository.php';
require_once __DIR__ . '/../Repositories/TypesTMCRepository.php';
require_once __DIR__ . '/../Entity/IProperty.php';
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/CudService/CUDFactory.php';

class PropertyController
{
    private Container $container;
    private Logger $logger;

    private CUDFactory $cudFactory;

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

        $this->cudFactory = new CUDFactory($this->container->get(Database::class), $this->logger, $this->container);
    }

    public function getTypeTMC(): Collection
    {
        try {
            $typeTMCRepository = $this->container->get(TypesTMCRepository::class);
            $result = $typeTMCRepository->getAll();

            // Если результат null, возвращаем пустую коллекцию
            if ($result === null) {
                //$this->logger->log('Предупреждение', 'TypesTMCRepository::getAll() вернул null');
                return new Collection(TypesTMC::class, []);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->log('Ошибка', 'Ошибка в getTypeTMC(): ' . $e->getMessage());
            return new Collection(TypesTMC::class, []);
        }
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

    /* public function addBrand(string $valueProp, int $propertyId): ?IProperty
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
    }*/

    /* public function addModel(string $valueProp, int $propertyId): ?IProperty
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
    }*/

    public function createLinkBrandToModel(int $brandTMCId, int $modelTMCId): void
    {
        try {
            // Временное прямое создание для диагностики
            $repository = new LinkBrandToModelRepository($this->container->get(Database::class));
            $link = new LinkBrandToModel([
                'IDBrandTMC' => $brandTMCId,
                'IDModel' => $modelTMCId
            ]);

            $result = $repository->save($link, 'create');
            //error_log("Прямое создание через репозиторий: " . print_r($result, true));
        } catch (Exception $e) {
            error_log("Ошибка прямого создания: " . $e->getMessage());
            throw $e;
        }
    }

    public function create($object): ?object
    {
        $result = $this->cudFactory->create($object);
        return $result;
    }
    public function update($object): ?object
    {
        return $this->cudFactory->update($object);
    }
    public function delete($object): ?bool
    {
        return $this->cudFactory->delete($object);
    }
}
