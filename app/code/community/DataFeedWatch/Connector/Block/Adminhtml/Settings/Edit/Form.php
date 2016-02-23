<?php

class DataFeedWatch_Connector_Block_Adminhtml_Settings_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{

    protected function _prepareForm()
    {

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'method' => 'post'
        ));

        $form->setUseContainer(true);

        $this->setForm($form);

        $fieldset = $form->addFieldset('attributes', array(
            'legend' =>Mage::helper('connector')->__('Select Attributes')
        ));

        $fieldset->addType('connector_attributes', 'DataFeedWatch_Connector_Block_Adminhtml_Renderer_Attribute');

        $data = array();

        $required = Mage::helper('connector/attribute')->getRequiredAttributes();
        $additional = array();
        if(Mage::getStoreConfig('datafeedwatch/settings/attributes')){
            $additional = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attributes'));
        }
        $data["required_attributes"] = array_keys($required);
        $data["additional_attributes"] = $additional;

        $collection = Mage::getModel('catalog/product')->getCollection();
        if($collection){
            $product = $collection->getFirstItem();
            $product = Mage::getModel('catalog/product')->load($product->getId());
        }
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $urlComment = '<br/><table>
        <tr><td>Sample Product URL:</td><td>'.$baseUrl . $product->getUrlPath().'</td></tr>
        <tr><td>Sample Full URL:</td><td>'.Mage::helper('connector/attribute_url')->getFullUrl($product).'</td></tr></table>
        <div class="logic_introduction">
Below are the default settings for the fields and values that are downloaded by DataFeedWatch.<br/>
The values that are downloaded for ‘variants’ (child products) can be taken from the ‘configurable product’ (parent)
or from the ’simple product’ (child) itself.
The default settings are optimal in most of the cases and we would advise you to adjust them to your own needs only if necessary.<br/>
When in doubt: contact <a href="mailto:support@datafeedwatch.com">support@datafeedwatch.com</a> :-)
        </div>
        <ul class="checkboxes">
        <li class="connector_checkboxes hints">
                <label>Download from</label>

                <div class="logic">
                  <span class="help">
                  Child
                  <div class="help-tooltip">
                  The value for that field is taken from the simple (child) product itself
                  </div>
                  </span>
                  <span class="help">Parent
                  <div class="help-tooltip">
                  The value for that field is taken (inherited) from the configurable (parent) product
                  </div></span>
                  <span class="help">Child then Parent
                  <div class="help-tooltip">
                  We will first attempt to take the value from the simple (child) product, but if no value exists, the value from the configurable (parent) product is taken<br/>
                  NOTE: the only exception is is_in_stock where it means: if parent has 0 and child is N(ot)V(isible)I(ndividually), then child has 0. If parent has 1, child has its own value
                  </div>
                  </span>
                </div></li>
        </ul>';

        $fieldset->addField('debug','select', array(
            'label' => Mage::helper('connector')->__('Debug'),
            'name' => 'debug',
            'values' => array(
                '1' => 'Yes',
                '0' => 'No'),
            'after_element_html' => 'info on why products are not returned will be written to var/log/dfw_skipped_skus.log'
        ));
        $data["debug"] = Mage::getStoreConfig('datafeedwatch/settings/debug');

        $fieldset->addField('url_type','select', array(
            'label' => Mage::helper('connector')->__('URL Type'),
            'name' => 'url_type',
            'values' => array(
                '1' => 'Product URL',
                '2' => 'Full URL'),
            'after_element_html' => $urlComment
        ));
        $data["url_type"] = Mage::getStoreConfig('datafeedwatch/settings/url_type');



        $fieldset->addField('required_attributes', 'connector_attributes',array(
            "label" => Mage::helper('connector')->__("Required Attributes"),
            "required" => false,
            "options" => $required,
            "name" => "required_attributes[]",
            "disabled" => array_keys($required),
            "ignore" => true,
            'style' => 'display:none'
        ));

        $attributesList = array_merge(Mage::helper('connector/attribute')->getAttributesList());

        /*get radios about logic */
        /*$field = $fieldset->addField('additional_attributes', 'checkboxes',array(
            "label" => Mage::helper('connector')->__("Optional Attributes"),
            "required" => false,
            "options" => $attributesList,
            "name" => "additional_attributes[]",
        ));*/

        $fieldset->addField('additional_attributes','connector_attributes',array(
            'label' => Mage::helper('connector')->__("Additional Attributes"),
            "name" => "additional_attributes[]",
            "options" => $attributesList,
            "class" =>'',
            'include_selectall' => true
        ));

        $form->setValues($data);

        return parent::_prepareForm();
    }
}