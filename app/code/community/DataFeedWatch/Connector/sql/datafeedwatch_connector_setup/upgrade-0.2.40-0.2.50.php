<?php
/* @var Mage_Core_Model_Resource_Setup $installer */
$installer  = $this;
/** @var Magento_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();

/** @var string $table */
$table      = $installer->getTable('datafeedwatch_connector/updated_products');

if (!$installer->tableExists(($table))) {
    $installer->startSetup();
    $updatedProductsTable = $connection->newTable($table)
                                       ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                                           'identity'  => true,
                                           'unsigned'  => true,
                                           'nullable'  => false,
                                           'primary'   => true,
                                       ), 'Product ID')
                                       ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
                                           'nullable'  => true,
                                       ), 'Updated At')
                                       ->setComment('Updated Products Table');
    $connection->createTable($updatedProductsTable);

    $installer->endSetup();
}

/** @var string $table */
$table = $installer->getTable('datafeedwatch_catalog_attribute_info');

if ($connection->tableColumnExists($installer->getTable('api/user'), 'dfw_connect_hash')) {
    $installer->startSetup();
    $connection->dropColumn($installer->getTable('api/user'), 'dfw_connect_hash');
    $installer->endSetup();
}
$installer->startSetup();
$connection->dropTable($installer->getTable('datafeedwatch_catalogrule_info'));
$installer->endSetup();
$installer->startSetup();
$connection->dropTable($installer->getTable('datafeedwatch_salesrule_info'));
$installer->endSetup();