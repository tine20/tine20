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
 * backend for leads products
 *
 * @package     Crm
 */
class Crm_Backend_LeadProducts implements Crm_Backend_Interface
{
    /**
    * lead products table
    *
    * @var string
    */
    protected $_table;
    
   /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * the constructor
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leads_products'));
    }
        
    /**
     * get products by lead id
     *
     * @param int $_leadId the leadId
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Product
     */
    public function getProductsByLeadId($_leadId)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);

        $where  = array(
            $this->_table->getAdapter()->quoteInto('lead_id = ?', $leadId)
        );

        $rows = $this->_table->fetchAll($where);
        
        $result = new Tinebase_Record_RecordSet('Crm_Model_Product', $rows->toArray());
   
        return $result;
    }      
}
