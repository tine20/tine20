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
 * interface for lead states class
 *
 * @package     Crm
 */
class Crm_Backend_LeadStates extends Tinebase_Abstract_SqlTableBackend
{
    /**
    * Instance of Crm_Backend_LeadStates
    *
    * @var Crm_Backend_LeadStates
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
        $this->_tableName = SQL_TABLE_PREFIX . 'metacrm_leadstate';
        $this->_modelName = 'Crm_Model_Leadstate';
    	$this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
    }
    
    /**
     * get leadstates
     * 
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadstate
     */
    public function getLeadStates($_sort = 'id', $_dir = 'ASC')
    {   
        $rows = $this->_table->fetchAll(NULL, $_sort, $_dir);
        
        $result = new Tinebase_Record_RecordSet('Crm_Model_Leadstate', $rows->toArray());
        
        return $result;
    }   
    
    /**
     * get state identified by id
     *
     * @param int $_leadStateId
     * @return Crm_Model_Leadstate
     */
    public function getLeadState($_leadStateId)
    {   
        $stateId = (int)$_leadStateId;
        if($stateId != $_leadStateId) {
            throw new InvalidArgumentException('$_leadStateId must be integer');
        }
        $rowSet = $this->_table->find($stateId);
        
        if(count($rowSet) === 0) {
            throw new Exception('lead state not found');
        }
        
        $result = new Crm_Model_Leadstate($rowSet->current()->toArray());
   
        return $result;
    }
    
    /**
    * add or updates an option
    *
    * @param Crm_Leadstate $_optionData the optiondata
    * @return unknown
    */
    public function saveLeadstates(Tinebase_Record_Recordset $_optionData)
    {

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadstate');

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadstate', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
    }
}
