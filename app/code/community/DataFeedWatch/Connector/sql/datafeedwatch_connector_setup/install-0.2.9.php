<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Add custom_hash for finding users
$installer->getConnection()->addColumn($installer->getTable('api/user'), 'dfw_connect_hash', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 32,
    'comment' => 'Hash used to update keys'
));

$installer->endSetup();