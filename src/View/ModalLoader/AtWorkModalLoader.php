<?php
require_once __DIR__ . '/../ModalLoader/ModalLoader.php';

class AtWorkModalLoader extends ModalLoader {
    public function load($params = []) {
        //$atWorkGroups = $this->container->getAtWorkItemsGrouped($this->session["Status"], $this->session["IDUser"]);
        //$brigadesToItemsCount = count($this->container->getBrigadesToItems($this->session["Status"], $this->session["IDUser"]));
        
        //if ($brigadesToItemsCount > 0) {
            ob_start();
            include __DIR__ . '/../Modal/at_work_modal.php';
            return ob_get_clean();
       // }
        //return '';
    }
}