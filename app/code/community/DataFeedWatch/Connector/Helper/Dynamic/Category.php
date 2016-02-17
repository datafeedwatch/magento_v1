<?php
class DataFeedWatch_Connector_Helper_Dynamic_Category extends DataFeedWatch_Connector_Helper_Attribute {

    public function addProductCategoriesToResult($usingParent){

        $result = Mage::registry('datafeedwatch_connector_result');
        if($usingParent == 1){
            $product = $result->getParentProduct();
        } else {
            $product = $result->getProduct();
        }

        $categoryCollection = $product->getCategoryCollection();
        foreach($categoryCollection as $category){
            $category_id[] = $category->getId();
        }

        if (empty($category_id)) {
            $result->setValueOf('category_name','');
            $result->setValueOf('category_parent_name','');
            $result->setValueOf('category_path','');
        } else {
            rsort($category_id);
            $index = '';
            foreach ($category_id as $key => $cate) {

                /* $this $this->_storeCategories is created in loadCategories call on beginning of products() */
                if (!in_array($cate, $this->_storeCategories)) {
                    continue;
                }

                /* $this $this->_storeCategories is created in loadCategories call on beginning of products(),
                specifically nodeToArray function */
                if (!array_key_exists($cate, $this->_categories)) {
                    continue;
                }

                $category = $this->_categories[$cate];


                $result->setValueOf('category_name' . $index,$category['name']);

                /* @TODO: move these two to property, doesn't make sense to read them everytime */
                $storeRoot = Mage::app()->getStore($this->storeId)->getRootCategoryId();
                $magentoRootCat = $this->_categories[$storeRoot]['parent_id'];

                if(array_key_exists($category['parent_id'],$this->_categories)
                    && $category['parent_id']!= $magentoRootCat
                ) {
                    $result->setValueOf('category_parent_name' . $index,$this->_categories[$category['parent_id']]['name']);
                }

                $categoryPath = $this->buildCategoryPath($category['category_id']);
                $result->setValueOf('category_path' . $index,implode(' > ', $categoryPath));

                if ($index == '') {
                    $index = 1;
                } else {
                    $index++;
                }
            }
        }

        return $result;
    }

    public function buildCategoryPath($category_id, &$path = array())
    {
        if(!$category_id){
            return $path;
        }
        if(array_key_exists($category_id,$this->_categories)){
            $category = $this->_categories[$category_id];
            if ($category['parent_id'] != '0') {
                $this->buildCategoryPath($category['parent_id'], $path);
            }

            if ($category['is_active'] == '1') {
                $path[] = $category['name'];
            }
        }

        return $path;
    }

    /**
     * prepare _storeCategories and _categories
     * @param int $storeRootCategoryId
     * @return array
     * @throws Mage_Core_Exception
     */
    public function loadCategories($storeRootCategoryId)
    {
        /* prepare _storeCategories */
        $storeCategoriesCollection = Mage::getResourceModel('catalog/category_collection');
        $storeCategoriesCollection->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addPathsFilter('%/' . $storeRootCategoryId);

        foreach ($storeCategoriesCollection as $storeCategory) {
            /* @var $storeCategory Mage_Catalog_Model_Category */
            $this->_storeCategories[] = $storeCategory->getId();
        }

        /* prepare _categories */
        $parentId = 1;

        /* @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
        $tree = Mage::getResourceSingleton('catalog/category_tree')->load();
        $root = $tree->getNodeById($parentId);

        if ($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active');

        $tree->addCollectionData($collection, true);

        return $this->_nodeToArray($root);
    }

    /**
     * Convert node to array
     *
     * @param Varien_Data_Tree_Node $node
     * @return array
     */
    private function _nodeToArray(Varien_Data_Tree_Node $node)
    {
        /* add this node to $this->_categories */
        $this->_categories[$node->getId()] = array(
            'category_id' => $node->getId(),
            'parent_id' => $node->getParentId(),
            'name' => $node->getName(),
            'is_active' => $node->getIsActive()
        );

        /* parse children nodes */
        $children = $node->getChildren();
        /* @var $children  Varien_Data_Tree_Node_Collection */
        if (!empty($children)) {
            foreach ($children->getNodes() as $child) {
                $this->_nodeToArray($child);
            }
        }
    }

    /**
     * This is cache-like variable
     * @var array $_categories */
    private $_categories;

    /**
     * This is cache-like variable used by DataFeedWatch_Connector_Helper_Data
     * @var array $_storeCategories cache-like variable */
    private $_storeCategories;

    public function getCategories(){
        return $this->_categories;
    }

    public function getStoreCategories(){
        return $this->_storeCategories;
    }


}