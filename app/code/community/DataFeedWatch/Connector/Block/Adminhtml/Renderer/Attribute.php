<?php
class DataFeedWatch_Connector_Block_Adminhtml_Renderer_Attribute extends Varien_Data_Form_Element_Checkboxes
{
    protected $_element;

    /*
     * You can do all necessary customisations here
     *
     * You can use parent::getElementHtml() to get original markup
     * if you are basing on some other type and if it is required
     *
     * Use $this->getData('desired_data_key') to extract the desired data
     * E.g. $this->getValue() or $this->getData('value') will return form elements value
     */

    public function getElementHtml()
    {
        $attributes = '<ul class="checkboxes">';

        if($this->getData('include_selectall')){
            $attributes .= '<li>
            <input id="additional_attributes_0_all"
            type="checkbox"
            name="additional_attributes[]"
            value="0_all">
            <label for="additional_attributes_0_all">Select/Unselect all</label>
            </li>';
        }

        $values = $this->_prepareValues();

        $attributeLogic = unserialize(Mage::getStoreConfig('datafeedwatch/settings/attribute_logic'));

        foreach ($values as $value) {

            $attributeCode = $value['label'];
            $childSelected  = ($attributeLogic[$attributeCode]=='child') ? 'checked' : '';
            $parentSelected = ($attributeLogic[$attributeCode]=='parent') ? 'checked' : '';
            $childThenParentSelected = ($attributeLogic[$attributeCode]=='child_then_parent' || !$attributeLogic[$attributeCode]) ? 'checked' : '';

            if(in_array($attributeCode,array('parent_id','parent_sku','parent_url'))){
                $childSelected = $childThenParentSelected = '';
                $parentSelected = 'selected';
            }

            $attributes.= '<li class="connector_checkboxes">
                '.$this->_optionToHtml($value).'
                <div class="logic">
                  <input class="child" type="radio" '. $childSelected .' name="attribute_logic['.$attributeCode.']" value="child">
                  <input class="parent"  type="radio" '. $parentSelected .' name="attribute_logic['.$attributeCode.']" value="parent">
                  <input class="child_then_parent"  type="radio" '. $childThenParentSelected .' name="attribute_logic['.$attributeCode.']" value="child_then_parent">
                </div></li>';
        }

    $attributes .= '</ul>';

        return $attributes;
    }

    protected function _optionToHtml($option)
    {
        $id = $this->getHtmlId().'_'.$this->_escape($option['value']);

        $html = '<input id="'.$id.'"';
        foreach ($this->getHtmlAttributes() as $attribute) {
            if ($value = $this->getDataUsingMethod($attribute, $option['value'])) {
                $html .= ' '.$attribute.'="'.$value.'"';
            }
        }
        $html .= ' value="'.$option['value'].'" />'
            . ' <label for="'.$id.'">' . $option['label'] . '</label>'
            . "\n";
        return $html;
    }
 }