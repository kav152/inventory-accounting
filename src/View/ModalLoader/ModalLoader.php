<?php
require_once __DIR__ . '/../../Database/DatabaseFactory.php';
require_once __DIR__ . '/../../BusinessLogic/ItemController.php';
abstract class ModalLoader
{
    //protected $controller;
    //protected $session;

    public function __construct()
    {
       // session_start();
       // error_log("ModalLoader Session ID: " . session_id());
       // error_log("Данные сессии: " . print_r($_SESSION, true));


        //DatabaseFactory::setConfig();
        //$this->controller = new ItemController();
        //$this->session = $_SESSION;
        //phpinfo();
        //session_get_cookie_params();
    }

    abstract public function load($params = []);
}