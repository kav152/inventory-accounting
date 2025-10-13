<?php
class Container // Позволяет формировать автоматическую настройку
{
    private array $registry = [];
    public function set(string $name, Closure $value) : void
    {
        $this->registry[$name] = $value;
    }

    public function get(string $class_name): object
    {
        // Проверка значений класса и автоматическая привязка зависимостей
        if(array_key_exists($class_name, $this->registry)) 
        { 
            return $this->registry[$class_name]();
        }

        // проверка на наличие аргументов класса (конструктор)
        $reflector = new ReflectionClass($class_name);
        $constructor = $reflector->getConstructor();
        if ($constructor === null) 
        {
            return new $class_name;
        }
        
        // создаем объекты классов
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter)
        {
            $type= $parameter->getType();
            $dependencies[] = $this->get($type->getName()); // Если у объектов есть объекты с зависимостями они должны автоматически создаваться
        }      

        return new $class_name(...$dependencies);
    }
}