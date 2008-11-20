<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sebastian Lenk <s.lenk@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Leads extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'metacrm_lead', 'Crm_Model_Lead');
    }
        
    /****************** update / delete *************/
    
    /**
     * delete lead
     *
     * @param int|Crm_Model_Lead $_leads lead ids
     * @return void
     */
    public function delete($_leadId)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
                    
            // delete products
            $where = array(
                $this->_db->quoteInto( $this->_db->quoteIdentifier('lead_id') . ' = ?', $leadId)
            );          
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_leads_products', $where);            
            
            // delete lead
            $where = array(
                $this->_db->quoteInto( $this->_db->quoteIdentifier('id') . ' = ?', $leadId)
            );
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' error while deleting lead ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
    }
    
    /************************ helper functions ************************/

    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Crm_Model_LeadFilter $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Crm_Model_LeadFilter $_filter)
    {
        $_select->where($this->_db->quoteInto( $this->_db->quoteIdentifier('container_id') . ' IN (?)', $_filter->container));
                                
        $_filter->appendFilterSql($_select);
    }        
}
