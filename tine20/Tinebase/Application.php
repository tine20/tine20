<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 * @todo        add 'getTitleTranslation' function?
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
     * Table name
     *
     * @var string
     */
    protected $_tableName = 'applications';
    
    /**
     * in class cache
     * 
     * @var array
     */
    protected $_classCache = array(
        'getApplications' => array()
    );
    
    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
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
        
        $application = $this->getApplications()->getById($applicationId);
        
        if (!$application) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Application not found. Id: ' . $applicationId);
            throw new Tinebase_Exception_NotFound("Application $applicationId not found.");
        }
        
        return $application;
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
        
        $application = $this->getApplications()->find('name', $_applicationName);
        
        if (!$application) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Application not found. Name: ' . $_applicationName);
            throw new Tinebase_Exception_NotFound("Application $_applicationName not found.");
        }
        
        return $application;
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Application
     */
    public function getApplications($_filter = NULL, $_sort = null, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $filter = null;
        if ($_filter) {
            $filter = new Tinebase_Model_ApplicationFilter(array(
                array('field' => 'name', 'operator' => 'contains', 'value' => $_filter),
            ));
        }
        
        $pagination = null;
        if ($_sort) {
            $pagination = new Tinebase_Model_Pagination(array(
                'sort'  => $_sort,
                'dir'   => $_dir,
                'start' => $_start,
                'limit' => $_limit
            ));
        }
        
        if ($filter === null && $pagination === null) {
            $classCacheId = 'allApplications';
            
            if (isset($this->_classCache[__FUNCTION__][$classCacheId])) {
                return $this->_classCache[__FUNCTION__][$classCacheId];
            }
            
            $cache = Tinebase_Core::getCache();
            if ($cache instanceof Zend_Cache_Core) {
                $cacheId = __FUNCTION__ . '_' . $classCacheId;
                
                $result = $cache->load($cacheId);
                if ($result instanceof Tinebase_Record_RecordSet) {
                    $this->_classCache[__FUNCTION__][$classCacheId] = $result;
                    
                    return $result;
                }
            }
        }
        
        $result = $this->_getBackend()->search($filter, $pagination);
        
        // cache result for this request 
        if (isset($classCacheId)) {
            $this->_classCache[__FUNCTION__][$classCacheId] = $result;
        }
        
        // cache result in external cache
        // cache will be cleared, when an application will be added or updated
        if (isset($cacheId)) {
            $cache->save($result, $cacheId);
        }
        
        return $result;
    }
    
    /**
     * get enabled or disabled applications
     *
     * @param  string  $state  can be Tinebase_Application::ENABLED or Tinebase_Application::DISABLED
     * @return Tinebase_Record_RecordSet list of applications
     */
    public function getApplicationsByState($state)
    {
        if (!in_array($state, array(Tinebase_Application::ENABLED, Tinebase_Application::DISABLED))) {
            throw new Tinebase_Exception_InvalidArgument('$status can be only Tinebase_Application::ENABLED or Tinebase_Application::DISABLED');
        }
        
        $result = $this->getApplications(null, /* sort = */ 'order')->filter('status', $state);
        
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
        $select = $this->_getDb()->select()
            ->from(SQL_TABLE_PREFIX . $this->_tableName, array('count' => 'COUNT(*)'));
        
        if($_filter !== NULL) {
            $select->where($this->_getDb()->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        
        $stmt = $this->_getDb()->query($select);
        $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        return $result[0];
    }
    
    /**
     * return if application is installed (and enabled)
     *
     * @param  Tinebase_Model_Application|string  $applicationId  the application name/id/object
     * @param  boolean $checkEnabled (FALSE by default)
     * 
     * @return boolean
     */
    public function isInstalled($applicationId, $checkEnabled = FALSE)
    {
        try {
            $app = $this->getApplicationById($applicationId);
            return ($checkEnabled) ? ($app->status === self::ENABLED) : TRUE;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return FALSE;
        } catch (Zend_Db_Statement_Exception $tenf) {
            // database tables might be not available yet
            // @see 0011338: First Configuration fails after Installation
            return FALSE;
        }
    }
    
    /**
     * set application state
     *
     * @param   array   $_applicationIds application ids to set new state for
     * @param   string  $state the new state
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setApplicationState($_applicationIds, $state)
    {
        if (!in_array($state, array(Tinebase_Application::ENABLED, Tinebase_Application::DISABLED))) {
            throw new Tinebase_Exception_InvalidArgument('$_state can be only Tinebase_Application::DISABLED  or Tinebase_Application::ENABLED');
        }
        
        if ($_applicationIds instanceof Tinebase_Model_Application ||
            $_applicationIds instanceof Tinebase_Record_RecordSet
        ) {
            $applicationIds = (array)$_applicationIds->getId();
        } else {
            $applicationIds = (array)$_applicationIds;
        }
        
        $data = array(
            'status' => $state
        );
        
        $affectedRows = $this->_getBackend()->updateMultiple($applicationIds, $data);
        
        if ($affectedRows === count($applicationIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Disabled/Enabled ' . $affectedRows . ' applications.');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Could not set state for all requested applications: ' . print_r($applicationIds, TRUE));
        }
        
        $this->_cleanCache();
    }
    
    /**
     * add new appliaction 
     *
     * @param Tinebase_Model_Application $application the new application object
     * @return Tinebase_Model_Application the new application with the applicationId set
     */
    public function addApplication(Tinebase_Model_Application $application)
    {
        $application = $this->_getBackend()->create($application);
        
        $this->_cleanCache();
        
        return $application;
    }
    
    /**
     * get all possible application rights
     *
     * @param   int application id
     * @return  array   all application rights
     */
    public function getAllRights($_applicationId)
    {
        $application = $this->getApplicationById($_applicationId);
        
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
    public function getAllRightDescriptions($_applicationId)
    {
        $application = $this->getApplicationById($_applicationId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Getting right descriptions for ' . $application->name );
        
        // call getAllApplicationRights for application (if it has specific rights)
        $appAclClassName = $application->name . '_Acl_Rights';
        if (! @class_exists($appAclClassName)) {
            $appAclClassName = 'Tinebase_Acl_Rights';
            $function = 'getTranslatedBasicRightDescriptions';
        } else {
            $function = 'getTranslatedRightDescriptions';
        }
        
        $descriptions = call_user_func(array($appAclClassName, $function));
        
        return $descriptions;
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
        
        $select = $this->_getDb()->select()
            ->from(SQL_TABLE_PREFIX . 'application_tables', array('name'))
            ->where($this->_getDb()->quoteIdentifier('application_id') . ' = ?', $applicationId);
            
        $stmt = $this->_getDb()->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        return $rows;
    }
    
    /**
     * get models for an application
     * @param string $applicationId
     */
    public function getApplicationModels($applicationId)
    {
        return $this->getApplicationById($applicationId)->getModels();
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
            $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('application_id') . '= ?', $applicationId),
            $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('name') . '= ?', $_tableName)
        );
        
        $this->_getDb()->delete(SQL_TABLE_PREFIX . 'application_tables', $where);
    }
    
    /**
     * reset class cache
     * 
     * @param string $key
     * @return Tinebase_Acl_Roles
     */
    public function resetClassCache($key = null)
    {
        foreach ($this->_classCache as $cacheKey => $cacheValue) {
            if ($key === null || $key === $cacheKey) {
                $this->_classCache[$cacheKey] = array();
            }
        }
        
        return $this;
    }
    
    /**
     * remove application from applications table
     *
     * @param Tinebase_Model_Application|string $_applicationId the applicationId
     */
    public function deleteApplication($_applicationId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Removing app ' . $_applicationId . ' from applications table.');
        
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $this->_cleanCache();
        
        $this->_getBackend()->delete($applicationId);
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
            'application_id' => $applicationId,
            'name'           => $_name,
            'version'        => $_version
        );
        
        $this->_getDb()->insert(SQL_TABLE_PREFIX . 'application_tables', $applicationData);
    }
    
    /**
     * update application
     * 
     * @param Tinebase_Model_Application $_application
     * @return Tinebase_Model_Application
     */
    public function updateApplication(Tinebase_Model_Application $_application)
    {
        $result = $this->_getBackend()->update($_application);
        
        $this->_cleanCache();
        
        return $result;
    }
    
    /**
     * delete containers, configs and other data of an application
     * 
     * NOTE: if a table with foreign key constraints to applications is added, we need to make sure that the data is deleted here 
     * 
     * @param Tinebase_Model_Application $_applicationName
     * @return void
     */
    public function removeApplicationData(Tinebase_Model_Application $_application)
    {
        $dataToDelete = array(
            'container'     => array('tablename' => ''),
            'config'        => array('tablename' => ''),
            'customfield'   => array('tablename' => ''),
            'rights'        => array('tablename' => 'role_rights'),
            'definitions'   => array('tablename' => 'importexport_definition'),
            'filter'        => array('tablename' => 'filter'),
            'modlog'        => array('tablename' => 'timemachine_modlog'),
            'import'        => array('tablename' => 'import')
        );
        $countMessage = ' Deleted';
        
        $where = array(
            $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('application_id') . '= ?', $_application->getId())
        );
        
        foreach ($dataToDelete as $dataType => $info) {
            switch ($dataType) {
                case 'container':
                    $count = Tinebase_Container::getInstance()->deleteContainerByApplicationId($_application->getId());
                    break;
                case 'config':
                    $count = Tinebase_Config::getInstance()->deleteConfigByApplicationId($_application->getId());
                    break;
                  case 'customfield':
                      $count = Tinebase_CustomField::getInstance()->deleteCustomFieldsForApplication($_application->getId());
                      break;
                default:
                    if ((isset($info['tablename']) || array_key_exists('tablename', $info)) && ! empty($info['tablename'])) {
                        try {
                            $count = $this->_getDb()->delete(SQL_TABLE_PREFIX . $info['tablename'], $where);
                        } catch (Zend_Db_Statement_Exception $zdse) {
                            Tinebase_Exception::log($zdse);
                            $count = 0;
                        }
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No tablename defined for ' . $dataType);
                        $count = 0;
                    }
            }
            $countMessage .= ' ' . $count . ' ' . $dataType . '(s) /';
        }
        
        $countMessage .= ' for application ' . $_application->name;
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . $countMessage);
    }
    
    /**
     * remove TMA from "in class" cache and Zend Cache
     * 
     * @return void
     */
    protected function _cleanCache()
    {
        $cache = Tinebase_Core::getCache();
        
        if ($cache instanceof Zend_Cache_Core) {
            $cache->remove('getApplications_allApplications');
        }
        
        $this->resetClassCache();
    }
    
    /**
     * 
     * @return Tinebase_Backend_Sql
     */
    protected function _getBackend()
    {
        if (!isset($this->_backend)) {
            $this->_backend = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_Application', 
                'tableName' => 'applications'
            ), $this->_getDb());
        }
        
        return $this->_backend;
    }
    
    /**
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getDb()
    {
        if (!isset($this->_db)) {
            $this->_db = Tinebase_Core::getDb();
        }
        
        return $this->_db;
    }
}
