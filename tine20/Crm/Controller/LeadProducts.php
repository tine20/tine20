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
 * @deprecated  this is going to be moved to erp/sales mgmt
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * products controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller_LeadProducts extends Tinebase_Controller_Abstract
{
    /**
     * lead products backend
     * 
     * @var Crm_Backend_LeadProducts
     */
    protected $_leadProductsBackend = NULL;
    
    /**
     * products backend
     * 
     * @var Crm_Backend_Products
     */
    protected $_backend = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Crm';
        $this->_leadProductsBackend = new Crm_Backend_LeadProducts();
        $this->_backend = new Crm_Backend_Products();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds the instance of the singleton
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
            self::$_instance = new Crm_Controller_LeadProducts();
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
        $result = $this->_backend->getAll($_sort, $_dir);
        
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
        $result = $this->_backend->get($_productId);
        
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
        $existingProducts = $this->_backend->getAll();
        
        $migration = $existingProducts->getMigration($_products->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
        	$this->_backend->delete($id);
        }
        
        // add / create
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toCreateIds'])) {
        		$this->_backend->create($product);
        	}
        }
        
        // update
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toUpdateIds'])) {
        		$this->_backend->update($product);
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
        $result = $this->_leadProductsBackend->getByLeadId($_leadId);

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
        $this->_leadProductsBackend->saveProducts($_leadId, $_products);
    } 
    
}
