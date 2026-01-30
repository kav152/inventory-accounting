<?php
require_once __DIR__ . '/CUDHandler.php';
require_once __DIR__ . '/../../Entity/InventoryItem.php';
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
        //error_log("Данные InventoryItem: " . print_r($postData, true));

        return [
            'NameTMC' => $postData['nameTMC'] ?? '',
            'IDTypesTMC' => $postData['idTypeTMC'],
            'IDBrandTMC' => $postData['idBrand'],
            'IDModel' => $postData['idModel'] ?? 0,
            'SerialNumber' => !empty($postData['serialNumber']) ? $postData['serialNumber'] : null,
            'Status' => StatusItem::NotDistributed,
            // IDLocation будет установлен в методе create
        ];
    }

    protected function create($data, int $patofID = null)
    {
        //error_log("Данные InventoryItem: " . print_r($data, true));
        try {
            // Получаем основной склад
            $mainWarehouse = $this->getMainWarehouse();
            if (!$mainWarehouse) {
                throw new Exception('Основной склад не найден');
            }

            // Устанавливаем IDLocation основного склада
            $data['IDLocation'] = $mainWarehouse->IDLocation;

            // Создаем объект InventoryItem
            $inventoryItem = new InventoryItem($data);

            // Используем фабрику через контроллер для создания
            $createdItem = $this->itemController->create($inventoryItem);
            $createdItem->Location = $mainWarehouse;

            if (!$createdItem) {
                throw new Exception('Не удалось создать InventoryItem через фабрику');
            }

            // Регистрация в HistoryOperations
            $historyOperations = new HistoryOperationsController();
            $historyOperations->OperationCreateTMC($createdItem);

            return $createdItem;

        } catch (Exception $e) {
            error_log("Ошибка при создании InventoryItem: " . $e->getMessage());
            throw $e;
        }
    }

    protected function update($id, $data, int $patofID = null)
    {
        try {
            // Получаем существующий элемент
            $existingItem = $this->itemController->getInventoryItem($id);
            if (!$existingItem) {
                throw new Exception("InventoryItem с ID {$id} не найден");
            }

            // Обновляем только измененные поля
            $fieldsToUpdate = ['NameTMC', 'IDTypesTMC', 'IDBrandTMC', 'IDModel', 'SerialNumber'];
            foreach ($fieldsToUpdate as $field) {
                if (array_key_exists($field, $data)) {
                    $existingItem->$field = $data[$field];
                }
            }

            // Используем фабрику через контроллер для обновления
            $updatedItem = $this->itemController->update($existingItem);

            if ($updatedItem) {
                // Обновляем дату изменения в RegistrationInventoryItem
                $registrationInventoryItemRepository = new RegistrationInventoryItemRepository(
                    DatabaseFactory::create()
                );
                $regItem = $registrationInventoryItemRepository->findById($id, "IDRegItem");
                if ($regItem) {
                    $regItem->ChangeDate = date('Y-m-d\TH:i:s');
                    $registrationInventoryItemRepository->save($regItem);
                }

                // Регистрация в HistoryOperations
                $historyOperations = new HistoryOperationsController();
                $historyOperations->OperationUpdateTMC($updatedItem);

                
            }

            return $updatedItem;

        } catch (Exception $e) {
            error_log("Ошибка при обновлении InventoryItem: " . $e->getMessage());
            throw $e;
        }
    }

    protected function delete($data):bool
    {
        try {
            // Получаем существующий элемент
            $existingItem = $this->itemController->getInventoryItem($data->getId());
            if (!$existingItem) {
                throw new Exception("InventoryItem с ID {$data->getId()} не найден");
            }

            // Используем фабрику через контроллер для удаления
            return $this->itemController->delete($data);

        } catch (Exception $e) {
            error_log("Ошибка при удалении InventoryItem: " . $e->getMessage());
            throw $e;
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
            ],
        ];
    }
}

// Использование
$handler = new processCUDInventoryItem();
$handler->handleRequest();