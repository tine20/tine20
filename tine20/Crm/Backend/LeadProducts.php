<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        move that to Crm_Backend_Products?
 */


/**
 * backend for leads products
 *
 * @package     Crm
 */
class Crm_Backend_LeadProducts extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'metacrm_products';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'metacrm_products';

    /**
     * Identifier
     *
     * @var string
     */
    protected $_identifier = 'lead_id';
    
    /**
     * get products by lead id
     *
     * @param int $_leadId the leadId
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_LeadProduct
     * 
     */
    public function getByLeadId($_leadId)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);

        $select = $this->_db->select();
        $select->from(SQL_TABLE_PREFIX . 'metacrm_leads_products')
                ->where($this->_db->quoteInto( $this->_db->quoteIdentifier('lead_id') . ' = ?', $leadId));
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
                
        $result = new Tinebase_Record_RecordSet('Crm_Model_LeadProduct', $queryResult);
   
        return $result;
    }      
    
    /**
    * add or updates an product (which belongs to one lead)
    *
    * @param int $_leadId the lead id
    * @param Tinebase_Record_Recordset $_productData the productdata
    */
    public function saveProducts($_leadId, Tinebase_Record_Recordset $_productData)
    {    
        $products = $_productData->toArray();
    
        if(!(int)$_leadId || $_leadId === 0) {
            return $_productData;  
        }
          
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_leads_products', 'lead_id = '.$_leadId);

            foreach($products as $data) {
                if (empty($data['id'])) {
                    unset($data['id']);
                }
                $this->_db->insert(SQL_TABLE_PREFIX . 'metacrm_leads_products', $data);                
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            error_log($e->getMessage());
        }
    }    
    
}
