<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
    use Tinebase_Controller_Record_ModlogTrait;

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

    const STATE_ACTION_QUEUE_LR_LAST_DURATION = 'actionQueueLRLastDuration';
    const STATE_ACTION_QUEUE_LR_LAST_DURATION_UPDATE = 'actionQueueLRLastDurationUpdate';
    const STATE_ACTION_QUEUE_LR_LAST_JOB_CHANGE = 'actionQueueLastJobChange';
    const STATE_ACTION_QUEUE_LR_LAST_JOB_ID = 'actionQueueLastJobId';
    const STATE_ACTION_QUEUE_LAST_DURATION = 'actionQueueLastDuration';
    const STATE_ACTION_QUEUE_LAST_DURATION_UPDATE = 'actionQueueLastDurationUpdate';
    const STATE_ACTION_QUEUE_LAST_JOB_CHANGE = 'actionQueueLastJobChange';
    const STATE_ACTION_QUEUE_LAST_JOB_ID = 'actionQueueLastJobId';
    const STATE_ACTION_QUEUE_STATE = 'actionQueueState';
    const STATE_FILESYSTEM_ROOT_REVISION_SIZE = 'filesystemRootRevisionSize';
    const STATE_FILESYSTEM_ROOT_SIZE = 'filesystemRootSize';
    const STATE_REPLICATION_MASTER_ID = 'replicationMasterId';
    const STATE_REPLICATION_PRIMARY_TB_ID = 'replicationPrimaryTBId';
    const STATE_UPDATES = 'updates';


    /**
     * Table name
     *
     * @var string
     */
    protected $_tableName = 'applications';
    
    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Application';
    
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
     * @param bool $boolean
     */
    public function omitModLog($boolean)
    {
        $this->_omitModLog = (bool)$boolean;
    }

    public static function destroyInstance()
    {
        self::$instance = null;
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

        /** @var Tinebase_Model_Application $application */
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

        $applications = $this->getApplications();
        if ($applications) {
            $application = $applications->find('name', $_applicationName);
        } else {
            $application = false;
        }
        
        if (!$application) {
            throw new Tinebase_Exception_NotFound("Application $_applicationName not found.");
        }

        /** @var Tinebase_Model_Application $application */
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
            try {
                return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__,
                    'allApplications', Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
        
        $result = $this->_getBackend()->search($filter, $pagination);
        
        if ($filter === null && $pagination === null) {
            // cache result in persistent shared cache too
            // cache will be cleared, when an application will be added or updated
            Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, 'allApplications', $result, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        }
        
        return $result;
    }

    public function clearCache()
    {
        Tinebase_Cache_PerRequest::getInstance()->reset(__CLASS__, __CLASS__ . '::getApplications', 'allApplications');
    }

    /**
     * get enabled or disabled applications
     *
     * @param  string  $state  can be Tinebase_Application::ENABLED or Tinebase_Application::DISABLED
     * @return Tinebase_Record_RecordSet list of applications
     * @throws Tinebase_Exception_InvalidArgument
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
     * get hash of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return string
     */
    public function getApplicationsHash($_filter = NULL, $_sort = null, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $applications = $this->getApplications($_filter, $_sort, $_dir, $_start, $_limit);
        
        // create a hash of installed applications and their versions
        $applications = array_combine(
            $applications->id,
            $applications->version
        );
        
        ksort($applications);
        
        return Tinebase_Helper::arrayHash($applications, true);
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
     * @param   string|array|Tinebase_Model_Application|Tinebase_Record_RecordSet   $_applicationIds application ids to set new state for
     * @param   string  $state the new state
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setApplicationStatus($_applicationIds, $state)
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
        
        $this->resetClassCache();
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
        
        $this->resetClassCache();

        $this->_writeModLog($application, null);

        /** @var Tinebase_Model_Application $application */
        return $application;
    }
    
    /**
     * get all possible application rights
     *
     * @param   int $_applicationId
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
     * @param   int     $_applicationId
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
     * @param string $method
     * @return Tinebase_Application
     */
    public function resetClassCache($method = null)
    {
        Tinebase_Cache_PerRequest::getInstance()->reset(__CLASS__, $method);
        
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

        if ($_applicationId instanceof Tinebase_Model_Application) {
            $application = $_applicationId;
        } else {
            $application = $this->getApplicationById($applicationId);
        }
        
        $this->resetClassCache();
        
        $this->_getBackend()->delete($applicationId);

        $this->_writeModLog(null, $application);
    }
    
    /**
     * add table to tine registry
     *
     * @param Tinebase_Model_Application|string $_applicationId
     * @param string $_name of table
     * @param int $_version of table
     * @return void
     */
    public function addApplicationTable($_applicationId, $_name, $_version)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Add application table: ' . $_name);

        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $applicationData = array(
            'application_id' => $applicationId,
            'name'           => $_name,
            'version'        => $_version
        );
        
        $this->_getDb()->insert(SQL_TABLE_PREFIX . 'application_tables', $applicationData);
    }

    /**
     * gets the current application state
     * we better do a select for update always
     *
     * @param mixed $_applicationId
     * @param string $_stateName
     * @param bool $_forUpdate
     * @return null|string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getApplicationState($_applicationId, $_stateName, $_forUpdate = false)
    {
        $id = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);

        $db = $this->_getDb();
        $result = $db->select()->forUpdate($_forUpdate)->from(SQL_TABLE_PREFIX . 'application_states', 'state')->where(
            $db->quoteIdentifier('id') . $db->quoteInto(' = ? AND ', $id) .
            $db->quoteIdentifier('name') . $db->quoteInto(' = ?', $_stateName))->query()
                ->fetchColumn(0);
        if (false === $result) {
            return null;
        }
        return $result;
    }

    /**
     * @param $_applicationId
     * @param $_stateName
     * @param $_state
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Adapter_Exception
     */
    public function setApplicationState($_applicationId, $_stateName, $_state)
    {
        $id = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);

        $db = $this->_getDb();
        if (null === $this->getApplicationState($id, $_stateName)) {
            $db->insert(SQL_TABLE_PREFIX . 'application_states',
                [
                    'id' => $id,
                    'name' => $_stateName,
                    'state' => $_state
                ]);
        } else {
            $db->update(SQL_TABLE_PREFIX . 'application_states', ['state' => $_state], $db->quoteIdentifier('id') .
                $db->quoteInto(' = ?', $id) . ' AND ' . $db->quoteIdentifier('name') .
                $db->quoteInto(' = ?', $_stateName));
        }
    }

    /**
     * @param $_applicationId
     * @param $_stateName
     * @param $_state
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Adapter_Exception
     */
    public function deleteApplicationState($_applicationId, $_stateName)
    {
        $id = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);

        $db = $this->_getDb();
        $db->delete(SQL_TABLE_PREFIX . 'application_states',
            $db->quoteIdentifier('id') . $db->quoteInto(' = ? AND ', $id) .
            $db->quoteIdentifier('name') . $db->quoteInto(' = ?', $_stateName));
    }

    /**
     * update application
     * 
     * @param Tinebase_Model_Application $_application
     * @return Tinebase_Model_Application
     */
    public function updateApplication(Tinebase_Model_Application $_application)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Update application: ' . print_r($_application->toArray(), true));

        $result = $this->_getBackend()->update($_application);
        
        $this->resetClassCache();

        /** @var Tinebase_Model_Application $result */
        return $result;
    }
    
    /**
     * delete containers, configs and other data of an application
     * ATTENTION this does NOT delete the application data itself! only auxiliary data
     * 
     * NOTE: if a table with foreign key constraints to applications is added, we need to make sure that the data is deleted here 
     * 
     * @param Tinebase_Model_Application $_application
     */
    public function removeApplicationAuxiliaryData(Tinebase_Model_Application $_application)
    {
        $dataToDelete = array(
            'container'     => array('tablename' => ''),
            'config'        => array('tablename' => ''),
            'customfield'   => array('tablename' => ''),
            'rights'        => array('tablename' => 'role_rights'),
            'definitions'   => array('tablename' => 'importexport_definition'),
            'filter'        => array('tablename' => 'filter'),
            'modlog'        => array('tablename' => 'timemachine_modlog'),
            'import'        => array('tablename' => 'import'),
            'rootnode'      => array('tablename' => ''),
            'pobserver'     => array(),
        );
        $countMessage = ' Deleted';
        
        $where = array(
            $this->_getDb()->quoteInto($this->_getDb()->quoteIdentifier('application_id') . '= ?', $_application->getId())
        );
        
        foreach ($dataToDelete as $dataType => $info) {
            switch ($dataType) {
                case 'container':
                    $count = Tinebase_Container::getInstance()->dropContainerByApplicationId($_application->getId());
                    break;
                case 'config':
                    $count = Tinebase_Config::getInstance()->deleteConfigByApplicationId($_application->getId());
                    break;
                case 'customfield':
                    try {
                        $count = Tinebase_CustomField::getInstance()->deleteCustomFieldsForApplication($_application->getId());
                    } catch (Exception $e) {
                        Tinebase_Exception::log($e);
                        $count = 0;
                    }
                    break;
                case 'pobserver':
                    $count = Tinebase_Record_PersistentObserver::getInstance()->deleteByApplication($_application);
                    break;
                case 'rootnode':
                    $count = 0;
                    try {
                        $tries = 0;
                        while (Tinebase_FileSystem::getInstance()->isDir($_application->name) && ++$tries < 10) {
                            // note: TFS expects name here, not ID
                            $count += (int)Tinebase_FileSystem::getInstance()->rmdir($_application->name, true);
                        }
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // nothing to do
                        Tinebase_Exception::log($tenf);
                    } catch (Tinebase_Exception_Backend $teb) {
                        // nothing to do
                        Tinebase_Exception::log($teb);
                    } catch (Throwable $e) {
                        // problem!
                        Tinebase_Exception::log($e);
                    }
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

    public function resetBackend()
    {
        $this->_backend = null;
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

    /**
     * returns the Models of all enabled (or all installed) applications
     * uses Tinebase_Application::getApplicationsByState
     * and Tinebase_Controller_Abstract::getModels
     *
     * @return array
     */
    public function getModelsOfAllApplications($allApps = false)
    {
        $models = array();

        if ($allApps) {
            $apps = $this->getApplications();
        } else {
            $apps = $this->getApplicationsByState(Tinebase_Application::ENABLED);
        }

        /** @var Tinebase_Model_Application $app */
        foreach ($apps as $app) {
            /** @var Tinebase_Controller $controllerClass */
            $controllerClass = $app->name . '_Controller';
            if (!class_exists(($controllerClass))) {
                try {
                    $controllerInstance = Tinebase_Core::getApplicationInstance($app->name, '', true);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    continue;
                } catch (Tinebase_Exception_AccessDenied $tead) {
                    continue;
                }
            } else {
                $controllerInstance = $controllerClass::getInstance();
            }

            $appModels = $controllerInstance->getModels();
            if (is_array($appModels)) {
                $models = array_merge($models, $appModels);
            }
        }

        return $models;
    }

    /**
     * extract model and app name from model name
     *
     * @param mixed $modelOrApplication
     * @param null $model
     * @return array
     */
    public static function extractAppAndModel($modelOrApplication, $model = null)
    {
        if (! $modelOrApplication instanceof Tinebase_Model_Application && $modelOrApplication instanceof Tinebase_Record_Interface) {
            $modelOrApplication = get_class($modelOrApplication);
        }

        // modified (some model names can have both . and _ in their names and we should treat them as JS model name
        if (strpos($modelOrApplication, '_') && ! strpos($modelOrApplication, '.')) {
            // got (complete) model name name as first param
            list($appName, /*$i*/, $modelName) = explode('_', $modelOrApplication, 3);
        } else if (strpos($modelOrApplication, '.')) {
            // got (complete) model name name as first param (JS style)
            list(/*$j*/, $appName, /*$i*/, $modelName) = explode('.', $modelOrApplication, 4);
        } else {
            $appName = $modelOrApplication;
            $modelName = $model;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Extracted appName: ' . $appName . ' modelName: ' . $modelName);

        return array(
            'appName'   => $appName,
            'modelName' => $modelName
        );
    }

    /**
     * apply modification logs from a replication master locally
     *
     * @param Tinebase_Model_ModificationLog $_modification
     * @throws Tinebase_Exception
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification)
    {

        switch ($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $model = $_modification->record_type;
                /** @var Tinebase_Model_Application $record */
                $record = new $model($diff->diff);

                // close transaction open in \Tinebase_Timemachine_ModificationLog::applyReplicationModLogs
                Tinebase_TransactionManager::getInstance()->rollBack();
                Setup_Core::set(Setup_Core::CHECKDB, true);
                Setup_Controller::destroyInstance();
                Setup_Controller::getInstance()->installApplications([$record->getId() => $record->name],
                    [Setup_Controller::INSTALL_NO_IMPORT_EXPORT_DEFINITIONS => true,
                        Setup_Controller::INSTALL_NO_REPLICATION_SLAVE_CHECK => true]);
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $model = $_modification->record_type;
                /** @var Tinebase_Model_Application $record */
                $record = new $model($diff->oldData);

                // close transaction open in \Tinebase_Timemachine_ModificationLog::applyReplicationModLogs
                Tinebase_TransactionManager::getInstance()->rollBack();
                Setup_Core::set(Setup_Core::CHECKDB, true);
                Setup_Controller::destroyInstance();
                Setup_Controller::getInstance()->uninstallApplications([$record->name], [
                    Setup_Controller::INSTALL_NO_REPLICATION_SLAVE_CHECK => true
                ]);
                break;

            default:
                throw new Tinebase_Exception('unsupported Tinebase_Model_ModificationLog->change_type: ' . $_modification->change_type);
        }
    }

    public function getAllApplicationGrantModels($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);

        try {
            return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__, $applicationId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {}

        $grantModels = [];
        $application = $this->getApplicationById($applicationId);
        /** @var DirectoryIterator $file */
        foreach (new DirectoryIterator(dirname(__DIR__) . '/' . $application->name . '/Model') as $file) {
            if ($file->isFile() && strpos($file->getFilename(), 'Grants.php') > 0) {
                $grantModel = $application->name . '_Model_' . substr($file->getFilename(), 0, -4);
                if (class_exists($grantModel)) {
                    $grantModels[] = $grantModel;
                }
            }
        }

        Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, $applicationId, $grantModels, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        return $grantModels;
    }
}
