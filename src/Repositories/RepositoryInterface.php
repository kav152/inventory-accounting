<?php
interface RepositoryInterface {
    public function getAll(): ?Collection;
    public function findBy(string $property): ?Collection;
    public function findById(int $id, string $nameID): ?object;
    public function save(object $entity, ?string $status = null): ?object;
    public function delete(object $entity): bool;
    public function updateDateWithGetDate(int $id, string $dateField, ?string $idField = null): bool;
}