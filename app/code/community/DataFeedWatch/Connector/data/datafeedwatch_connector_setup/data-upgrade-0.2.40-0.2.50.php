<?php
/** @var DataFeedWatch_Connector_Model_Api_User $apiUser */
$apiUser = Mage::getModel('datafeedwatch_connector/api_user');
$apiUser->loadDfwUser();
$apiUser->createDfwUser();


$installer = Mage::getResourceModel('catalog/setup', 'default_setup');

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'ignore_datafeedwatch', array(
    'group'                     => 'General',
    'input'                     => 'select',
    'type'                      => 'int',
    'label'                     => 'Ignore In DataFeedWatch',
    'source'                    => 'eav/entity_attribute_source_boolean',
    'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                   => true,
    'required'                  => false,
    'visible_on_front'          => false,
    'is_html_allowed_on_front'  => false,
    'is_configurable'           => false,
    'searchable'                => false,
    'filterable'                => false,
    'comparable'                => false,
    'unique'                    => false,
    'user_defined'              => true,
    'default'                   => '0',
    'is_user_defined'           => false,
    'used_in_product_listing'   => false
));
$attribute = Mage::getModel('eav/entity_attribute')->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'ignore_datafeedwatch');

$attribute->setImportToDfw(0)
    ->setCanConfigureImport(0)
    ->setCanConfigureInheritance(0)
    ->setInheritance(DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID)
    ->save();


Mage::helper('datafeedwatch_connector')->restoreOriginalAttributesConfig();


/** @var DataFeedWatch_Connector_Model_Cron $cron */
$cron = Mage::getModel('datafeedwatch_connector/cron');

$cron->reindex();