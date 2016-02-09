<?php
class DataFeedWatch_Connector_Model_Observer extends Mage_Core_Model_Abstract {

    /* @TODO: the date should update on rule apply! */
    public function catalogruleRuleSaveAfter(Varien_Event_Observer $observer){
        $object = $observer->getEvent();

        $ruleId = $object->getRule()->getRuleId();

        $dfwCatalogRuleInfo = Mage::getModel('connector/catalogrule_info')->load($ruleId,'catalogrule_id');
        $dfwCatalogRuleInfo
            ->setCatalogruleId($ruleId)
            ->setUpdatedAt(time())
        ->save();
    }

    /* @TODO: the date should update on rule apply! */
    public function salesruleRuleSaveAfter(Varien_Event_Observer $observer){
        $object = $observer->getEvent();
        $ruleId = $object->getRule()->getRuleId();

        $dfwSalesRuleInfo = Mage::getModel('connector/salesrule_info')->load($ruleId,'salesrule_id');;
        $dfwSalesRuleInfo
            ->setSalesruleId($object->getRule()->getRuleId())
            ->setUpdatedAt(time())
            ->save();
    }

    /* after you press apply All Catalog Rules in admin */
    public function catalogruleRuleApplyAllAfter(Varien_Event_Observer $observer){
        $object = $observer->getEvent();
        $rules = array();

        //get all current rules
        $rules = Mage::getModel('catalogrule/rule')->getCollection();

        foreach($rules as $rule){
            /* @var $rule Mage_CatalogRule_Model_Rule */
            if($rule->getIsActive() && $rule->getMatchingProductIds()) {
                /* save current datetime to info for each of them */
                $dfwCatalogRuleInfo = Mage::getModel('connector/catalogrule_info')->load($rule->getRuleId(), 'catalogrule_id');
                $dfwCatalogRuleInfo
                    ->setCatalogruleId($rule->getRuleId())
                    ->setUpdatedAt(time())
                    ->save();
            }
        }


    }
}