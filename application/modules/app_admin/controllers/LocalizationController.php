<?php

require_once 'Zend/Acl.php';
require_once 'models/app_admin/AdminUserDBAccess.php';
require_once 'models/app_admin/DBWebservices.php';
require_once 'models/app_admin/AdminUserAuth.php';
require_once 'models/app_admin/AdminCustomerDBAccess.php';
require_once 'clients/app_admin/AdminPage.php';
require_once 'models/app_admin/AdminUserAuth.php';

class LocalizationController extends Zend_Controller_Action {

    public function init() {

        if (!AdminUserAuth::identify()) {

            $this->_request->setControllerName('error');
            $this->_request->setActionName('expired');
            $this->_request->setDispatched(false);
        }

        $this->SERVICES_ACCESS = new DBWebservices();

        $this->REQUEST = $this->getRequest();

        $this->PAGE = new AdminPage();
    }

    public function indexAction() {

        $USER_PERMISSIONS = AdminUserAuth::getACLValue();

        $this->userSettings = isset($USER_PERMISSIONS["SETTINGS"]) ? $USER_PERMISSIONS["SETTINGS"] : false;
    }
    
    public function listAction() {

        AdminUserAuth::getACLValue();
        
    }

    public function jsonloadAction() {

        switch ($this->REQUEST->getPost('cmd')) {

            case "jsonAllLocalizations":
                $jsondata = $this->SERVICES_ACCESS->jsonAllLocalizations($this->REQUEST->getPost());
                break;
        }

        if (isset($jsondata))
            $this->setJSON($jsondata);
    }

    public function jsonsaveAction() {

        switch ($this->REQUEST->getPost('cmd')) {
            case "jsonActionLocalization":
                $jsondata = DBWebservices::jsonActionLocalization($this->REQUEST->getPost());
                break;
            case "jsonDeleteLocalization":
                $jsondata = DBWebservices::jsonDeleteLocalization($this->REQUEST->getPost());
                break;
        }

        if (isset($jsondata))
            $this->setJSON($jsondata);
    }

    public function jsontreeAction() {

        switch ($this->REQUEST->getPost('cmd')) {

            case "jsonTreeAllLocal":
                //$jsondata = $this->DB_LOCAL->jsonTreeAllLocal($this->REQUEST->getPost());

                break;
        }

        if (isset($jsondata))
            $this->setJSON($jsondata);
    }

    public function setJSON($jsondata) {

        Zend_Loader::loadClass('Zend_Json');

        $json = Zend_Json::encode($jsondata);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>