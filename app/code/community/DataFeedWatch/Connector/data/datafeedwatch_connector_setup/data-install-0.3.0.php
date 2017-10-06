<?php
/** @var DataFeedWatch_Connector_Model_Api_User $apiUser */
$apiUser = Mage::getModel('datafeedwatch_connector/api_user');
$apiUser->loadDfwUser();
$apiUser->createDfwUser();

/** @var Mage_Catalog_Model_Resource_Setup $attributeInstaller */
$attributeInstaller = Mage::getResourceModel('catalog/setup','catalog_setup');
$attributeId = $attributeInstaller->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'dfw_parent_ids');
if (empty($attributeId)) {
    $attributeInstaller->addAttribute(
        Mage_Catalog_Model_Product::ENTITY,
        'dfw_parent_ids',
        array(
            'type'                          => 'varchar',
            'backend'                       => '',
            'frontend'                      => '',
            'input'                         => 'text',
            'class'                         => '',
            'global'                        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'visible'                       => true,
            'required'                      => false,
            'user_defined'                  => true,
            'default'                       => '',
            'searchable'                    => false,
            'filterable'                    => false,
            'comparable'                    => false,
            'visible_on_front'              => false,
            'unique'                        => false,
            'group'                         => 'General',
            'can_configure_inheritance'     => 0,
            'inheritance'                   => DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID,
            'can_configure_import'          => 0,
            'import_to_dfw'                 => 0,
        ));
}

$attributeCode = 'ignore_datafeedwatch';
if (!$attributeInstaller->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode)) {
    $attributeInstaller->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode, array(
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
        'used_in_product_listing'   => false,
        'can_configure_inheritance' => 0,
        'inheritance'               => DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID,
        'can_configure_import'      => 0,
        'import_to_dfw'             => 0,
    ));
}

Mage::helper('datafeedwatch_connector')->restoreOriginalAttributesConfig();

/** @var DataFeedWatch_Connector_Model_Cron $cron */
$cron = Mage::getModel('datafeedwatch_connector/cron');

$cron->reindex();