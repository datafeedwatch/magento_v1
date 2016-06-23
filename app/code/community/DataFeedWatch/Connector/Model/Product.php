<?php
/**
 * @method DataFeedWatch_Connector_Model_Product getParent()
 */

class DataFeedWatch_Connector_Model_Product
    extends Mage_Catalog_Model_Product
{
    /** @var array $importData */
    protected $importData = array();

    protected function _construct()
    {
        $this->_init('datafeedwatch_connector/product');
    }

    /**
     * @return array
     */
    public function getDataToImport()
    {
        $parent = $this->getParent();
        if ($this->registryHelper()->isStatusAttributeInheritable()) {
            $this->setStatus($this->getFilterStatus());
        }
        $date = $this->getRuleDate();
        $date = new DateTime($date);
        $this->setUpdatedAt($date->format('Y-m-d H:i:s'));
        $this->fillAllAttributesData();
        $this->importData['product_id']                 = $this->getId();
        $this->importData['sku']                        = $this->getSku();
        $this->importData['product_type']               = $this->getTypeId();
        $this->importData['quantity']                   = (int) $this->getQty();
        $this->importData['currency_code']              = $this->getStore()->getCurrentCurrencyCode();
        $this->importData['price']                      = $this->getImportPrice(false);
        $this->importData['price_with_tax']             = $this->getImportPrice(true);
        $this->importData['special_price']              = $this->getImportSpecialPrice(false);
        $this->importData['special_price_with_tax']     = $this->getImportSpecialPrice(true);
        $this->importData['special_from_date']          = $this->getSpecialFromDate();
        $this->importData['special_to_date']            = $this->getSpecialToDate();
        $this->importData['image_url']                  = $this->getBaseImageUrl();
        $this->importData['product_url']                = $this->getProductUrl();
        $this->importData['product_url_rewritten']      = $this->getProductUrlRewritten();
        $this->importData['is_in_stock']                = $this->getIsInStock();
        $this->getCategoryPathToImport();
        $this->setDataToImport($this->getCategoriesNameToImport(false));

        if (!empty($parent)) {
            $this->importData['parent_id']                      = $parent->getId();
            $this->importData['parent_sku']                     = $parent->getSku();
            $this->importData['parent_price']                   = $parent->getImportPrice(false);
            $this->importData['parent_price_with_tax']          = $parent->getImportPrice(true);
            $this->importData['parent_special_price']           = $parent->getImportSpecialPrice(false);
            $this->importData['parent_special_price_with_tax']  = $parent->getImportSpecialPrice(true);
            $this->importData['parent_special_from_date']       = $parent->getSpecialFromDate();
            $this->importData['parent_special_to_date']         = $parent->getSpecialToDate();
            $this->importData['parent_url']                     = $parent->getProductUrl();

            if ($this->helper()->isProductUrlInherited()) {
                $this->importData['product_url'] = $this->importData['parent_url'];
            }

            $this->setDataToImport($parent->getCategoriesNameToImport(true));
            if ($parent->isConfigurable()) {
                $this->importData['variant_spac_price']             = $parent->getVariantSpacPrice($this, false);
                $this->importData['variant_spac_price_with_tax']    = $parent->getVariantSpacPrice($this, true);
                $this->importData['variant_name']                   = $this->getName();
                $this->getDfwDefaultVariant();
            }
        }

        $this->getExcludedImages();
        $this->setDataToImport($this->getAdditionalImages($this->importData['image_url'], false));
        if (!empty($parent)) {
            if ($this->helper()->isImageUrlInherited()) {
                $this->importData['image_url'] = $parent->getBaseImageUrl();
            }
            $this->setDataToImport($parent->getAdditionalImages($this->importData['image_url'], true));
        }

        return $this->importData;
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return Varien_Object
     */
    public function setOrigData($key = null, $data = null)
    {
        if (is_null($key)) {
            $this->_origData = $this->_data;
        } else {
            $this->_origData[$key] = $data;
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function fillAllAttributesData()
    {
        $productAttributes = array_keys($this->getAttributes());
        $attributeCollection = Mage::registry(DataFeedWatch_Connector_Helper_Registry::ALL_IMPORTABLE_ATTRIBUTES_KEY);
        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (empty($attributeCode) || !in_array($attributeCode, $productAttributes)) {
                continue;
            }
            $value = $attribute->getFrontend()->getValue($this);
            if ($attribute->getBackendType() === 'int' && $value === 'N/A') {
                $value = '';
            }
            $this->importData[$attributeCode] = $value;
        }

        return $this;
    }

    /**
     * @param array $data
     */
    protected function setDataToImport($data)
    {
        foreach ($data as $key => $value) {
            $this->importData[$key] = $value;
        }
    }

    /**
     * @param bool $withTax
     * @return float
     */
    protected function getImportPrice($withTax = false)
    {
        $price = $this->getStore()->roundPrice($this->getStore()->convertPrice($this->getFinalPrice()));
        return $this->getTaxHelper()->getPrice($this, $price, $withTax);
    }

    /**
     * @param bool $withTax
     * @return float
     */
    protected function getImportSpecialPrice($withTax = false)
    {
        return $this->getTaxHelper()->getPrice($this, $this->getSpecialPrice(), $withTax);
    }

    /**
     * @param DataFeedWatch_Connector_Model_Product $child
     * @param bool $withTax
     * @return float
     */
    protected function getVariantSpacPrice($child, $withTax = false)
    {
        if (!$this->isConfigurable()) {
            return null;
        }
        $attributes = $this->getTypeInstance(true)->getConfigurableAttributes($this);
        $pricesByAttributeValues = array();
        $basePrice = $this->getFinalPrice();
        foreach ($attributes as $attribute) {
            $prices = $attribute->getPrices();
            foreach ($prices as $price) {
                if ($price['is_percent']) {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                } else {
                    $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                }
            }
        }
        $totalPrice = $basePrice;
        foreach ($attributes as $attribute) {
            $value = $child->getData($attribute->getProductAttribute()->getAttributeCode());
            if (isset($pricesByAttributeValues[$value])) {
                $totalPrice += $pricesByAttributeValues[$value];
            }
        }

        return $this->getTaxHelper()->getPrice($this, $totalPrice, $withTax);
    }

    /**
     * @return $this
     */
    protected function getCategoryPathToImport()
    {
        $index = '';
        $categoriesCollection = Mage::registry(DataFeedWatch_Connector_Helper_Registry::ALL_CATEGORIES_ARRAY_KEY);
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {

            $categoryName = array();
            $path = $category->getPath();
            foreach (explode('/', $path) as $categoryId) {
                if (isset($categoriesCollection[$categoryId])) {
                    $categoryName[] = $categoriesCollection[$categoryId]->getName();
                }
            }
            if (!empty($categoryName)) {
                $key = 'category_path' . $index;
                $this->importData[$key] = implode(' > ', $categoryName);
                $index++;
            }
        }

        return $this;
    }

    /**
     * @param bool $isParent
     * @return array
     */
    protected function getCategoriesNameToImport($isParent = false)
    {
        $index = '';
        $names = array();
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {
            $key            = $isParent ? 'category_parent_name' : 'category_name';
            $key            .= $index++;
            $names[$key]    = $category->getName();
        }

        return $names;
    }

    /**
     * @return string|null
     */
    protected function getBaseImageUrl()
    {
        $this->load('image');
        $image = $this->getImage();
        if ($image !== 'no_selection' && !empty($image)) {

            return $this->getMediaConfig()->getMediaUrl($image);
        }

        return null;
    }

    /**
     * @param null|string $importedBaseImage
     * @param bool $isParent
     * @return array
     */
    protected function getAdditionalImages($importedBaseImage = null, $isParent = false)
    {
        if (empty($importedBaseImage)) {
            $this->getBaseImageUrl();
        }
        $this->load('media_gallery');
        $gallery            = $this->getMediaGalleryImages();

        $index              = 1;
        $additionalImages   = array();
        foreach ($gallery as $image) {
            $imageUrl = $image->getUrl();
            if ($imageUrl !== $importedBaseImage && $imageUrl !== 'no_selection' && !empty($imageUrl)) {
                $key                    = $isParent ? 'parent_additional_image_url' : 'product_additional_image_url';
                $key                    .= $index++;
                $additionalImages[$key] = $imageUrl;
            }
        }

        return $additionalImages;
    }

    /**
     * @return $this
     */
    protected function getExcludedImages()
    {
        $this->load('media_gallery');
        $gallery    = $this->getMediaGallery('images');
        $index      = 1;
        foreach ($gallery as $image) {
            if ($image['disabled']) {
                $imageUrl               = $this->getMediaConfig()->getMediaUrl($image['file']);
                $key                    = 'image_url_excluded' . $index++;
                $this->importData[$key] = $imageUrl;
            }
        }

        return $this;
    }

    /**
     * @param null|Mage_Catalog_Model_Category $category
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getProductUrlRewritten($category = null)
    {
        if (!empty($category)) {
            $categoryId = $category->getId();
        }
        $productId  = $this->getId();
        $store      = $this->getStore();
        $storeId    = $store->getId();
        $coreUrl    = Mage::getModel('core/url_rewrite');
        $idPath     = sprintf('product/%d', $productId);

        if (!empty($categoryId)) {
            $idPath = sprintf('%s/%d', $idPath, $categoryId);
        }
        $coreUrl->setStoreId($storeId);
        $coreUrl->loadByIdPath($idPath);
        $requestPath = $coreUrl->getRequestPath();
        if (empty($requestPath)) {
            return '';
        }

        return $store->getBaseUrl() . $requestPath;
    }

    /**
     * @return $this
     */
    protected function getDfwDefaultVariant()
    {
        $parent = $this->getParent();
        if (empty($parent)) {
            return $this;
        }

        $superAttributes = Mage::registry(DataFeedWatch_Connector_Helper_Registry::ALL_SUPER_ATTRIBUTES_KEY);
        $parentSuperAttributes                      = $parent->getData('super_attribute_ids');
        $parentSuperAttributes                      = explode(',', $parentSuperAttributes);
        $this->importData['dfw_default_variant']    = 1;
        foreach ($parentSuperAttributes as $superAttributeId) {
            if (!isset($superAttributes[$superAttributeId])) {
                continue;
            }
            $superAttribute = $superAttributes[$superAttributeId];
            $defaultValue   = $superAttribute->getDefaultValue();
            if (!empty($defaultValue) && $defaultValue !== $this->getData($superAttribute->getAttributeCode())) {
                $this->importData['dfw_default_variant'] = 0;

                return $this;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function getParentAttributes()
    {
        $parent = $this->getParent();
        if (empty($parent)) {

            return $this;
        }
        $allAttributes = Mage::registry(DataFeedWatch_Connector_Helper_Registry::ALL_ATTRIBUTE_COLLECTION_KEY);
        foreach ($allAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            switch ($attribute->getInheritance()) {
                case (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::CHILD_THEN_PARENT_OPTION_ID:
                    $productData = $this->getData($attributeCode);
                    if (empty($productData) || $this->shouldChangeVisibilityForProduct($attribute)) {
                        $parentData = $parent->getData($attributeCode);
                        $this->setData($attributeCode, $parentData);
                    }
                    break;
                case (string) DataFeedWatch_Connector_Model_System_Config_Source_Inheritance::PARENT_OPTION_ID:
                    $parentData = $parent->getData($attributeCode);
                    if ($attributeCode === 'meta_title') {
                    }
                    $this->setData($attributeCode, $parentData);
                    break;
            }
        }

        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function shouldChangeVisibilityForProduct($attribute)
    {
        $attributeCode = $attribute->getAttributeCode();

        return $attributeCode === 'visibility'
        && (int)$this->getData($attributeCode) === Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
    }

    /**
     * @return Mage_Tax_Helper_Data
     */
    public function getTaxHelper()
    {
        return Mage::helper('tax');
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('datafeedwatch_connector');
    }

    /**
     * @return DataFeedWatch_Connector_Helper_Registry
     */
    public function registryHelper()
    {
        return Mage::helper('datafeedwatch_connector/registry');
    }
}