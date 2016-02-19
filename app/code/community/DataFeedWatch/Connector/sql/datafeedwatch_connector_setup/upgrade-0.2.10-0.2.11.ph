<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Add custom_hash for finding users
$installer->getConnection()->dropColumn($installer->getTable('api/user'), 'dfw_connect_hash');

$installer->endSetup();