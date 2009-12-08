<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 *
 * @todo        add 'getTitleTranslation' function?
 * @todo        use Tinebase_Backend_Sql?
 * @todo        migrate from Zend_Db_Table to plain Zend_Db
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
class Tinebase_Application
{
    /**
     * application enabled
     *
     */
    const ENABLED  = 'enabled';
    
    /**
     * application disabled
     *
     */
    const DISABLED = 'disabled';
    
    /**
     * the table object for the SQL_TABLE_PREFIX . applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_applicationTable;

    /**
     * Table name
     *
     * @var string
     */
    protected $_tableName;

    /**
     * application objects cache
     * 
     * @var array (id/name => Tinebase_Model_Application)
     */
    protected $_applicationCache = array();
    
    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = '';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'applications';
        $this->_applicationTable = new Tinebase_Db_Table(array('name' => $this->_tableName));
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds the instance of the singleton
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
     * returns one application identified by id
     *
     * @param Tinebase_Model_Application|string $_applicationId the id of the application
     * @throws Tinebase_Exception_NotFound
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationById($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        if (isset($this->_applicationCache[$applicationId])) {
            return $this->_applicationCache[$applicationId];
        }
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?' , $applicationId);
        $rows = $this->_applicationTable->fetchAll($where)->toArray();
        
        if (empty($rows)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Application not found. Id: ' . $applicationId);
            throw new Tinebase_Exception_NotFound('Application not found.');
        }
        
        $result = new Tinebase_Model_Application($rows[0]);
        
        $this->_applicationCache[$applicationId] = $result;
        
        return $result;
    }

    /**
     * returns one application identified by application name
     * - results are cached
     *
     * @param string $_applicationName the name of the application
     * @return Tinebase_Model_Application the information about the application
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getApplicationByName($_applicationName)
    {
        if(empty($_applicationName) || ! is_string($_applicationName)) {
            throw new Tinebase_Exception_InvalidArgument('$_applicationName can not be empty / has to be string.');
        }
        
        if (isset($this->_applicationCache[$_applicationName])) {
            return $this->_applicationCache[$_applicationName];
        } 
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = 'getApplicationByName' . $_applicationName;
        $result = $cache->load($cacheId);
        
        if (!$result) {

            $select = $this->_db->select();
            $select->from($this->_tableName)
                   ->where($this->_db->quoteIdentifier('name') . ' = ?', $_applicationName);
    
            $stmt = $this->_db->query($select);
            $queryResult = $stmt->fetch();
            $stmt->closeCursor();
            
            if (!$queryResult) {
                throw new Tinebase_Exception_NotFound("Application $_applicationName not found.");
            }
            $result = new Tinebase_Model_Application($queryResult);
            
            if (isset($cache)) {
                $cache->save($result, $cacheId, array('applications'));
            }
        }
        
        $this->_applicationCache[$_applicationName] = $result;
        
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
    public function getApplications($_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        
        $rowSet = $this->_applicationTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $rowSet->toArray());

        return $result;
    }    
    
    /**
     * get enabled or disabled applications
     *
     * @param int $_state can be Tinebase_Application::ENABLED or Tinebase_Application::DISABLED
     * @return Tinebase_Record_RecordSet list of applications
     */
    public function getApplicationsByState($_status)
    {
        if($_status !== Tinebase_Application::ENABLED && $_status !== Tinebase_Application::DISABLED) {
            throw new Tinebase_Exception_InvalidArgument('$_status can be only Tinebase_Application::ENABLED or Tinebase_Application::DISABLED');
        }
        $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', $_status);
        
        $rowSet = $this->_applicationTable->fetchAll($where);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $rowSet->toArray());

