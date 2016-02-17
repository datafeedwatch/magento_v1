<?php
class DataFeedWatch_Connector_Helper_Attribute_Is_In_Stock
    extends DataFeedWatch_Connector_Helper_Attribute
    implements DataFeedWatch_Connector_Helper_Attribute_Interface
{
    public function getAttributeByLogic($attributeCode,$logic)
    {
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();
        $parentProduct = $result->getParentProduct();

        if($parentProduct){
            if ($logic =='parent') {
                return $this->getParentIsInStockValue();
            } else if ($logic =='child') {
                return $this->getChildIsInStockValue();
            } else if ($logic =='child_then_parent') {

                /*NVI variants should inherit is_in_stock ==0 from parent only when it is 0*/
                $parentInventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentProduct);
                if (!empty($parentInventoryStatus)) {
                    $isInStockParent = $parentInventoryStatus->getIsInStock() == '1' ? 1 : 0;
                }

                if($isInStockParent == 0 && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
                    return 0;
                } else {
                    /* Otherwise simply fetch product value */
                    $inventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                    if (!empty($inventoryStatus)) {
                        $isInStock = $inventoryStatus->getIsInStock() == '1' ? 1 : 0;
                        return $isInStock;
                    }
                    return 0;
                }
            }
        } else {
            return $this->getChildIsInStockValue();
        }
        return null;
    }


    public function getChildIsInStockValue(){
        $result = Mage::registry('datafeedwatch_connector_result');
        $product = $result->getProduct();

        /* Otherwise simply fetch product value */
        $inventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        if (!empty($inventoryStatus)) {
            $isInStock = $inventoryStatus->getIsInStock() == '1' ? 1 : 0;
            return $isInStock;
        }
        return 0;
    }

    public function getParentIsInStockValue(){
        $result = Mage::registry('datafeedwatch_connector_result');
        $parentProduct = $result->getParentProduct();
        $parentInventoryStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentProduct);
        if (!empty($parentInventoryStatus)) {
            $isInStockParent = $parentInventoryStatus->getIsInStock() == '1' ? 1 : 0;
            return $isInStockParent;
        }
        return 0;
    }



}