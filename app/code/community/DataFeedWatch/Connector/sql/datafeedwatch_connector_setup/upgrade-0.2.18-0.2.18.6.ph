<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

// Add custom_hash for finding users
$catalogRuleInfoTable = $installer->getConnection()->newTable($installer->getTable('connector/catalogrule_info'))
    ->addColumn('catalogruleinfo_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'CatalogRuleInfo ID')
    ->addColumn('catalogrule_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Catalog Rule ID')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        array(
            'nullable'  => true
        ), 'Updated At')
    ->addIndex($installer->getIdxName('connector/catalogrule_info', array('catalogrule_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('catalogrule_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex(
        $installer->getIdxName('connector/catalogrule_info', array('updated_at')),
        array('updated_at')
    )
    ->addForeignKey(
        $installer->getFkName('connector/catalogrule_info', 'role_id', 'catalogrule/rule', 'rule_id'),
        'catalogrule_id',
        Mage::getSingleton('core/resource')->getTableName('catalogrule/rule'),
        'rule_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('CatalogRule Info Table');
$installer->getConnection()->createTable($catalogRuleInfoTable);


$salesRuleInfoTable = $installer->getConnection()->newTable($installer->getTable('connector/salesrule_info'))
    ->addColumn('salesruleinfo_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'SalesRuleInfo ID')
    ->addColumn('salesrule_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Sales Rule ID')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        array(
            'nullable'  => true
        ), 'Updated At')
    ->addIndex($installer->getIdxName('connector/salesrule_info', array('rule_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('salesrule_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('admin/role', array('updated_at')),
        array('updated_at'))
    ->addForeignKey(
        $installer->getFkName('connector/salesrule_info', 'salesrule_id', 'salesrule/rule', 'rule_id'),
        'salesrule_id',
        Mage::getSingleton('core/resource')->getTableName('salesrule/rule'),
        'rule_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('SalesRule Info Table');
$installer->getConnection()->createTable($salesRuleInfoTable);

$installer->endSetup();