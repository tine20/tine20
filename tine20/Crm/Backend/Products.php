<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * interface for products class
 *
 * @package     Crm
 */
class Crm_Backend_Products extends Tinebase_Abstract_SqlTableBackend
{
	/**
	* Instance of Crm_Backend_Products
	*
	* @var Crm_Backend_Products
	*/
    protected $_table;
   
   /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'metacrm_products';
        $this->_modelName = 'Crm_Model_Product';
    	$this->_db = Zend_Registry::get('dbAdapter');
        $this->_table  = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_products'));
    }
}
