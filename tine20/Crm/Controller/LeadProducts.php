<?php
/**
 * products controller for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add abstract crm controller and extend it here
 */

/**
 * products controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller_LeadProducts extends Tinebase_Application_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Crm';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Controller_LeadProducts
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller_LeadProducts
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller_LeadProducts;
        }
        
        return self::$_instance;
    }    
    
    /*************** products functions *****************/

    /**
     * get products available
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     * 
     */
    public function getProducts($_sort = 'id', $_dir = 'ASC')
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->getAll($_sort, $_dir);
        
        return $result;    
    }     

    /**
     * get product
     *
     * @param integer $_productId
     * @return Crm_Model_Product
     * 
     */
    public function getProduct($_productId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->get($_productId);
        
        return $result;    
    }     
    
    /**
     * saves products
     *
     * Saving products means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_products Products to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_products
     */
    public function saveProducts(Tinebase_Record_Recordset $_products)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $existingProducts = $backend->getAll();
        
        $migration = $existingProducts->getMigration($_products->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
        	$backend->delete($id);
        }
        
        // add / create
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toCreateIds'])) {
        		$backend->create($product);
        	}
        }
        
        // update
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toUpdateIds'])) {
        		$backend->update($product);
        	}
        }
        
        return $_products;
    } 
    
    /**
     * get Products linked to a lead
     *
     * @param string $_leadId
     * @return Tinebase_Record_Recordset products
     * 
     * @todo write test
     */ 
    public function getLeadProducts($_leadId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_PRODUCTS);
        $result = $backend->get($_leadId);

        return $result;    
    } 
    
    /**
     * save Products linked to a lead
     *
     * @param string $_leadId
     * @param Tinebase_Record_Recordset $_products
     * 
     * @todo write test
     */ 
    public function saveLeadProducts($_leadId, Tinebase_Record_Recordset $_products)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_PRODUCTS);
        $backend->saveProducts($_leadId, $_products);
    } 
    
}
