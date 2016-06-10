<?php

class DataFeedWatch_Connector_Model_System_Config_Source_Inheritance
{
    const CHILD_OPTION_ID                   = 1;
    const CHILD_OPTION_LABEL                = 'Child';
    const PARENT_OPTION_ID                  = 2;
    const PARENT_OPTION_LABEL               = 'Parent';
    const CHILD_THEN_PARENT_OPTION_ID       = 3;
    const CHILD_THEN_PARENT_OPTION_LABEL    = 'Child Then Parent';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::CHILD_OPTION_ID,
                'label' => $this->_helper()->__(self::CHILD_OPTION_LABEL),
                ),
            array(
                'value' => self::PARENT_OPTION_ID,
                'label' => $this->_helper()->__(self::PARENT_OPTION_LABEL),
                ),
            array(
                'value' => self::CHILD_THEN_PARENT_OPTION_ID,
                'label' => $this->_helper()->__(self::CHILD_THEN_PARENT_OPTION_LABEL),
                ),
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            self::CHILD_OPTION_ID =>
                $this->_helper()->__(self::CHILD_OPTION_LABEL),
            self::PARENT_OPTION_ID =>
                $this->_helper()->__(self::PARENT_OPTION_LABEL),
            self::CHILD_THEN_PARENT_OPTION_ID =>
                $this->_helper()->__(self::CHILD_THEN_PARENT_OPTION_LABEL),
        );
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function _helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }
}