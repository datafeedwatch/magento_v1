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
$table = $installer->getTable('datafeedwatch_connector/catalog_attribute_info');

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

if (!$installer->tableExists(($table))) {
    $installer->startSetup();
    $salesRuleInfoTable = $connection->newTable($table)
                                     ->addColumn('catalog_attribute_info_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                                         'identity'  => true,
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                         'primary'   => true,
                                     ), 'Catalog Attribute Info ID')
                                     ->addColumn('catalog_attribute_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                     ), 'Catalog Attribute ID')
                                     ->addColumn('can_configure_inheritance', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                         'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                                         'default'   => 1,
                                     ), 'Can configure inheritance field? 1 - YES, 0 - NO')
                                     ->addColumn('inheritance', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                         'default'   => 1,
                                     ), 'Inheritance: 1 - Child, 2 - Parent, 3 - Child Then Parent')
                                     ->addColumn('can_configure_import', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                         'default'   => 1,
                                     ), 'Can configure import field? 1 - YES, 0 - NO')
                                     ->addColumn('import_to_dfw', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                                         'unsigned'  => true,
                                         'nullable'  => false,
                                         'default'   => 1,
                                     ), 'Should import attribute? 1 - YES, 0 - NO')
                                     ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
                                         'nullable'  => true,
                                     ), 'Updated At')
                                     ->setComment('Catalog Attribute Additional Info');
    $connection->createTable($salesRuleInfoTable);

    $installer->endSetup();
}