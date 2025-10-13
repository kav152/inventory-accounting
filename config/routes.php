<?php
//declare(strict_types=1);
class Routes
{   
    private $routes = [];

    public function __construct()
    {
        $this->add('/login', function(){
            //echo "Это страница проверки пароля";
            require APP_PATH.'/src/View/login.php';            
            //require __DIR__ .'/../src/view/login.php';
        });
        
        $this->add('/home', function(){
            require APP_PATH.'/src/View/home.php';
        });

        $this->add('/get-card-item', function() {
            // Получаем ID из POST-запроса
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo "Ошибка: ID не указан";
                return;
            }
            
            // Передаем ID в представление
            require APP_PATH . '/src/View/cardItem.php';
        });
        
        $this->add('/orederCard/{id}', function($id){
            echo "Это окно Заказа, его индификатор {$id}";
        });
        
        $this->add("/orederCard/{id}/user/{user_id}", function($id, $user_id){
            echo "Это окно Заказа, его индификатор {$id} и ИД пользователя {$user_id}";
        });
        
    }

    public function add(string $path, Closure $handler): void
    {
        $this->routes[$path] = $handler;
    }

    public function dispatch(string $path): void
    {
        foreach ($this->routes as $route => $handler) 
        {        
            // Разбиваем адрес тсроки на части 0 - полная совпадение, 1 - часто которая совпала (например ID)
            $pattern = preg_replace("#\{\w+\}#","([^\/]+)", $route);
            if(preg_match("#^$pattern$#", $path, $matches))
            {                
                // удаление 1 элемента масива
                array_shift($matches);
                //print_r($matches);
                //echo '-----';
                //print_r($handler);
                //echo '-----';
                //print_r($this->routes);
                call_user_func_array($handler, $matches);
                //print_r($matches);
                return;
            }
        }

        echo 'Старница не найдена';
    }
}