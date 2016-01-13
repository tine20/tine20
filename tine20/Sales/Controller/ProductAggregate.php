<?php
/**
 * ProductAggregate controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ProductAggregate controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_ProductAggregate extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName         = 'Sales';
        $this->_modelName               = 'Sales_Model_ProductAggregate';
        $this->_backend                 = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'sales_product_agg',
            'modlogActive' => TRUE
        ));
        $this->_doContainerACLChecks    = FALSE;
    }    
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }
     
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_ProductAggregate
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_ProductAggregate
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }


    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $acs = $this->_getProductAccountables($_record);
        if ($acs !== null && $acs->count())
        {
            foreach($acs as $ac) {
                $ac->_inspectBeforeCreateProductAggregate($_record);
            }
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_record->product_id != $_oldRecord->product_id) {
            // uhh, now what?!?
        }
        $acs = $this->_getProductAccountables($_record);
        if ($acs !== null && $acs->count())
        {
            foreach($acs as $ac) {
                $ac->_inspectBeforeUpdateProductAggregate($_record, $_oldRecord);
            }
        }
    }

    /**
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return null|Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getProductAccountables(Sales_Model_ProductAggregate $productAggregate) {
        $json_attributes = $productAggregate->json_attributes;
        if (!is_array($json_attributes) || !isset($json_attributes['assignedAccountables']))
            return null;

        $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
        if ($product->accountable == '')
            return null;


        $app = Tinebase_Core::getApplicationInstance($product->accountable, '');
        return $app->getMultiple($json_attributes['assignedAccountables']);
    }
}
