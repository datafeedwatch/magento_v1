<?php

class DataFeedWatch_Connector_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    const MY_DATA_FEED_WATCH_URL                = 'https://my.datafeedwatch.com/';
    const LOG_DIR_NAME                          = 'DataFeedWatch_Connector';
    const API_LOG_DIR_NAME                      = 'api';
    const SQL_LOG_DIR_NAME                      = 'sql';
    const CRON_LOG_DIR_NAME                     = 'cron';
    const DEBUG_XPATH                           = 'datafeedwatch_connector/general/debug';
    const PRODUCT_URL_CUSTOM_INHERITANCE_XPATH  = 'datafeedwatch_connector/custom_inheritance/product_url';
    const IMAGE_URL_CUSTOM_INHERITANCE_XPATH    = 'datafeedwatch_connector/custom_inheritance/image_url';
    const LAST_CATALOGRULE_PRICE_ID_XPATH       = 'datafeedwatch_connector/custom_inheritance/last_catalogrule_price_id';
    const LAST_INHERITANCE_UPDATE_XPATH         = 'datafeedwatch_connector/custom_inheritance/last_inheritance_update';

    /**
     * @return bool
     */
    public function isDebugModeEnabled()
    {
        return Mage::getStoreConfigFlag(self::DEBUG_XPATH);
    }

    /**
     * @return bool
     */
    public function isProductUrlInherited()
    {
        return Mage::getStoreConfigFlag(self::PRODUCT_URL_CUSTOM_INHERITANCE_XPATH);
    }

    /**
     * @return bool
     */
    public function isImageUrlInherited()
    {
        return Mage::getStoreConfigFlag(self::IMAGE_URL_CUSTOM_INHERITANCE_XPATH);
    }

    /**
     * @return string
     */
    public function getLastCatalogRulePriceId()
    {
        return Mage::getStoreConfig(self::LAST_CATALOGRULE_PRICE_ID_XPATH);
    }

    /**
     * @param string|int $id
     */
    public function setLastCatalogRulePriceId($id)
    {
        Mage::getModel('core/config')->saveConfig(self::LAST_CATALOGRULE_PRICE_ID_XPATH, $id);
    }

    /**
     * @return string
     */
    public function getLastInheritanceUpdateDate()
    {
        return Mage::getStoreConfig(self::LAST_INHERITANCE_UPDATE_XPATH);
    }

    /**
     * @param string $date
     */
    public function setLastInheritanceUpdateDate($date)
    {
        Mage::getModel('core/config')->saveConfig(self::LAST_INHERITANCE_UPDATE_XPATH, $date)->reinit();
    }

    public function updateLastInheritanceUpdateDate()
    {
        $this->setLastInheritanceUpdateDate(date('Y-m-d H:i:s'));
    }

    /**
     * @param mixed $message
     */
    public function log($message)
    {
        $this->logToFile($message, self::API_LOG_DIR_NAME);
    }

    /**
     * @param mixed $message
     */
    public function sqlLog($message)
    {
        $this->logToFile($message, self::SQL_LOG_DIR_NAME);
    }

    /**
     * @param mixed $message
     */
    public function cronLog($message)
    {
        $this->logToFile($message, self::CRON_LOG_DIR_NAME);
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function logToFile($message, $type)
    {
        if ($this->isDebugModeEnabled()) {
            $this->createLogFileDir($type);
            $fileName = $this->getLogFilePath($type);
            Mage::log($message, null, $fileName, true);
        }
    }

    /**
     * @param string $type
     * @return string
     */
    public function getLogFilePath($type)
    {
        return self::LOG_DIR_NAME. DS . $type . DS . sprintf('%s.log', date('Y-m-d'));
    }

    /**
     * @param string $type
     */
    public function createLogFileDir($type) {
        $dir = Mage::getBaseDir('var') . DS . 'log' . DS . self::LOG_DIR_NAME . DS . $type . DS;
        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0775, true);
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }
    }

    /**
     * @return string
     */
    public function getDataFeedWatchUrl()
    {
        return self::MY_DATA_FEED_WATCH_URL;
    }

    public function restoreOriginalAttributesConfig()
    {
        $cannotConfigureImportField = array(
            'name',
            'description',
            'short_description',
            'tax_class_id',
            'visibility',
            'status',
            'meta_title',
            'meta_keyword',
            'meta_description',
            'media_gallery',
            'image',
            'small_image',
            'thumbnail',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'sku',
            'updated_at',
            'ignore_datafeedwatch',
            'dfw_parent_ids',
        );

        $cannotConfigureInheritanceField = array(
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'media_gallery',
            'image',
            'small_image',
            'thumbnail',
            'updated_at',
            'ignore_datafeedwatch',
            'dfw_parent_ids',
        );

        $enableImport = array(
            'name',
            'description',
            'short_description',
            'tax_class_id',
            'visibility',
            'status',
            'meta_title',
            'meta_keyword',
            'meta_description',
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'updated_at',
        );

        $inheritanceData = array(
            'updated_at'            => DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::PARENT_OPTION_ID,
            'ignore_datafeedwatch'  => DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID,
            'dfw_parent_ids'        => DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID,
        );

        $catalogAttributes = Mage::getResourceModel('catalog/product_attribute_collection');
        $catalogAttributes->setDataToAll('can_configure_inheritance', null);
        $catalogAttributes->setDataToAll('inheritance', null);
        $catalogAttributes->setDataToAll('can_configure_import', null);
        $catalogAttributes->setDataToAll('import_to_dfw', null);
        $catalogAttributes->save();

        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addVisibleFilter();
        foreach ($attributes as $attribute) {
            $attributeCode  = $attribute->getAttributeCode();
            $inheritance    = DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_OPTION_ID;
            if (array_key_exists($attributeCode, $inheritanceData)) {
                $inheritance = $inheritanceData[$attributeCode];
            }
            $attribute->setImportToDfw(in_array($attributeCode, $enableImport))
                ->setCanConfigureImport(!in_array($attributeCode, $cannotConfigureImportField))
                ->setCanConfigureInheritance(!in_array($attributeCode, $cannotConfigureInheritanceField))
                ->setInheritance($inheritance)
                ->save();
        }

        Mage::getModel('core/config')->saveConfig('datafeedwatch_connector/custom_inheritance/product_url', 1);
        Mage::getModel('core/config')->saveConfig('datafeedwatch_connector/custom_inheritance/image_url', 0);
    }
}