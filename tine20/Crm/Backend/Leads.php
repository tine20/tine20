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
 */


/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Leads implements Crm_Backend_Interface
{
    /**
    * Instance of Crm_Backend_Leads
    *
    * @var Crm_Backend_Leads
    */
    protected $_table;
   
   /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_lead'));
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone ()
    {
    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Backend_Leads
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Crm_Backend_Sql
     */
    public static function getInstance ()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Backend_Leads();
        }
        return self::$_instance;
    }
    
    /**
    * get LeadContacts
    *
    * @return unknown
    */  
    public function getLeadContacts(Tinebase_Record_Recordset $_leads)
    {    
        $leads = $_leads->toArray();
        
        foreach($leads AS $lead)
        {
            $_id = $lead['id'];
            $_contact = $this->getContactsById($_id);
            $leadContacts[$_id] = $_contact;
        }
    
        return $leadContacts;
    }
    
    /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
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
    
    //handle for FOLDER->LEADS functions
    protected function _getLeadsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads)
    {
        $where = array_merge($_where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));

        $db = Zend_Registry::get('dbAdapter');

        $select = $this->_getLeadSelectObject()
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
     * get the basic select object to fetch leads from the database 
     *
     * @todo do we need this join here
     * @return Zend_Db_Select
     */
    protected function _getLeadSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');
        $selectObject = $db->select()
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
            )
            ->join(array('leadstate' => SQL_TABLE_PREFIX . 'metacrm_leadstate'),
                'lead.leadstate_id = leadstate.id', array( 'leadstate') );        
                // 'lead.id = leadstate.id');
                
            //echo $selectObject->__toString();     
                
        return $selectObject;
    }

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
    * add a lead
    *
    * @param Crm_Lead $_leadData the leaddata
    * @return Crm_Model_Lead
    */
    public function addLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }

        $leadData = $_lead->toArray();
        if(empty($_lead->id)) {
            unset($leadData['id']);
        }
        
        // unset fields that should not be written into the db
        $unsetFields = array('responsible', 'customer', 'partner', 'tasks', 'tags');
        foreach ( $unsetFields as $field ) {
            unset($leadData[$field]);
        }
        
        $id = $this->_table->insert($leadData);

        // if we insert a contact without an id, we need to get back one
        if(empty($_lead->id) && $id == 0) {
            throw new Exception("returned lead id is 0");
        }
        
        // if the account had no accountId set, set the id now
        if(empty($_lead->id)) {
            $_lead->id = $id;
        }
        
        return $this->get($_lead->id);
    }
    
    /**
     * get lead
     *
     * @param int|Crm_Model_Lead $_id
     * @return Crm_Model_Lead
     */
    public function get($_id)
    {
        $id = Crm_Model_Lead::convertLeadIdToInt($_id);

        $select = $this->_getLeadSelectObject()
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
    
    /**
     * delete lead
     *
     * @param int|Crm_Model_Lead $_leads lead ids
     * @return void
     */
    public function deleteLead($_leadId)
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
