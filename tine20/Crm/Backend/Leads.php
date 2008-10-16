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
 * @todo        rename container to container_id in leads table
 * @todo use functions from Tinebase_Abstract_SqlTableBackend
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
     * 
     * @deprecated
     * @todo replace by getMultiple function from SqlTableBackend 
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
    
    /****************** update / delete *************/
    
    /**
     * delete lead
     *
     * @param int|Crm_Model_Lead $_leads lead ids
     * @param boolean $_deleteTasks delete linked tasks
     * @return void
     */
    public function delete($_leadId, $_deleteTasks = TRUE)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
                    
            // delete products
            $where = array(
                $this->_db->quoteInto('lead_id = ?', $leadId)
            );          
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_leads_products', $where);            
            
            // remove linked tasks / relations
            if ($_deleteTasks) {
                $relationsController = Tinebase_Relations::getInstance();                
                $relations = $relationsController->getRelations('Crm_Model_Lead', 'Sql', $leadId);
                $relationsController->setRelations('Crm_Model_Lead', 'Sql', $leadId, array());
                foreach ($relations as $relation) {
                    if ($relation->related_model === 'Tasks_Model_Task' /* && $relation->own_degree === 'sibling' */) {
                        Tasks_Controller::getInstance()->deleteTask($relation->related_id);
                    }
                }                
            }
            
            // delete lead
            $where = array(
                $this->_db->quoteInto('id = ?', $leadId)
            );
            $this->_db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' error while deleting lead ' . $e->__toString());
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw($e);
        }
    }

    /**
     * updates a lead
     *
     * @param Crm_Lead $_leadData the leaddata
     * @return Crm_Model_Lead
     * 
     * @deprecated
     * @todo replace by getMultiple function from SqlTableBackend 
     */
    public function update(Tinebase_Record_Interface $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_lead);        

        $leadData = $_lead->toArray();
        
       // unset fields that should not be written into the db
        $unsetFields = array('id', 'tasks', 'products', 'tags', 'relations', 'notes');
        foreach ( $unsetFields as $field ) {
            unset($leadData[$field]);
        }
                
        $where  = array(
            $this->_table->getAdapter()->quoteInto('id = ?', $leadId),
        );
        
        $updatedRows = $this->_table->update($leadData, $where);
                
        return $this->get($leadId);
    }
    
    
    /************************ helper functions ************************/

    /**
     * get the basic select object to fetch leads from the database 
     * @param $_getCount only get the count
     *
     * @return Zend_Db_Select
     */
    protected function _getSelect($_getCount = FALSE)
    {        
        if ($_getCount) {
            $fields = array('count' => 'COUNT(*)');    
        } else {
            $fields = array(
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
                'end_scheduled',
                'created_by',
                'creation_time',
                'last_modified_by',
                'last_modified_time',
                'is_deleted',
                'deleted_time',
                'deleted_by',
            );
        }

        $select = $this->_db->select()
            ->from(array('lead' => SQL_TABLE_PREFIX . 'metacrm_lead'), $fields);
        
        return $select;
    }
    
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Crm_Model_LeadFilter $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Crm_Model_LeadFilter $_filter)
    {
        $_select->where($this->_db->quoteInto('lead.container IN (?)', $_filter->container));
                        
        if (!empty($_filter->query)) {
            $_select->where($this->_db->quoteInto('(lead.lead_name LIKE ? OR lead.description LIKE ?)', '%' . $_filter->query . '%'));
        }
        if (!empty($_filter->leadstate)) {
            $_select->where($this->_db->quoteInto('lead.leadstate_id = ?', $_filter->leadstate));
        }
        if (!empty($_filter->probability)) {
            $_select->where($this->_db->quoteInto('lead.probability >= ?', (int)$_filter->probability));
        }
        if (isset($_filter->showClosed) && $_filter->showClosed){
            // nothing to filter
        } else {
            $_select->where('end IS NULL');
        }
    }        
}
