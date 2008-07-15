<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sebastian Lenk <s.lenk@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        rename function updateLead -> update
 * @todo        rename container to container_id in leads table
 */


/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Leads extends Tinebase_Abstract_SqlTableBackend
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'metacrm_lead';
        $this->_modelName = 'Crm_Model_Lead';
    	$this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
    }
    
    /********** get / search ***********/

    /**
     * get lead
     *
     * @param int|Crm_Model_Lead $_id
     * @return Crm_Model_Lead
     */
    public function get($_id)
    {
        $id = Crm_Model_Lead::convertLeadIdToInt($_id);

        $select = $this->_getSelect()
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('lead.id = ?', $id));

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderflowException('lead not found');
        }
        
        $lead = new Crm_Model_Lead($row);

        return $lead;
    }
    
    /**
     * Search for leads matching given filter
     *
     * @param Crm_Model_LeadFilter $_filter
     * @param Crm_Model_LeadPagination $_pagination
     * @return Tinebase_Record_RecordSet of Crm_Model_Lead records
     * 
     * @todo    abstract filter2sql
     * @todo    add more filters?
     */
    public function search(Crm_Model_LeadFilter $_filter, Crm_Model_LeadPagination $_pagination = NULL)
    {
        $set = new Tinebase_Record_RecordSet('Crm_Model_Lead');
        
        // empty means, that e.g. no shared containers exist
        if (empty($_filter->container)) {
            return $set;
        }
        
        if ($_pagination === NULL) {
            $_pagination = new Crm_Model_LeadPagination();
        }
        
        // build query
        $select = $this->_getSelect()
            ->where($this->_db->quoteInto('lead.container IN (?)', $_filter->container));
                        
        if (!empty($_pagination->limit)) {
            $select->limit($_pagination->limit, $_pagination->start);
        }
        if (!empty($_pagination->sort)) {
            $select->order($_pagination->sort . ' ' . $_pagination->dir);
        }
        if (!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(lead.lead_name LIKE ? OR lead.description LIKE ?)', '%' . $_filter->query . '%'));
        }
        if (!empty($_filter->leadstate)) {
            $select->where($this->_db->quoteInto('lead.leadstate_id = ?', $_filter->leadstate));
        }
        if (!empty($_filter->probability)) {
            $select->where($this->_db->quoteInto('lead.probability >= ?', (int)$_filter->probability));
        }
        if (isset($_filter->showClosed) && $_filter->showClosed){
            // nothing to filter
        } else {
            $select->where('end IS NULL');
        }
        
        $stmt = $this->_db->query($select);
        
        // get records
        $leads = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($leads as $leadArray) {
            $lead = new Crm_Model_Lead($leadArray, true, true);
            $set->addRecord($lead);
        }
        
        return $set;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Crm_Model_LeadFilter $_filter
     * @return int
     */
    public function searchCount(Crm_Model_LeadFilter $_filter)
    {
        return count($this->search($_filter));
    }
    
    /**
     * get the basic select object to fetch leads from the database 
     *
     * @return Zend_Db_Select
     */
    protected function _getSelect()
    {
        $select = $this->_db->select()
            ->from(array('lead' => SQL_TABLE_PREFIX . 'metacrm_lead'), array(
                'id',
                'lead_name',
                'leadstate_id',
                'leadtype_id',
                'leadsource_id',
                'container',
                'start',
                'description',
                'end',
                'turnover',
                'probability',
                'end_scheduled')
            );
                
        return $select;
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

        $db = Zend_Registry::get('dbAdapter');
        
        $db->beginTransaction();
        
        try {
            $where = array(
                $db->quoteInto('lead_id = ?', $leadId)
            );          
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leads_products', $where);            

            $where = array(
                $db->quoteInto('link_app1 = ?', 'crm'),
                $db->quoteInto('link_id1 = ?', $leadId),
                $db->quoteInto('link_app2 = ?', 'addressbook')
            );                                  
            $db->delete(SQL_TABLE_PREFIX . 'links', $where);               
            
            $where = array(
                $db->quoteInto('id = ?', $leadId)
            );
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where);

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * updates a lead
     *
     * @param Crm_Lead $_leadData the leaddata
     * @return Crm_Model_Lead
     * 
     * @todo    rename
     * @todo    remove responsible/customer/... -> they are covered with the relations field
     */
    public function updateLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_lead);        

        $leadData = $_lead->toArray();
        
       // unset fields that should not be written into the db
        $unsetFields = array('id', 'tasks', 'products', 'tags', 'relations');
        foreach ( $unsetFields as $field ) {
            unset($leadData[$field]);
        }
                
        $where  = array(
            $this->_table->getAdapter()->quoteInto('id = ?', $leadId),
        );
        
        $updatedRows = $this->_table->update($leadData, $where);
                
        return $this->get($leadId);
    }
}
