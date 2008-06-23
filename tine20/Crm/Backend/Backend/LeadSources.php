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
 * interface for lead sources
 *
 * @package     Crm
 */
class Crm_Backend_LeadSources implements Crm_Backend_Interface
{
    /**
    * Instance of Crm_Backend_LeadSources
    *
    * @var Crm_Backend_Sources
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
        $this->_table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
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
            self::$_instance = new Crm_Backend_LeadSources();
        }
        return self::$_instance;
    }
        
    /**
     * get Leadsources
     * 
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadsource
     */
    public function getLeadSources($_sort = 'id', $_dir = 'ASC')
    {   
        $rows = $this->_table->fetchAll(NULL, $_sort, $_dir);
        
        $result = new Tinebase_Record_RecordSet('Crm_Model_Leadsource', $rows->toArray());
        
        return $result;
    }

    /**
    * add or updates an option
    *
    * @param Tinebase_Record_Recordset $_leadSources list of lead sources
    * @return unknown
    */
    public function saveLeadsources(Tinebase_Record_Recordset $_leadSources)
    {
        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadsource');

            foreach($_leadSources as $leadSource) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadsource', $leadSource->toArray());                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_leadSources;
    }
    
    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @return int the number of rows deleted
     */
    public function deleteLeadsourceById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
        
        $where  = array(
            $this->_table->getAdapter()->quoteInto('leadsource_id = ?', $Id),
        );
             
        $result = $this->_table->delete($where);

        return $result;
    }
    
    /**
    * get leadsource identified by id
    *
    * @return Crm_Model_Leadsource
    */
    public function getLeadSource($_sourceId)
    {   
        $sourceId = (int)$_sourceId;
        if($sourceId != $_sourceId) {
            throw new InvalidArgumentException('$_sourceId must be integer');
        }
        $rowSet = $this->_table->find($sourceId);
        
        if(count($rowSet) == 0) {
            // something bad happend
        }
        
        $result = new Crm_Model_Leadsource($rowSet->current()->toArray());
   
        return $result;
    }
    
    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteProductsourceById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }      
            $where  = array(
                $this->linksTable->getAdapter()->quoteInto('leadsource_id = ?', $Id),
            );
             
            $result = $this->_table->delete($where);

        return $result;
    }    
}
