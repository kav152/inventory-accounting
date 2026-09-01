<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/processCUDInventoryItem.log');

require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
require_once __DIR__ . '/../../Entity/Location.php';
require_once __DIR__ . '/../../Repositories/LocationRepository.php';
require_once __DIR__ . '/../ItemController.php';

class processCUDInventoryItem extends CUDHandler
{
    private ItemController $itemController;
    public function __construct()
    {
        DatabaseFactory::setConfig();
        parent::__construct(new ItemController(), InventoryItem::class);
        $this->itemController = new ItemController();
    }

    protected function prepareData($postData)
    {
        return [
            'NameTMC' => $postData['nameTMC'] ?? '',
            'IDTypesTMC' => $postData['idTypeTMC'],
            'IDBrandTMC' => $postData['idBrand'],
            'IDModel' => $postData['idModel'] ?? 0,
            'SerialNumber' => !empty($postData['serialNumber']) ? $postData['serialNumber'] : null,
            'Status' => StatusItem::NotDistributed,
            'IDLocation' => isset($postData['idLocation']) ? (int) $postData['idLocation'] : 0,
            'FormsJointStockCompanies' => trim((string) ($postData['legalEntity'] ?? '')),
        ];
    }

    protected function create($data, ?int $patofID = null)
    {
        try {
            $location = $this->resolveLocation((int) ($data['IDLocation'] ?? 0));
            if (!$location) {
                throw new Exception('Локация не найдена');
            }

            $data['IDLocation'] = $location->IDLocation;
            $this->applyLegalToLocation($location, (string) ($data['FormsJointStockCompanies'] ?? ''));
            unset($data['FormsJointStockCompanies']);

            $inventoryItem = new InventoryItem($data);
            $createdItem = $this->itemController->create($inventoryItem);
            $createdItem->Location = $location;

            if (!$createdItem) {
                throw new Exception('Не удалось создать InventoryItem через фабрику');
            }

            $historyOperations = new HistoryOperationsController();
            $historyOperations->OperationCreateTMC($createdItem);

            return $createdItem;

        } catch (Exception $e) {
            error_log("Ошибка при создании InventoryItem: " . $e->getMessage());
            throw $e;
        }
    }

    protected function update($id, $data, ?int $patofID = null)
    {
        try {
            $existingItem = $this->itemController->getInventoryItem($id);
            if (!$existingItem) {
                throw new Exception("InventoryItem с ID {$id} не найден");
            }

            $fieldsToUpdate = ['NameTMC', 'IDTypesTMC', 'IDBrandTMC', 'IDModel', 'SerialNumber'];
            foreach ($fieldsToUpdate as $field) {
                if (array_key_exists($field, $data)) {
                    $existingItem->$field = $data[$field];
                }
            }

            $requestedLocationId = (int) ($data['IDLocation'] ?? 0);
            $location = $this->resolveLocation(
                $requestedLocationId > 0 ? $requestedLocationId : (int) ($existingItem->IDLocation ?? 0)
            );
            if ($location) {
                $existingItem->IDLocation = $location->IDLocation;
                $this->applyLegalToLocation($location, (string) ($data['FormsJointStockCompanies'] ?? ''));
                $existingItem->Location = $location;
            }

            $updatedItem = $this->itemController->update($existingItem);

            if ($updatedItem) {
                $registrationInventoryItemRepository = new RegistrationInventoryItemRepository(
                    DatabaseFactory::create()
                );
                $regItem = $registrationInventoryItemRepository->findById($id, "IDRegItem");
                if ($regItem) {
                    $regItem->ChangeDate = date('Y-m-d\TH:i:s');
                    $registrationInventoryItemRepository->save($regItem);
                }

                $historyOperations = new HistoryOperationsController();
                $historyOperations->OperationUpdateTMC($updatedItem);
            }

            return $updatedItem;

        } catch (Exception $e) {
            error_log("Ошибка при обновлении InventoryItem: " . $e->getMessage());
            throw $e;
        }
    }

    protected function delete($data): bool
    {
        try {
            $existingItem = $this->itemController->getInventoryItem($data->getId());
            if (!$existingItem) {
                throw new Exception("InventoryItem с ID {$data->getId()} не найден");
            }

            return $this->itemController->delete($data);

        } catch (Exception $e) {
            error_log("Ошибка при удалении InventoryItem: " . $e->getMessage());
            throw $e;
        }
    }

    private function resolveLocation(int $locationId): ?Location
    {
        try {
            $locationRepo = new LocationRepository(DatabaseFactory::create());
            if ($locationId > 0) {
                $location = $locationRepo->findById($locationId, 'IDLocation');
                if ($location) {
                    return $location;
                }
            }
            return $this->getMainWarehouse();
        } catch (Exception $e) {
            error_log("Ошибка при получении локации: " . $e->getMessage());
            return null;
        }
    }

    private function applyLegalToLocation(Location $location, string $legalEntity): void
    {
        $legalEntity = trim($legalEntity);
        $current = trim((string) ($location->FormsJointStockCompanies ?? ''));
        if ($legalEntity === $current) {
            return;
        }

        $location->FormsJointStockCompanies = $legalEntity;
        $locationRepo = new LocationRepository(DatabaseFactory::create());
        $saved = $locationRepo->save($location);
        if ($saved === null) {
            throw new Exception('Не удалось сохранить юр. лицо для локации');
        }
    }

    private function getMainWarehouse(): ?Location
    {
        try {
            $locationRepo = new LocationRepository(DatabaseFactory::create());
            $sql = 'WHERE isMainWarehouse = 1';
            return $locationRepo->first($sql);
        } catch (Exception $e) {
            error_log("Ошибка при получении основного склада: " . $e->getMessage());
            return null;
        }
    }

    protected function prepareResultEntity($inventoryItem)
    {
        return [
            'id' => $inventoryItem->getId(),
            'nameTMC' => $inventoryItem->NameTMC,
            'serialNumber' => $inventoryItem->SerialNumber,
            'BrandTMC' => [
                'NameBrand' => $inventoryItem->BrandTMC->NameBrand ?? 'бренд не опредлен!',
            ],
            'Status' => (new StatusItem())->getDescription($inventoryItem->Status),
            'User' => [
                'FIO' => $inventoryItem->User->FIO ?? 'Пользователь не определен!',
            ],
            'Location' => [
                'NameLocation' => $inventoryItem->Location->NameLocation ?? 'Локация не определена!',
                'FormsJointStockCompanies' => trim((string) ($inventoryItem->Location->FormsJointStockCompanies ?? '')),
            ],
        ];
    }
}

$handler = new processCUDInventoryItem();
$handler->handleRequest();
