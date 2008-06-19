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
 * @todo        add search/count functions and replace old deprecated functions
 * @todo        rename functions (update)
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
    * Instance of Crm_Backend_Leads
    *
    * @var Crm_Backend_Leads
    */
    protected $_table;
   
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_tableName = SQL_TABLE_PREFIX . 'metacrm_lead';
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
        $this->_modelName = 'Crm_Model_Lead';
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

      // echo $select->__toString();
       
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
            //error_log(print_r($Task->toArray(),true));
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
     * 
     * @todo add tags or other joins?
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
            //->joinLeft(array('tag'     => $this->_tableNames['tag']), 'tasks.id = tag.task_id', array())
            //->join(array('leadstate' => SQL_TABLE_PREFIX . 'metacrm_leadstate'),
            //    'lead.leadstate_id = leadstate.id', array( 'leadstate') );        
            
            //echo $selectObject->__toString();     
                
        return $select;
    }

    // @todo remove deprecated functions
    
    /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
     * 
     * @deprecated
     */
    protected function _getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $where = array();
        
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('(lead_name LIKE ? OR description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        if( is_numeric($_leadstate) && $_leadstate > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead.leadstate_id = ?', (int)$_leadstate);
        }
        
        if( is_numeric($_probability) && $_probability > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead.probability >= ?', (int)$_probability);
        }       

        if($_getClosedLeads === FALSE  || $_getClosedLeads == 'false') {
            $where[] = 'end IS NULL';
        }
        return $where;
    }
    
    /**
     * _getLeadsFromTable
     *
     * @param array $_where
     * @param unknown_type $_filter
     * @param unknown_type $_sort
     * @param unknown_type $_dir
     * @param unknown_type $_limit
     * @param unknown_type $_start
     * @param unknown_type $_leadstate
     * @param unknown_type $_probability
     * @param unknown_type $_getClosedLeads
     * @return unknown
     * 
     * @deprecated
     */
    protected function _getLeadsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads)
    {
        $where = array_merge($_where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));

        $db = Zend_Registry::get('dbAdapter');

        $select = $this->_getSelect()
            ->order($_sort.' '.$_dir)
            ->limit($_limit, $_start);

         foreach($where as $whereStatement) {
              $select->where($whereStatement);
         }               
       //echo  $select->__toString();
       
        $stmt = $db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $leads = new Tinebase_Record_RecordSet('Crm_Model_Lead', $rows);
                
        return $leads;
    }   
    
    /**
     * add quick search filter
     *
     * @param unknown_type $_where
     * @param unknown_type $_filter
     * @return unknown
     * 
     * @deprecated 
     */
    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $_where[] = $this->_table->getAdapter()->quoteInto('(lead_name LIKE ? OR description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        return $_where;
    }

    /**
     * get list of leads from all shared folders the current user has access to
     *
     * @param array $_container container to read the contacts from
     * @param string $_filter string to search for in leads
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many leads to display
     * @param unknown_type $_start how many leads to skip
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     * 
     * @deprecated
     */
    public function getLeads(array $_container, $_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL, $_leadState = NULL, $_probability = NULL, $_getClosedLeads = FALSE)
    {
        if(count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }        

        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('container IN (?)', $_container)
        );
        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadState, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    /**
     * get total count of leads matching filter
     *
     * @param array $_container
     * @param string $_filter
     * @param int $_leadState
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return int total number of matching leads
     * 
     * @deprecated
     */
    public function getCountOfLeads(array $_container, $_filter = NULL, $_leadState = NULL, $_probability = NULL, $_getClosedLeads = FALSE)
    {
        if(count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }        
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('container IN (?)', $_container)
        );
                
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadState, $_probability, $_getClosedLeads));
        
        $result = $this->_table->getTotalCount($where);

        return $result;
    }
    
    /****************** update / delete *************/
    
    /**
     * delete lead
     *
     * @param int|Crm_Model_Lead $_leads lead ids
     * @return void
     * 
     * @todo    rename
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
     */
    public function updateLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_lead);        

        $leadData = $_lead->toArray();
        
       // unset fields that should not be written into the db
        $unsetFields = array('id', 'responsible', 'customer', 'partner', 'tasks', 'tags');
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
