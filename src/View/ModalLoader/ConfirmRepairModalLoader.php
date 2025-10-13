<?php
// src/views/home/Modal/confirmRepair_modal_loader.php
require_once __DIR__ . '/ModalLoader.php';

class ConfirmRepairModalLoader extends ModalLoader {
    public function load($params = []) {
        //$confirmRepairItems = $this->container->getConfirmRepairItems($this->session["Status"], $this->session["IDUser"]);
        //$confirmRepairCount = count($confirmRepairItems);
        //$locationRepairs = $this->container->getLocations(true);
        
        //if ($confirmRepairCount > 0) {
            ob_start();
            include __DIR__ . '/confirmRepair_modal_content.php';
            return ob_get_clean();
        //}
        //return '';
    }
}