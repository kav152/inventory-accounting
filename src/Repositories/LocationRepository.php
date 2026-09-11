<?php
require_once __DIR__ . "/../Entity/Location.php";
require_once __DIR__ . '/GenericRepository.php';
require_once __DIR__ . '/CityRepository.php';

class LocationRepository extends GenericRepository
{
    public function __construct(Database $database)
    {
        parent::__construct($database, Location::class, 'Location');

        $cytiRepository = new CityRepository($database);
        $this->addRelationship(
            'City',
            $cytiRepository,
            'IDCity',
            'IDCity'
        );
    }

    /**
     * Сохранить только юр. лицо локации (без полного UPDATE всех полей)
     */
    public function updateLegalEntity(int $locationId, string $legalEntity): void
    {
        if ($locationId <= 0) {
            throw new InvalidArgumentException('Не указана локация');
        }

        try {
            $this->updateScalarField(
                $locationId,
                'FormsJointStockCompanies',
                $legalEntity,
                'IDLocation'
            );
        } catch (PDOException $e) {
            $message = $e->getMessage();
            if (stripos($message, 'FormsJointStockCompanies') !== false
                || stripos($message, 'Invalid column name') !== false) {
                throw new RuntimeException(
                    'В таблице Location нет колонки FormsJointStockCompanies. Выполните миграцию database/migrations/001_add_forms_joint_stock_companies.sql',
                    0,
                    $e
                );
            }
            throw new RuntimeException('Не удалось сохранить юр. лицо: ' . $message, 0, $e);
        }
    }
}
