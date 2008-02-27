<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * the class provides functions to handle applications
 * 
 */
class Tinebase_Application
{
    const ENABLED  = 1;
    
    const DISABLED = 0;
    
    /**
     * the table object for the SQL_TABLE_PREFIX . applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $applicationTable;

    private function __construct() {
        $this->applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'applications'));
    }
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Application
     */
    private static $instance = NULL;
    
    /**
     * Returns instance of Tinebase_Application
     *
     * @return Tinebase_Application
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Application;
        }
        
        return self::$instance;
    }
    
    
    /**
     * returns one application identified by app_id
     *
     * @param int $_applicationId the id of the application
     * @todo code still needs some testing
     * @throws Exception if $_applicationId is not integer and not greater 0
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationById($_applicationId)
    {
        $applicationId = (int)$_applicationId;
        if($applicationId != $_applicationId) {
            throw new InvalidArgumentException('$_applicationId must be integer');
        }
        
        $row = $this->applicationTable->fetchRow('app_id = ' . $applicationId);
        
        $result = new Tinebase_Model_Application($row->toArray());
        
        return $result;
    }

    /**
     * returns one application identified by application name
     *
     * @param string $$_applicationName the name of the application
     * @todo code still needs some testing
     * @throws InvalidArgumentException, Exception
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationByName($_applicationName)
    {
        if(empty($_applicationName)) {
            throw new InvalidArgumentException('$_applicationName can not be empty');
        }
        
        $where = $this->applicationTable->getAdapter()->quoteInto('app_name = ?', $_applicationName);
        if(!$row = $this->applicationTable->fetchRow($where)) {
            throw new Exception("application $_applicationName not found");
        }
        
        $result = new Tinebase_Model_Application($row->toArray());
        
        return $result;
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return Tinebase_RecordSet_Application
     */
    public function getApplications($_filter = NULL, $_sort = 'app_id', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->applicationTable->getAdapter()->quoteInto('app_name LIKE ?', '%' . $_filter . '%');
        }
        
        $rowSet = $this->applicationTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

        $result = new Tinebase_Record_RecordSet($rowSet->toArray(), 'Tinebase_Model_Application');

        return $result;
    }    
    
    /**
     * get enabled or disabled applications
     *
     * @param int $_state can be Tinebase_Application::ENABLED or Tinebase_Application::DISABLED
     * @return Tinebase_Record_RecordSet list of applications
     */
    public function getApplicationsByState($_state)
    {
        if($_state !== Tinebase_Application::ENABLED && $_applicationName !== Tinebase_Application::DISABLED) {
            throw new InvalidArgumentException('$_state can be only Tinebase_Application::ENABLED or Tinebase_Application::DISABLED');
        }
        $where[] = $this->applicationTable->getAdapter()->quoteInto('app_enabled = ?', $_state);
        
        $rowSet = $this->applicationTable->fetchAll($where);

        $result = new Tinebase_Record_RecordSet($rowSet->toArray(), 'Tinebase_Model_Application');

        return $result;
    }    
    
    /**
     * return the total number of applications installed
     *
     * @return int
     */
    public function getTotalApplicationCount($_filter = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->applicationTable->getAdapter()->quoteInto('app_name LIKE ?', '%' . $_filter . '%');
        }
        $count = $this->applicationTable->getTotalCount($where);
        
        return $count;
    }
    
    public function setApplicationState(array $_applicationIds, $_state)
    {
        if($_state != Tinebase_Application::DISABLED && $_state != Tinebase_Application::ENABLED) {
            throw new OutOfRangeException('$_state can be only ' . Tinebase_Application::DISABLED . ' or ' . Tinebase_Application::ENABLED);
        }
        
        $where = array(
            $this->applicationTable->getAdapter()->quoteInto('app_id IN (?)', $_applicationIds)
        );
        
        $data = array(
            'app_enabled' => $_state
        );
        
        $affectedRows = $this->applicationTable->update($data, $where);
        
        error_log("AFFECTED:: $affectedRows");
    }
}