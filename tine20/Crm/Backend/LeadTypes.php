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
 * interface for lead types class
 *
 * @package     Crm
 */
class Crm_Backend_LeadTypes implements Crm_Backend_Interface
{
    /**
    * Instance of Crm_Backend_Types
    *
    * @var Crm_Backend_Types
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
        $this->leadType = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
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
     * @var Crm_Backend_Sql
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
            self::$_instance = new Crm_Backend_Types();
        }
        return self::$_instance;
    }
        
    /**
     * get Leadtypes
     * 
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadtype
     */
    public function getLeadTypes($_sort = 'id', $_dir = 'ASC')
    {   
        $rows = $this->leadTypeTable->fetchAll(NULL, $_sort, $_dir);
        
        $result = new Tinebase_Record_RecordSet('Crm_Model_Leadtype', $rows->toArray());
        
        return $result;
    }   
    
    /**
    * add or updates an option
    *
    * @param Crm_Leadtype $_optionData the optiondata
    * @return unknown
    */
    public function saveLeadtypes(Tinebase_Record_Recordset $_optionData)
    {

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadtype');

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadtype', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
    }

    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteLeadtypeById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
            $where  = array(
                $this->leadTypeTable->getAdapter()->quoteInto('leadtype_id = ?', $Id),
            );
             
            $result = $this->leadTypeTable->delete($where);

        return $result;
    }    
        
    /**
    * get leadtype identified by id
    *
    * @param int $_typeId
    * @return Crm_Model_Leadtype
    */
    public function getLeadType($_typeId)
    {   
        $typeId = (int)$_typeId;
        if($typeId != $_typeId) {
            throw new InvalidArgumentException('$_typeId must be integer');
        }
        $rowSet = $this->leadTypeTable->find($typeId);
        
        if(count($rowSet) == 0) {
            throw new Exception('lead type not found');
        }
        
        $result = new Crm_Model_Leadtype($rowSet->current()->toArray());
   
        return $result;
    }
}
