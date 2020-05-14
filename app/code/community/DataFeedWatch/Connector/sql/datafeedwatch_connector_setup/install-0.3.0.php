<?php
/* @var Mage_Core_Model_Resource_Setup $installer */
$installer  = $this;
/** @var Magento_Db_Adapter_Pdo_Mysql|Varien_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();
$table      = $installer->getTable('datafeedwatch_connector/updated_products');

if (!$installer->tableExists(($table))) {
    $table = $connection->newTable($table)
        ->addColumn('dfw_prod_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
            ), 'Product ID')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'nullable'  => true,
            ), 'Updated At')
        ->setComment('Updated Products Table');
    $connection->createTable($table);
}

$connection->dropColumn($installer->getTable('api/user'), 'dfw_connect_hash');
$connection->dropTable($installer->getTable('datafeedwatch_catalogrule_info'));
$connection->dropTable($installer->getTable('datafeedwatch_salesrule_info'));

$table = $installer->getTable('catalog/eav_attribute');

if (!$connection->tableColumnExists($table, 'can_configure_inheritance')) {
    $connection->addColumn($table,
              'can_configure_inheritance',
              array(
                  'unsigned' => true,
                  'nullable' => false,
                  'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                  'default'  => 1,
                  'comment'  => 'Can configure inheritance field? 1 - YES, 0 - NO',
              )
          );
}
if (!$connection->tableColumnExists($table, 'inheritance')) {
    $connection->addColumn($table,
            'inheritance',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Inheritance: 1 - Child, 2 - Parent, 3 - Child Then Parent',
            )
        );
}
if (!$connection->tableColumnExists($table, 'can_configure_import')) {
    $connection->addColumn($table,
            'can_configure_import',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Can configure import field? 1 - YES, 0 - NO',
            )
        );
}
if (!$connection->tableColumnExists($table, 'import_to_dfw')) {
    $connection->addColumn($table,
            'import_to_dfw',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Should import attribute? 1 - YES, 0 - NO',
            )
        );
}
/** @var string $dfwAttrInfoTable */
$oldDfwTable = $installer->getTable('datafeedwatch_catalog_attribute_info');
if ($installer->tableExists(($oldDfwTable))) {
    $dataToImport = $connection->select()->from($oldDfwTable);
    $dataToImport = $connection->query($dataToImport);
    $dataToImport = $dataToImport->fetchAll();

    if (is_array($dataToImport)) {
        foreach ($dataToImport as $oldData) {
            $connection->update($table, array(
                'can_configure_inheritance' => $oldData['can_configure_inheritance'],
                'inheritance'               => $oldData['inheritance'],
                'can_configure_import'      => $oldData['can_configure_import'],
                'import_to_dfw'             => $oldData['import_to_dfw'],
            ), sprintf('attribute_id = %s', $oldData['catalog_attribute_id']));
        }
    }
    $connection->dropTable($oldDfwTable);
}
