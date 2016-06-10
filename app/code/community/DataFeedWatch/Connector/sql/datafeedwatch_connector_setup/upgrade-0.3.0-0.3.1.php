<?php
/* @var Mage_Core_Model_Resource_Setup $installer */
$installer  = $this;
/** @var Magento_Db_Adapter_Pdo_Mysql|Varien_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();
/** @var string $table */
$table          = $installer->getTable('datafeedwatch_catalog_attribute_info');
/** @var string $catalogTable */
$catalogTable   = $installer->getTable('catalog/eav_attribute');

if (!$connection->tableColumnExists($catalogTable, 'can_configure_inheritance')) {
    $installer->startSetup();
    $connection->addColumn($catalogTable,
              'can_configure_inheritance',
              array(
                  'unsigned' => true,
                  'nullable' => false,
                  'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                  'default'  => 1,
                  'comment'  => 'Can configure inheritance field? 1 - YES, 0 - NO',
              )
          );
    $installer->endSetup();
}
if (!$connection->tableColumnExists($catalogTable, 'inheritance')) {
    $installer->startSetup();
    $connection->addColumn($catalogTable,
            'inheritance',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Inheritance: 1 - Child, 2 - Parent, 3 - Child Then Parent',
            )
        );
    $installer->endSetup();
}
if (!$connection->tableColumnExists($catalogTable, 'can_configure_import')) {
    $installer->startSetup();
    $connection->addColumn($catalogTable,
            'can_configure_import',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Can configure import field? 1 - YES, 0 - NO',
            )
        );
    $installer->endSetup();
}
if (!$connection->tableColumnExists($catalogTable, 'import_to_dfw')) {
    $installer->startSetup();
    $connection->addColumn($catalogTable,
            'import_to_dfw',
            array(
                'unsigned'  => true,
                'nullable'  => false,
                'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'default'   => 1,
                'comment'   => 'Should import attribute? 1 - YES, 0 - NO',
            )
        );
    $installer->endSetup();
}

if ($installer->tableExists(($table))) {
    $installer->startSetup();
    $dataToImport = $connection->select()->from($table);
    $dataToImport = $connection->query($dataToImport);
    $dataToImport = $dataToImport->fetchAll();

    if (is_array($dataToImport)) {
        foreach ($dataToImport as $oldData) {
            $connection->update($catalogTable, array(
                'can_configure_inheritance' => $oldData['can_configure_inheritance'],
                'inheritance'               => $oldData['inheritance'],
                'can_configure_import'      => $oldData['can_configure_import'],
                'import_to_dfw'             => $oldData['import_to_dfw'],
            ), sprintf('attribute_id = %s', $oldData['catalog_attribute_id']));
        }
    }
    $connection->dropTable($table);
    $installer->endSetup();
}

/** @var string $table */
$table          = $installer->getTable('datafeedwatch_connector/updated_products');
if ($connection->tableColumnExists($table, 'product_id')) {
    $connection->changeColumn($table, 'product_id', 'dfw_prod_id', array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    ));
}

/** @var Mage_Catalog_Model_Resource_Setup $attributeInstaller */
$attributeInstaller = Mage::getResourceModel('catalog/setup','catalog_setup');
$attributeId = $attributeInstaller->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'dfw_parent_ids', 'attribute_id');
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