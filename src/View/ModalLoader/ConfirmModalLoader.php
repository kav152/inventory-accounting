<?php
// src/views/home/Modal/confirm_modal_loader.php
require_once __DIR__ . '/ModalLoader.php';

class ConfirmModalLoader extends ModalLoader {
    public function load($params = []) {
        //$confirmItems = $this->container->getConfirmItems($this->session["Status"], $this->session["IDUser"]);
       // $confirmCount = count($confirmItems);
        
      //  if ($confirmCount > 0) {
            ob_start();
            include __DIR__ . '/confirm_modal_content.php';
            return ob_get_clean();
    //    }
    //    return '';
    }
}