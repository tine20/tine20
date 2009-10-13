<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Leads extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'metacrm_lead';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Lead';
    
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
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $leadId)
            );
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' error while deleting lead ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
    }
}
