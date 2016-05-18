<?php

class DataFeedWatch_Connector_Adminhtml_DatafeedwatchController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return Mage_Core_Controller_Response_Http
     */
    public function openAction()
    {
        $apiUser = $this->getApiUserModel();
        $apiUser->loadDfwUser();
        /** @var DataFeedWatch_Connector_Helper_Data $helper */
        $helper = Mage::helper('datafeedwatch_connector');
        if (!$apiUser->isObjectNew()) {
            return $this->getResponse()->setRedirect($helper->getDataFeedWatchUrl());
        }

        $apiUser->createDfwUser();

        return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
    }

    /**
     * @return Mage_Core_Controller_Response_Http
     */
    public function addStoreAction()
    {
        $apiUser = $this->getApiUserModel();
        $apiUser->loadDfwUser();
        $apiUser->createDfwUser();

        return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function extortAction()
    {
        Mage::helper('datafeedwatch_connector')->updateLastInheritanceUpdateDate();

        return $this->_redirectReferer();
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function refreshAction()
    {
        $apiUser = $this->getApiUserModel();
        $apiUser->loadDfwUser();
        $apiUser->createDfwUser();

        $this->_getSession()
            ->addSuccess($this->__('%s user has been refreshed.', DataFeedWatch_Connector_Model_Api_User::USER_NAME));

        return $this->_redirectReferer();
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function restoreOriginalAttributeConfigAction()
    {
        Mage::helper('datafeedwatch_connector')->restoreOriginalAttributesConfig();

        $this->_getSession()
            ->addSuccess($this->__('Original inheritance configuration has been restored.'));

        return $this->_redirectReferer();
    }

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function renderInheritanceGridAction()
    {
        $page   = $this->getRequest()->getParam('page');
        $limit  = $this->getRequest()->getParam('limit');

        $this->loadLayout();

        $grid = $this->getLayout()
            ->getBlock('datafeedwatch_connector_inheritance_grid_items')
            ->setPage($page)
            ->setLimit($limit)
            ->toHtml();

        return $this->getResponse()->setBody($grid);
    }

    public function saveAttributeInheritanceAction()
    {
        $attributeId    = $this->getRequest()->getParam('attribute_id');
        $value          = $this->getRequest()->getParam('value');

        $attribute = Mage::getModel('datafeedwatch_connector/catalog_attribute_info')
            ->loadByAttributeId($attributeId);
        $attribute->setInheritance($value)->save();
    }

    public function saveAttributeImportAction()
    {
        $attributeId    = $this->getRequest()->getParam('attribute_id');
        $value          = $this->getRequest()->getParam('value');

        $attribute = Mage::getModel('datafeedwatch_connector/catalog_attribute_info')
            ->loadByAttributeId($attributeId);
        $attribute->setImportToDfw($value)->save();

    }

    /**
     * @return DataFeedWatch_Connector_Model_Api_User
     */
    protected function getApiUserModel()
    {
        return Mage::getModel('datafeedwatch_connector/api_user');
    }
}