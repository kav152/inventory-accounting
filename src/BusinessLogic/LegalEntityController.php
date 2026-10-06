<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/LegalEntityController.log');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Logging/Logger.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';
require_once __DIR__ . '/../Container.php';
require_once __DIR__ . '/../Repositories/LegalEntityRepository.php';
require_once __DIR__ . '/../Entity/LegalEntity.php';
require_once __DIR__ . '/CudService/CUDFactory.php';

class LegalEntityController
{
    private Container $container;
    private Logger $logger;
    private CUDFactory $cudFactory;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->container = new Container();
        $this->container->set(Database::class, function () {
            return DatabaseFactory::create();
        });
        $this->container->set(Logger::class, function () {
            return new Logger(__DIR__ . '/../storage/logs/LegalEntityController.log');
        });
        $this->logger = $this->container->get(Logger::class);
        $this->cudFactory = new CUDFactory(
            $this->container->get(Database::class),
            $this->logger,
            $this->container
        );
    }

    public function getLegalEntities(bool $onlyActive = false)
    {
        try {
            $repo = $this->container->get(LegalEntityRepository::class);
            if ($onlyActive) {
                return $repo->findBy('WHERE isActive = 1 ORDER BY NameLegalEntity');
            }
            return $repo->findBy('ORDER BY NameLegalEntity');
        } catch (Throwable $e) {
            error_log('LegalEntity getLegalEntities: ' . $e->getMessage());
            return null;
        }
    }

    public function getLegalEntity(?int $id): ?LegalEntity
    {
        if (!$id) {
            return new LegalEntity();
        }
        try {
            $repo = $this->container->get(LegalEntityRepository::class);
            return $repo->findById((int) $id, 'IDLegalEntity') ?: new LegalEntity();
        } catch (Throwable $e) {
            error_log('LegalEntity getLegalEntity: ' . $e->getMessage());
            return new LegalEntity();
        }
    }

    /** Уникальные названия для выпадающих списков */
    public function getLegalEntityNames(bool $onlyActive = true): array
    {
        try {
            $items = $this->getLegalEntities($onlyActive);
            $names = [];
            if ($items) {
                foreach ($items as $item) {
                    $name = trim((string) ($item->NameLegalEntity ?? ''));
                    if ($name !== '') {
                        $names[$name] = $name;
                    }
                }
            }
            ksort($names, SORT_NATURAL | SORT_FLAG_CASE);
            return array_values($names);
        } catch (Throwable $e) {
            error_log('LegalEntity getLegalEntityNames: ' . $e->getMessage());
            return [];
        }
    }

    public function create($object, $patofID = null): ?object
    {
        return $this->cudFactory->create($object);
    }

    public function update($object): ?object
    {
        return $this->cudFactory->update($object);
    }

    public function delete($object): bool
    {
        return $this->cudFactory->delete($object);
    }
}
