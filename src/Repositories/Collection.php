<?php
class Collection implements \IteratorAggregate, \Countable
{
    /** @var array */
    private array $items = [];
    
    /** @var string */
    private string $type;

    public function __construct(string $type, array $items = [])
    {
        $this->type = $type;
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add($item): void
    {
        if (!$item instanceof $this->type) {
            throw new \InvalidArgumentException("Элемент должен быть типа {$this->type}");
        }
        $this->items[] = $item;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Универсальный поиск по свойству
     * @param string $property Название свойства (например, "IDUser")
     * @param mixed $value Значение для поиска
     * @return object|null
     */
    public function findBy(string $property, $value): ?object
    {
        foreach ($this->items as $item) {
            if (property_exists($item, $property) && $item->{$property} === $value) {
                return $item;
            }
        }
        return null;
    }
    public function first(): ?object 
    {
        return $this->items[0] ?? null;
    }

    public function last(): ?object 
    {
        if (count($this->items) === 0) {
            return null;
        }
        return $this->items[count($this->items) - 1];
    }
    public function indexOf(int $index): ?object 
    {
         return $this->items[$index] ?? null;
    }
}