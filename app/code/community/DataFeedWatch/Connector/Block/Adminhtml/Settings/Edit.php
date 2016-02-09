<?php

class DataFeedWatch_Connector_Block_Adminhtml_Settings_Edit extends Mage_Adminhtml_Block_Widget_Form_Container{

    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'connector';
        $this->_controller = 'adminhtml_settings';
        $this->_mode = 'edit';

        $user = Mage::getModel('api/user')->load('datafeedwatch','username');

        if ($user->isObjectNew()) {
            $linkUrl = DataFeedWatch_Connector_Block_Adminhtml_Connectorbackend::getInstance()->getCreateUserUrl();
        } else {
            $linkUrl = DataFeedWatch_Connector_Block_Adminhtml_Connectorbackend::getInstance()->getRedirectUrl();
        }

        $this->removeButton('reset');
        $this->removeButton('back');

        $this->addButton('goToMyDataFeedWatch', array(
            'label' => $this->__('Go to my DataFeedWatch'),
            'onclick' => "setLocation('$linkUrl')",
        ));
    }
    public function getHeaderText()
    {
        return Mage::helper('connector')->__('DataFeedWatch Settings');
    }

}