        return $result;
    }    
    
    /**
     * return the total number of applications installed
     *
     * @param $_filter
     * 
     * @return int
     */
    public function getTotalApplicationCount($_filter = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        $count = $this->_applicationTable->getTotalCount($where);
        
        return $count;
    }
    
    /**
     * set application state
     *
     * @param   array $_applicationIds application ids to set new state for
     * @param   string $_state the new state
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setApplicationState(array $_applicationIds, $_state)
    {
        if($_state != Tinebase_Application::DISABLED && $_state != Tinebase_Application::ENABLED) {
            throw new Tinebase_Exception_InvalidArgument('$_state can be only Tinebase_Application::DISABLED  or Tinebase_Application::ENABLED');
        }
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $_applicationIds)
        );
        
        $data = array(
            'status' => $_state
        );
        
        $affectedRows = $this->_applicationTable->update($data, $where);
        
        $this->_cleanCache();
        //error_log("AFFECTED:: $affectedRows");
    }
    
    /**
     * add new appliaction 
     *
     * @param Tinebase_Model_Application $_application the new application object
     * @return Tinebase_Model_Application the new application with the applicationId set
     */
    public function addApplication(Tinebase_Model_Application $_application)
    {
        if (empty($_application->id)) {
            $newId = $_application->generateUID();
            $_application->setId($newId);
        }
        
        $data = $_application->toArray();
        unset($data['tables']);
        
        $this->_applicationTable->insert($data);

        $result = $this->getApplicationById($_application->id);
        
        return $result;
    }
    
    /**
     * get all possible application rights
     *
     * @param   int application id
     * @return  array   all application rights
     */
    public function getAllRights($_applicationId)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_applicationId);
        
        // call getAllApplicationRights for application (if it has specific rights)
        $appAclClassName = $application->name . '_Acl_Rights';
        if (@class_exists($appAclClassName)) {
            $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
            $allRights = $appAclObj->getAllApplicationRights();
        } else {
            $allRights = Tinebase_Acl_Rights::getInstance()->getAllApplicationRights($application->name);   
        }
        
        return $allRights;
    }

    /**
     * get right description
     *
     * @param   int     application id
     * @param   string  right
     * @return  array   right description
     */
    public function getRightDescription($_applicationId, $_right)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_applicationId);
        
        // call getAllApplicationRights for application (if it has specific rights)
        $appAclClassName = $application->name . '_Acl_Rights';
        if ( @class_exists($appAclClassName) ) {
            $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
            $description = $appAclObj->getRightDescription($_right);
        } else {
            $description = Tinebase_Acl_Rights::getInstance()->getRightDescription($_right);   
        }
        
        return $description;
    }
    
    /**
     * get tables of application
     *
     * @param Tinebase_Model_Application $_applicationId
     * @return array
     */
    public function getApplicationTables($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'application_tables', array('name'))
            ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId);
            
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        if($rows === NULL) {
            return array();
        }
        
        $tables = array();
        foreach($rows as $row) {
            $tables[] = $row['name'];
        }
        return $tables;
    }
    
    /**
     * remove table from application_tables table
     *
     * @param Tinebase_Model_Application|string $_applicationId the applicationId
     * @param string $_tableName the table name
     */
    public function removeApplicationTable($_applicationId, $_tableName)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . '= ?', $applicationId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . '= ?', $_tableName)
        );
        
        $this->_db->delete(SQL_TABLE_PREFIX . 'application_tables', $where);
    }
    
    /**
     * remove application from applications table
     *
     * @param Tinebase_Model_Application|string $_applicationId the applicationId
     */
    public function deleteApplication($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
                
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . '= ?', $applicationId)
        );
        
        $this->_db->delete(SQL_TABLE_PREFIX . 'applications', $where);
        
        $this->_cleanCache();
    }
    
    /**
     * add table to tine registry
     *
     * @param Tinebase_Model_Application
     * @param string name of table
     * @param int version of table
     * @return int
     */
    public function addApplicationTable($_applicationId, $_name, $_version)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $applicationData = array(
            'application_id'    => $applicationId,
            'name'              => $_name,
            'version'           => $_version
        );
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'application_tables', $applicationData);
    }
    
    /**
     * update application
     * 
     * @param Tinebase_Model_Application $_application
     * @return Tinebase_Model_Application
     */
    public function updateApplication(Tinebase_Model_Application $_application)
    {
        $backend = new Tinebase_Backend_Sql('Tinebase_Model_Application', 'applications', $this->_db, SQL_TABLE_PREFIX);
        return $backend->update($_application);
    }
    
    /**
     * clean cache
     * 
     * @return void
     */
    protected function _cleanCache()
    {
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('applications'));
        $this->_applicationCache = array();
    }
}
