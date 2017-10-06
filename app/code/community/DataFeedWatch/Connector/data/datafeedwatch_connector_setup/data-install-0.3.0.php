<?php
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

$currentTimestamp = time();
$createdAt   = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp);
$scheduledAt = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp + 120);
Mage::getModel('cron/schedule')
    ->setJobCode('datafeedwatch_connector_installer')
    ->setCreatedAt($createdAt)
    ->setScheduledAt($scheduledAt)->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)->save();

$scheduledAt = strftime('%Y-%m-%d %H:%M:00', $currentTimestamp + 240);
Mage::getModel('cron/schedule')
    ->setJobCode('datafeedwatch_connector_fill_updated_at_table')
    ->setCreatedAt($createdAt)
    ->setScheduledAt($scheduledAt)->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)->save();

Mage::helper('datafeedwatch_connector')->setInstallationIncomplete();