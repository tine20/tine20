<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        refactor that: remove code duplication, remove Zend_Db_Table_Abstract usage, use standard record controller/backend functions
 *              -> make use of Tinebase_Backend_Sql_Grants
 * @todo        move (or replace from) functions to backend
 * @todo        switch containers to hash ids
 */

/**
 * this class handles access rights(grants) to containers
 * 
 * any record in Tine 2.0 is tied to a container. the rights of an account on a record gets 
 * calculated by the grants given to this account on the container holding the record (if you know what i mean ;-))
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Container extends Tinebase_Backend_Sql_Abstract implements Tinebase_Controller_SearchInterface, Tinebase_Container_Interface
{
    use Tinebase_Controller_Record_ModlogTrait;

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'container';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Container';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * the table object for the container_acl table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_containerAclTable;
    
    /**
     * container content history backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_contentBackend = NULL;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Container
     */
    private static $_instance = NULL;
    
    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';

    /**
     * whether or not to execute _addSearchAclFilter
     *
     * @var bool
     */
    protected $_doSearchAclFilter = true;

    /**
     * cache timeout for ACL related cache entries (in seconds)
     * 
     * @see 0007266: make groups / group memberships cache cleaning more efficient
     * @var integer
     */
    const ACL_CACHE_TIMEOUT = 30;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Container();
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * get content backend
     * 
     * @return Tinebase_Backend_Sql
     * 
     * @todo move this to constructor when this no longer extends Tinebase_Backend_Sql_Abstract
     */
    public function getContentBackend()
    {
        if ($this->_contentBackend === NULL) {
            $this->_contentBackend  = new Tinebase_Backend_Sql(array(
                'modelName' => 'Tinebase_Model_ContainerContent', 
                'tableName' => 'container_content',
            ));
        }
        
        return $this->_contentBackend;
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string $_action
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->_addSearchAclFilter($_filter);

        return parent::search($_filter, $_pagination, $_onlyIds);
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $this->_addSearchAclFilter($_filter);

        return parent::searchCount($_filter);
    }

    /**
     * sets _doSearchAclFilter, returns old value. Pass null to not set, just get
     *
     * @param bool $bool
     * @return bool
     */
    public function doSearchAclFilter($bool = true)
    {
        $oldValue = $this->_doSearchAclFilter;
        if (null !== $bool) {
            $this->_doSearchAclFilter = (bool)$bool;
        }
        return $oldValue;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _addSearchAclFilter(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        if (true !== $this->_doSearchAclFilter) {
            return;
        }

        if (! $_filter instanceof Tinebase_Model_ContainerFilter) {
            throw new Tinebase_Exception_InvalidArgument('filter is expected to be instanceof Tinebase_Model_ContainerFilter');
        }
        if (count($_filter->getFilter('application_id', true)) !== 1 ||
            null === ($applicationFilter = $_filter->getFilter('application_id')) ||
            $applicationFilter->getOperator() !== 'equals') {
            throw new Tinebase_Exception_InvalidArgument('filter needs to contain exactly 1 application_id equals filter');
        }
        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            throw new Tinebase_Exception_InvalidArgument('outermost filter needs to be of condition AND');
        }
        $_filter->addFilter(new Tinebase_Model_Filter_Id('id', 'in', $this->getContainersByApplicationId(
            $applicationFilter->getValue(), Tinebase_Core::getUser()->getId(), Tinebase_Model_Grants::GRANT_READ)
            ->getArrayOfIds()));
    }

    /**
     * creates a new container
     *
     * @param   Tinebase_Model_Container $_container the new container
     * @param   Tinebase_Record_RecordSet $_grants the grants for the new folder 
     * @param   bool  $_ignoreAcl
     * @return  Tinebase_Model_Container the newly created container
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addContainer(Tinebase_Model_Container $_container, $_grants = NULL, $_ignoreAcl = FALSE)
    {
        $_container->isValid(TRUE);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' Add new container: ' . print_r($_container->toArray(), true) . ' with the following grants: '
            . print_r($_grants instanceof Tinebase_Record_RecordSet ? $_grants->toArray() : $_grants, true));
        
        if ($_ignoreAcl !== TRUE) {
            switch ($_container->type) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    // is the user allowed to create personal container?
                    break;
                    
                case Tinebase_Model_Container::TYPE_SHARED:
                    $application = Tinebase_Application::getInstance()->getApplicationById($_container->application_id);
                    $appName = (string) $application;
                    $manageRight = FALSE;
                    
                    // check for MANAGE_SHARED_FOLDERS right
                    $appAclClassName = $appName . '_Acl_Rights';
                    if (@class_exists($appAclClassName)) {
                        $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
                        $allRights = $appAclObj->getAllApplicationRights();
                        if (in_array(Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS, $allRights)) {
                            $manageRight = Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS);
                        }
                    }
                    

                    if (!$manageRight && !Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::ADMIN)) {
                        throw new Tinebase_Exception_AccessDenied('Permission to add shared container denied.');
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_InvalidArgument('Can add personal or shared folders only when ignoring ACL.');
                    break;
            }
        }
        
        if (! empty($_container->owner_id)) {
            $accountId = $_container->owner_id instanceof Tinebase_Model_User ? $_container->owner_id->getId() : $_container->owner_id;
        } else {
            $accountId = (is_object(Tinebase_Core::getUser())) ? Tinebase_Core::getUser()->getId() : NULL;
            if ($_container->type === Tinebase_Model_Container::TYPE_PERSONAL) {
                $_container->owner_id = $accountId;
            }
        }
        
        if($_grants === NULL || count($_grants) == 0) {
            // TODO fetch personal grants depending on grants/record model
            $grants = Tinebase_Model_Grants::getPersonalGrants($accountId);
            if (    $_container->type === Tinebase_Model_Container::TYPE_SHARED
                 && ! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
    
                // add all grants to creator and
                // add read grants to any other user
                $grants->addRecord(new Tinebase_Model_Grants(array(
                    'account_id'      => '0',
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Tinebase_Model_Grants::GRANT_READ    => true,
                    Tinebase_Model_Grants::GRANT_EXPORT  => true,
                    Tinebase_Model_Grants::GRANT_SYNC    => true,
                ), TRUE));
            }
        } else {
            $grants = $_grants;
        }
        
        $event = new Tinebase_Event_Container_BeforeCreate();
        $event->accountId = $accountId;
        $event->container = $_container;
        $event->grants = $grants;
        Tinebase_Event::fireEvent($event);
        
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_container, 'create');
        $container = $this->create($_container);
        Tinebase_Notes::getInstance()->addSystemNote($container, Tinebase_Core::getUser());
        $this->setGrants($container->getId(), $event->grants, TRUE, FALSE);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
            . __LINE__ . ' Created new ' . $_container->type . ' container for account id ' . $accountId
            . ' with container_id ' . $container->getId());

        return $container;
    }

    /**
     * do something after creation of record
     *
     * @param Tinebase_Record_Interface $_newRecord
     * @param Tinebase_Record_Interface $_recordToCreate
     * @return void
     */
    protected function _inspectAfterCreate(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_recordToCreate)
    {
        $this->_writeModLog($_newRecord, null);
    }

    /**
     * add grants to container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param $_accountType
     * @param   int $_accountId
     * @param   array $_grants list of grants to add
     * @param bool $_ignoreAcl
     * @return  boolean
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function addGrants($_containerId, $_accountType, $_accountId, array $_grants, $_ignoreAcl = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        
        if($_ignoreAcl !== TRUE and !$this->hasGrant(Tinebase_Core::getUser(), $_containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to manage grants on container denied.');
        }
        
        switch($_accountType) {
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                $accountId = Tinebase_Model_Group::convertGroupIdToInt($_accountId);
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                $accountId = '0';
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                $accountId = Tinebase_Model_Role::convertRoleIdToInt($_accountId);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('invalid $_accountType');
                break;
        }
        
        $containerGrants = $this->getGrantsOfContainer($containerId, TRUE);
        $containerGrants->addIndices(array('account_type', 'account_id'));
        $existingGrants = $containerGrants->filter('account_type', $_accountType)->filter('account_id', $accountId)
            ->getFirstRecord();
        
        foreach($_grants as $grant) {
            if ($existingGrants === NULL || ! $existingGrants->{$grant}) {
                $data = array(
                    'id'            => Tinebase_Record_Abstract::generateUID(),
                    'container_id'  => $containerId,
                    'account_type'  => $_accountType,
                    'account_id'    => $accountId,
                    'account_grant' => $grant
                );
                $this->_getContainerAclTable()->insert($data);
            }
        }

        $newGrants = $this->getGrantsOfContainer($containerId, true);
        $this->_writeModLog(
            new Tinebase_Model_Container(array('id' => $containerId, 'account_grants' => $newGrants), true),
            new Tinebase_Model_Container(array('id' => $containerId, 'account_grants' => $containerGrants), true)
        );

        $this->_setRecordMetaDataAndUpdate($containerId, 'update');
        
        return true;
    }
    
    /**
     * resolves the $_recordClass argument for legacy handling: $_recordClass was before $_application
     * in getDefaultContainer and getPersonalContainer
     * @param string|Tinebase_Record_Interface $_recordClass
     * @throws Tinebase_Exception_InvalidArgument
     * @return array
     */
    protected function _resolveRecordClassArgument($_recordClass)
    {
        $ret = array();
        if(is_string($_recordClass)) {
            $split = explode('_', $_recordClass);
            switch (count($split)) {
                case 1:
                    // only app name given - check if it has a default model
                    $defaultModel = Tinebase_Core::getApplicationInstance('Calendar')->getDefaultModel();
                    if ($defaultModel) {
                        $ret['appName'] = $split[0];
                        $ret['recordClass'] = $defaultModel;
                    } else {
                        throw new Tinebase_Exception_InvalidArgument(
                            'Using application name is deprecated and no default model found. Use the classname of the model itself.');
                    }
                case 3:
                    $ret['appName'] = $split[0];
                    $ret['recordClass'] = $_recordClass;
                    break;
                default: throw new Tinebase_Exception_InvalidArgument('Invalid value as recordClass given: ' . print_r($_recordClass, 1));
            }
        } elseif (in_array('Tinebase_Record_Interface', class_implements($_recordClass))) {
            $ret['appName'] = $_recordClass->getApplication();
            $ret['recordClass'] = get_class($_recordClass);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Invalid value as recordClass given: ' . print_r($_recordClass, 1));
        }
        
        return $ret;
    }
    
    /**
     * set modified timestamp for container
     * 
     * @param int|Tinebase_Model_Container $container
     * @param string                       $action    one of {create|update|delete}
     * @param boolean $fireEvent
     * @return Tinebase_Model_Container
     */
    protected function _setRecordMetaDataAndUpdate($container, $action, $fireEvent = true)
    {
        if (! $container instanceof Tinebase_Model_Container) {
            $container = $this->getContainerById($container);
        }
        Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($container, $action, $container);
        
        $this->_clearCache($container);
        
        return $this->update($container, true, $fireEvent);
    }

    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param   string|Tinebase_Model_User          $accountId
     * @param   string                              $recordClass
     * @param   array|string                        $grant
     * @param   bool                                $onlyIds return only ids
     * @param   bool                                $ignoreACL
     * @return  Tinebase_Record_RecordSet|array
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerByACL($accountId, $recordClass, $grant, $onlyIds = FALSE, $ignoreACL = FALSE)
    {
        // legacy handling
        $meta = $this->_resolveRecordClassArgument($recordClass);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' app: ' . $meta['appName'] . ' / account: ' . $accountId . ' / grant:' . implode('/', (array)$grant));
        
        $accountId     = Tinebase_Model_User::convertUserIdToInt($accountId);
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($meta['appName'])->getId();
        $grant         = $ignoreACL ? '*' : $grant;
        
        // always bring values in the same order for $classCacheId
        $sortedGrant = $grant;
        if (is_array($sortedGrant)) {
            sort($sortedGrant);
        }
        
        $classCacheId = $accountId . $applicationId . implode('', (array)$sortedGrant) . (int)$onlyIds . (int)$ignoreACL;
        
        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }
        
        $select = $this->_getSelect($onlyIds ? 'id' : '*')
            ->distinct()
            ->joinLeft(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $applicationId);

        $this->addGrantsSql($select, $accountId, $grant);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        
        if ($onlyIds) {
            $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        } else {
            $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC), true);
        }
        
        // any account should have at least one personal folder
        // @todo add test for empty case
        if (empty($result)) {
            $personalContainer = $this->getDefaultContainer($recordClass, $accountId);
            if ($personalContainer instanceof Tinebase_Model_Container) {
                $result = ($onlyIds) ? 
                    array($personalContainer->getId()) : 
                    new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer), TRUE);
            }
        }
        
        $this->saveInClassCache(__FUNCTION__, $classCacheId, $result);
        
        return $result;
    }
    
    /**
     * return a container by containerId
     * - cache the results because this function is called very often
     *
     * @todo what about grant checking here???
     * 
     * @param   int|Tinebase_Model_Container $_containerId the id of the container
     * @param   bool                         $_getDeleted get deleted records
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function getContainerById($_containerId, $_getDeleted = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        
        $cacheId = $containerId . 'd' . (int)$_getDeleted;
        
        try {
            $container = $this->loadFromClassCache(__FUNCTION__, $cacheId,
                Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
            if (! $container instanceof Tinebase_Model_Container) {
                throw new Tinebase_Exception_UnexpectedValue('did not get a container from cache!');
            }
            return $container;
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        /** @var Tinebase_Model_Container $result */
        $result = $this->get((string)$containerId, $_getDeleted);
        
        $this->saveInClassCache(__FUNCTION__, $cacheId, $result, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);

        return $result;
    }
    
    /**
     * return a container identified by path
     *
     * @param   string  $_path        the path to the container
     * @param   bool    $_getDeleted  get deleted records
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getByPath($_path, $_getDeleted = FALSE)
    {
        if (($containerId = Tinebase_Model_Container::pathIsContainer($_path) === false)) {
            throw new Tinebase_Exception_UnexpectedValue ("Invalid path $_path supplied.");
        }
    
        return $this->getContainerById($containerId, $_getDeleted);
    }
    
    /**
     * return a container by container name
     *
     * @param   string                             $recordClass
     * @param   int|Tinebase_Model_Container       $containerName
     * @param   string                             $type
     * @param   string                             $ownerId
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
     *
     * TODO needs fixing, you dont get container by application name, maybe by models (yet maybe we want to have
     * TODO containers containing multiple models at some point? ... needs a proper uniqueness here!)
     */
    public function getContainerByName($recordClass, $containerName, $type, $ownerId = NULL)
    {
        // legacy handling
        $meta = $this->_resolveRecordClassArgument($recordClass);

        if (! in_array($type, array(Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Model_Container::TYPE_SHARED))) {
            throw new Tinebase_Exception_UnexpectedValue ("Invalid type $type supplied.");
        }
        
        if ($type == Tinebase_Model_Container::TYPE_PERSONAL && empty($ownerId)) {
            throw new Tinebase_Exception_UnexpectedValue ('$ownerId can not be empty for personal folders');
        }
        
        $ownerId = $ownerId instanceof Tinebase_Model_User ? $ownerId->getId() : $ownerId;
        
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($meta['appName'])->getId();

        $select = $this->_getSelect()
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $applicationId)
            ->where("{$this->_db->quoteIdentifier('container.name')} = ?", $containerName)
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", $type)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE);

        if ($type == Tinebase_Model_Container::TYPE_PERSONAL) {
            $select->where("{$this->_db->quoteIdentifier('owner_id')} = ?", $ownerId);
        }
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $containersData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (count($containersData) == 0) {
            throw new Tinebase_Exception_NotFound("Container $containerName not found.");
        }
        if (count($containersData) > 1) {
            throw new Tinebase_Exception_Duplicate("Container $containerName name duplicate.");
        }

        $container = new Tinebase_Model_Container($containersData[0]);
        
        return $container;
    }
    
    /**
     * returns the personal container of a given account accessible by a another given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Record_Interface    $_recordClass
     * @param   int|Tinebase_Model_User             $_owner
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getPersonalContainer($_accountId, $_recordClass, $_owner, $_grant = Tinebase_Model_Grants::GRANT_READ, $_ignoreACL = false)
    {
        $meta = $this->_resolveRecordClassArgument($_recordClass);
        
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $ownerId     = Tinebase_Model_User::convertUserIdToInt($_owner);
        $grant       = $_ignoreACL ? '*' : $_grant;
        $application = Tinebase_Application::getInstance()->getApplicationByName($meta['appName']);

        $classCacheId = $accountId .
                        $application->getId() .
                        ($meta['recordClass'] ? $meta['recordClass'] : null) .
                        $ownerId .
                        implode('', (array)$grant) .
                        (int)$_ignoreACL;

        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        $select = $this->_db->select()
            ->distinct() // TODO needed?
            ->from(array('container' => SQL_TABLE_PREFIX . 'container'))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                /* on     */ "{$this->_db->quoteIdentifier('container.id')} = {$this->_db->quoteIdentifier('container_acl.container_id')}",
                /* select */ array()
            )

            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            ->where("{$this->_db->quoteIdentifier('container.owner_id')} = ?", $ownerId)

            ->order('container.creation_time');

        $this->addGrantsSql($select, $accountId, $grant);
        
        if ($meta['recordClass']) {
            // TODO needs fixing, you dont get container by application name, maybe by models (yet maybe we want to have
            //      containers containing multiple models at some point? ... needs a proper uniqueness here!)
            // TODO we also might fix (or remove) createPersonalFolder for Addressbook + Calendar as they always create Contact/Event containers
            if (in_array($meta['recordClass'], ['Addressbook_Model_List', 'Calendar_Model_Poll'])) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' FIXME: Lists+Contacts and Events+Polls share the containers! Implement multi-model containers');
            } else {
                $select->where("{$this->_db->quoteIdentifier('container.model')} = ?", $meta['recordClass']);
            }
        }
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $containersData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        // if no containers where found, maybe something went wrong when creating the initial folder
        // let's check if the controller of the application has a function to create the needed folders
        if (empty($containersData) && $accountId === $ownerId) {
            $application = Tinebase_Core::getApplicationInstance($meta['appName'], '', true);

            if ($application instanceof Tinebase_Application_Container_Interface && method_exists($application, 'createPersonalFolder')) {
                return $application->createPersonalFolder($accountId);
            } else if ($meta['recordClass']) {
                $containersData = array($this->createDefaultContainer($meta['recordClass'], $meta['appName'], $accountId));
            }
        }

        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $containersData, TRUE);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);

        return $containers;
    }

    /**
     * create default container for a user
     *
     * @param string $recordClass
     * @param string $applicationName
     * @param Tinebase_Model_User|string $account
     * @param string $containerName
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     *
     * @todo add record name to container name?
     */
    public function createDefaultContainer($recordClass, $applicationName, $account, $containerName = null)
    {
        if (! $account instanceof Tinebase_Model_User) {
            $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $account);
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Creating new default container ' . $containerName . ' for '
            . $account->accountFullName . ' for model ' . $recordClass);

        if (! $containerName) {
            $translation = Tinebase_Translation::getTranslation('Tinebase');
            $containerName = sprintf($translation->_("%s's personal container"), $account->accountFullName);
        }

        $container = $this->addContainer(new Tinebase_Model_Container(array(
            'name'              => $containerName,
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => $account->getId(),
            'backend'           => 'Sql',
            'model'             => $recordClass,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($applicationName)->getId()
        )));

        return $container;
    }

    /**
     * appends container_acl sql 
     * 
     * @param  Zend_Db_Select    $_select
     * @param  String            $_accountId
     * @param  Array|String      $_grant
     * @param  String            $_aclTableName (deprecated)
     * @param  bool              $_andGrants
     * @return void
     */
    public static function addGrantsSql($_select, $_accountId, $_grant, $_aclTableName = 'container_acl', $_andGrants = FALSE, $joinCallBack = null)
    {
        $accountId = $_accountId instanceof Tinebase_Record_Interface
            ? $_accountId->getId()
            : $_accountId;
        
        $db = $_select->getAdapter();
        
        $grants = is_array($_grant) ? $_grant : array($_grant);

        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        $roleMemberships    = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($accountId);
        // enforce string for pgsql
        array_walk($roleMemberships, function(&$item) {$item = (string)$item;});

        $quotedActId   = $db->quoteIdentifier("{$_aclTableName}.account_id");
        $quotedActType = $db->quoteIdentifier("{$_aclTableName}.account_type");
        
        $accountSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        $accountSelect
            ->orWhere("{$quotedActId} = ? AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER), $accountId)
            ->orWhere("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP), empty($groupMemberships) ? ' ' : $groupMemberships)
            ->orWhere("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE), empty($roleMemberships) ? ' ' : $roleMemberships);
        
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
            $accountSelect->orWhere("{$quotedActType} = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        }
        $accountSelect->appendWhere(Zend_Db_Select::SQL_AND);
        
        // we only need to filter, if the filter does not contain %
        if (!in_array('*', $grants)) {
            // @todo fetch wildcard from specific db adapter
            $grants = str_replace('*', '%', $grants);
        
            $quotedGrant   = $db->quoteIdentifier($_aclTableName . '.account_grant');

            if (null === $joinCallBack) {
                $joinCallBack = [static::class, 'addGrantsSqlCallback'];
            }
            $iteration = 0;
            $grantsSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            foreach ($grants as $grant) {
                if ($grant[0] === '(') {
                    $grantsSelect->openBracket();
                    $grant = substr($grant, 1);
                }
                if ($grant[0] === '&') {
                    ++$iteration;
                    $andGrants = true;
                    $grant = substr($grant, 1);
                } elseif ($grant[0] === '|') {
                    $andGrants = false;
                    $grant = substr($grant, 1);
                } else {
                    $andGrants = $_andGrants;
                }
                if ($grant[strlen($grant) - 1] === ')') {
                    $closeBracket = true;
                    $grant = rtrim($grant, ')');
                } else {
                    $closeBracket = false;
                }
                if ($andGrants) {
                    if ($iteration > 0) {
                        $callbackIdentifier = call_user_func($joinCallBack, $_select, $iteration);
                        $grantsSelect->where($db->quoteIdentifier($callbackIdentifier . '.account_grant') . ' LIKE ?', $grant);
                    } else {
                        $grantsSelect->where($quotedGrant . ' LIKE ?', $grant);
                    }
                    ++$iteration;
                } else {
                    $grantsSelect->orWhere($quotedGrant . ' LIKE ?', $grant);
                }
                if ($closeBracket) {
                    $grantsSelect->closeBracket();
                }
            }

            // admin grant includes all other grants
            if (! in_array(Tinebase_Model_Grants::GRANT_ADMIN, $grants)) {
                $grantsSelect->orWhere($quotedGrant . ' LIKE ?', Tinebase_Model_Grants::GRANT_ADMIN);
            }

            $grantsSelect->appendWhere(Zend_Db_Select::SQL_AND);
        }
    }

    /**
     * gets default container of given user for given app
     *  - did and still does return personal first container by using the application name instead of the recordClass name
     *  - allows now to use different models with default container in one application
     *
     * @param   string|Tinebase_Record_Interface    $recordClass
     * @param   string|Tinebase_Model_User          $accountId use current user if omitted
     * @param   string                              $defaultContainerPreferenceName
     * @return  Tinebase_Model_Container
     * 
     * @todo get default container name from app/translations?
     */
    public function getDefaultContainer($recordClass, $accountId = NULL, $defaultContainerPreferenceName = NULL)
    {
        // legacy handling
        $meta = $this->_resolveRecordClassArgument($recordClass);

        $account = ($accountId !== NULL)
            ? Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $accountId)
            : Tinebase_Core::getUser();

        if ($defaultContainerPreferenceName !== NULL) {
            $defaultContainerId = Tinebase_Core::getPreference($meta['appName'])->getValueForUser($defaultContainerPreferenceName, $account->getId());
            try {
                $result = $this->getContainerById($defaultContainerId);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Got default container from preferences: ' . $result->name);
                return $result;
            } catch (Tinebase_Exception $te) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Default container not found (' . $te->getMessage() . ')');
                // default may be gone -> remove default adb pref
                $appPref = Tinebase_Core::getPreference($meta['appName']);
                if ($appPref) {
                    $appPref->deleteUserPref($defaultContainerPreferenceName);
                }
            }
        }

        $result = $this->getPersonalContainer($account, $recordClass, $account, Tinebase_Model_Grants::GRANT_ADD)->getFirstRecord();
        
        if ($result === NULL) {
            $result = $this->createDefaultContainer($recordClass, $meta['appName'], $accountId);
        }
        
        if ($defaultContainerPreferenceName !== NULL) {
            // save as new pref
            Tinebase_Core::getPreference($meta['appName'])->setValue($defaultContainerPreferenceName, $result->getId());
        }

        return $result;
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Record_Interface    $recordClass
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @param   bool                                $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getSharedContainer($_accountId, $recordClass, $_grant, $_ignoreACL = FALSE, $_andGrants = FALSE)
    {
        // legacy handling
        $meta = $this->_resolveRecordClassArgument($recordClass);
        $application = Tinebase_Application::getInstance()->getApplicationByName($meta['appName']);
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $grant       = $_ignoreACL ? '*' : $_grant;
        
        $classCacheId = Tinebase_Helper::convertCacheId(
            $accountId .
            $application->getId() .
            implode('', (array)$grant) .
            (int)$_ignoreACL .
            (int)$_andGrants
        );

        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        $select = $this->_getSelect(array(self::ALLCOL, "CONCAT(COALESCE(hierarchy, ''), name) as hierachyName"))
            ->distinct()
            ->joinLeft(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                array()
            )
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_SHARED);
        
        $select->order(['order', 'hierachyName']);

        $this->addGrantsSql($select, $accountId, $grant, 'container_acl', $_andGrants, __CLASS__ . '::addGrantsSqlCallback');

        /** @var Tinebase_Model_Grants $grantModel */
        foreach (Tinebase_Application::getInstance()->getAllApplicationGrantModels($application) as $grantModel) {
            $grantModel::addCustomGetSharedContainerSQL($select, $application, $accountId, $grant);
        }

        $data = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select)->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $data, TRUE);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Found ' . count($containers) . ' shared container(s) in application ' . $application->name);
        
        return $containers;
    }
    
    /**
     * return users which made personal containers accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $recordClass
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @param   bool                                $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_User
     */
    public function getOtherUsers($_accountId, $recordClass, $_grant, $_ignoreACL = FALSE, $_andGrants = FALSE)
    {
        $meta = \Tinebase_Application::extractAppAndModel($recordClass);
        $userIds = $this->_getOtherAccountIds($_accountId, $meta['appName'], $_grant, $_ignoreACL, $_andGrants);
        
        $users = Tinebase_User::getInstance()->getMultiple($userIds);
        $users->sort('accountDisplayName');
        
        return $users;
    }

    /**
     * appends container_acl sql
     *
     * @param  Zend_Db_Select    $_select
     * @param  integer           $iteration
     * @return string table identifier to work on
     */
    public static function addGrantsSqlCallback($_select, $iteration)
    {
        $db = $_select->getAdapter();
        $_select->join(array(
            /* table  */ 'container_acl' . $iteration => SQL_TABLE_PREFIX . 'container_acl'),
            /* on     */ $db->quoteIdentifier('container_acl' . $iteration . '.container_id') . ' = ' . $db->quoteIdentifier('container.id'),
            array()
        );
        return 'container_acl' . $iteration;
    }

    /**
     * return account ids of accounts which made personal container accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @param   bool                                $_andGrants
     * @return  array of array of containerData
     */
    protected function _getOtherAccountIds($_accountId, $_application, $_grant, $_ignoreACL = FALSE, $_andGrants = FALSE)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        $grant       = $_ignoreACL ? '*' : $_grant;

        $classCacheId = Tinebase_Helper::convertCacheId($accountId . $application->getId() . implode('', (array)$grant) . (int)$_ignoreACL . (int)$_andGrants);
        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        $select = $this->_db->select()
            ->from(array('container' => SQL_TABLE_PREFIX . 'container'), array('owner_id'))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('container_id' => 'container.id')
            )
            ->join(array(
                /* table  */ 'accounts' => SQL_TABLE_PREFIX . 'accounts'),
                /* on     */ "{$this->_db->quoteIdentifier('container.owner_id')} = {$this->_db->quoteIdentifier('accounts.id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            ->where("{$this->_db->quoteIdentifier('container.owner_id')} != ?", $accountId)
            ->where("{$this->_db->quoteIdentifier('accounts.status')} IN (?)",
                [Tinebase_Model_FullUser::ACCOUNT_STATUS_BLOCKED, Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED]);

        $this->addGrantsSql($select, $accountId, $grant, 'container_acl', $_andGrants, __CLASS__ . '::addGrantsSqlCallback');

        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $accountIds = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $accountIds);

        return $accountIds;
    }
    
    /**
     * return set of all personal container of other users made accessible to the given account 
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $recordClass
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getOtherUsersContainer($_accountId, $recordClass, $_grant, $_ignoreACL = FALSE)
    {
        // legacy handling
        $meta = \Tinebase_Application::extractAppAndModel($recordClass);
        $result = $this->_getOtherUsersContainerData($_accountId, $meta['appName'], $_grant, $_ignoreACL);

        return $result;
    }
    
    /**
     * return containerData of containers which made personal accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $_application
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    protected function _getOtherUsersContainerData($_accountId, $_application, $_grant, $_ignoreACL = FALSE)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        $grant       = $_ignoreACL ? '*' : $_grant;
        
        $classCacheId = $accountId .
                        $application->getId() .
                        implode('', (array)$grant) .
                        (int)$_ignoreACL;

        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        $select = $this->_db->select()
            ->from(array('container' => SQL_TABLE_PREFIX . 'container'))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                /* on     */ "{$this->_db->quoteIdentifier('container.id')} = {$this->_db->quoteIdentifier('container_acl.container_id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'accounts' => SQL_TABLE_PREFIX . 'accounts'),
                /* on     */ "{$this->_db->quoteIdentifier('container.owner_id')} = {$this->_db->quoteIdentifier('accounts.id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            ->where("{$this->_db->quoteIdentifier('container.owner_id')} != ?", $accountId)
            ->where("{$this->_db->quoteIdentifier('accounts.status')} = ?", 'enabled')
            
            ->order('accounts.display_name');

        $this->addGrantsSql($select, $accountId, $grant);
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);

        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $result, TRUE);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);
        
        return $containers;
    }

    /**
     * Deletes entries
     *
     * @param string|integer|Tinebase_Record_Interface|array $_id
     * @return int The number of affected rows.
     */
    public function delete($_id)
    {
        if (empty($_id)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No records deleted.');
            return 0;
        }

        $idArray = (! is_array($_id)) ? array(Tinebase_Record_Abstract::convertId($_id, $this->_modelName)) : $_id;

        foreach($idArray as $id) {
            $this->deleteContainer($id);
        }
    }

    /**
     * delete container if user has the required right
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   boolean $_ignoreAcl
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_SystemContainer
     * @throws  Tinebase_Exception_InvalidArgument
     * 
     * @todo move records in deleted container to personal container?
     */
    public function deleteContainer($_containerId, $_ignoreAcl = false)
    {
        $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        $container = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($containerId, $_ignoreAcl);
        $this->checkSystemContainer($containerId);

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleting container id ' . $containerId . ' ...');

        $deletedContainer = NULL;

        if ($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN) && (
                    !isset($container->xprops()['Calendar']['Resource']['resource_id']) ||
                    !Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES))) {
                throw new Tinebase_Exception_AccessDenied('Permission to delete container denied.');
            }

            if($container->type !== Tinebase_Model_Container::TYPE_PERSONAL and $container->type !== Tinebase_Model_Container::TYPE_SHARED) {
                throw new Tinebase_Exception_InvalidArgument('Can delete personal or shared containers only.');
            }

            // get personal container
            $personalContainer = $this->getDefaultContainer($container->model, Tinebase_Core::getUser());
            if ((string)($personalContainer->getId()) === (string)$containerId) {
                // _('You are not allowed to delete your default container!')
                throw new Tinebase_Exception_SystemGeneric('You are not allowed to delete your default container!');
            }
        }

        $tm = Tinebase_TransactionManager::getInstance();
        $myTransactionId = $tm->startTransaction(Tinebase_Core::getDb());

        try {
            $this->_writeModLog(null, $container);

            $this->deleteContainerContents($container, $_ignoreAcl);
            $deletedContainer = $this->_setRecordMetaDataAndUpdate($container, 'delete');

            $event = new Tinebase_Event_Record_Delete();
            $event->observable = $deletedContainer;
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent($event);
        } catch (Exception $e) {
            $tm->rollBack();
            // otherwise it would not be logged by the server code
            if ($e instanceof Tinebase_Exception_ProgramFlow) {
                Tinebase_Exception::log($e);
            }
            throw $e;
        }
        
        $tm->commitTransaction($myTransactionId);
        
        return $deletedContainer;

        /*
        // move all contained objects to next available personal container and try again to delete container
        $app = Tinebase_Application::getApplicationById($container->application_id);

        // get personal containers
        $personalContainers = $this->getPersonalContainer(
            Tinebase_Core::getUser(),
            $app->name,
            $container->owner,
            Tinebase_Model_Grants::GRANT_ADD
        );
        
        //-- determine first matching personal container (or create new one)
        // $personalContainer =
        
        //-- move all records to personal container
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Moving all records from container ' . $containerId . ' to personal container ' . $personalContainer->getId()
        );
        */
    }
    
    /**
     * set delete flag of records belonging to the given container
     * 
     * @param Tinebase_Model_Container $container
     */
    public function deleteContainerContents($container, $_ignoreAcl = FALSE)
    {
        $model = $container->model;

        if (empty($model)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No container model defined');
            return;
        }

        $controller = Tinebase_Core::getApplicationInstance($model, /* $_modelName */ '', $_ignoreAcl);

        if ($controller) {
            if ($_ignoreAcl === TRUE && method_exists($controller, 'doContainerACLChecks')) {
                $acl = $controller->doContainerACLChecks(FALSE);
            }

            if (method_exists($controller, 'deleteContainerContents')) {
                $controller->deleteContainerContents($container, $_ignoreAcl);
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' No deleteContainerContents defined in controller ' . get_class($controller));
            }

            if ($_ignoreAcl === TRUE && method_exists($controller, 'doContainerACLChecks')) {
                $controller->doContainerACLChecks($acl);
            }
        }
    }

    /**
     * @param string $_applicationId
     * @param string $_accountId
     * @param string|array $_grants
     * @return Tinebase_Record_RecordSet
     */
    public function getContainersByApplicationId($_applicationId, $_accountId, $_grants)
    {
        $accountId   = Tinebase_Model_User::convertUserIdToInt($_accountId);

        $classCacheId = $accountId . '#~#' . $_applicationId . join(',', (array)$_grants);

        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }

        $select = $this->_db->select()
            ->from(array('container' => SQL_TABLE_PREFIX . 'container'))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                /* on     */ "{$this->_db->quoteIdentifier('container.id')} = {$this->_db->quoteIdentifier('container_acl.container_id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $_applicationId)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE);

        $this->addGrantsSql($select, $accountId, $_grants);

        Tinebase_Backend_Sql_Abstract::traitGroup($select);

        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);

        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $result, TRUE);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);

        return $containers;
    }

    /**
     * drop container by application id, this is a straight and hard DB deletion
     * ATTENTION this does not follow deleteContainer() cleanup logic, this JUST deletes the containers, nothing more
     * 
     * @param string $_applicationId
     * @return integer number of deleted containers
     */
    public function dropContainerByApplicationId($_applicationId)
    {
        $this->resetClassCache();
        
        return $this->deleteByProperty($_applicationId, 'application_id');
    }
    
    /**
     * set container name, if the user has the required right
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   string $_containerName the new name
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function setContainerName($_containerId, $_containerName)
    {
        $container = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($_containerId);
        
        if (!$this->hasGrant(Tinebase_Core::getUser(), $container, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to rename container denied.');
        }
        
        $container->name = $_containerName;
        
        return $this->_setRecordMetaDataAndUpdate($container, 'update');
    }

    /**
     * set container color, if the user has the required right
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   string $_color the new color
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function setContainerColor($_containerId, $_color)
    {
        $container = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($_containerId);

        if (! $this->hasGrant(Tinebase_Core::getUser(), $container, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to set color of container denied.');
        }
        
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $_color)) {
            throw new Tinebase_Exception_UnexpectedValue('color is not valid');
        }
        
        $container->color = $_color;
        
        return $this->_setRecordMetaDataAndUpdate($container, 'update');
    }
    
    /**
     * check if the given user user has a certain grant
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Record_Interface        $_containerId
     * @param   array|string                        $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        try {
            $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getTraceAsString());
            return false;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' account: ' . $accountId . ' / containerId: ' . $containerId . ' / grant:' . implode('/', (array)$_grant));
        
        $classCacheId = $accountId . $containerId . implode('', (array)$_grant);
        
        try {
            $allGrants = $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // NOTE: some tests ask for already deleted container ;-)
            $select = $this->_getSelect(array(), true)
                ->distinct()
                ->where("{$this->_db->quoteIdentifier('container.id')} = ?", $containerId)
                ->join(array(
                    /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                    /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                    /* select */ array('container_acl.account_grant')
                );

            $this->addGrantsSql($select, $accountId, '*');

            $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);

            $allGrants = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
            $this->saveInClassCache(__FUNCTION__, $classCacheId, $allGrants);
        }
        
        $matchingGrants = array_intersect((array)$_grant, $allGrants);
        
        return !!count($matchingGrants);
    }
    
    /**
     * get all grants assigned to this container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   bool                         $_ignoreAcl
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        $container = $_containerId instanceof Tinebase_Model_Container ? $_containerId : $this->getContainerById($containerId);
        $grantModel = $container->getGrantClass();
        $grants = new Tinebase_Record_RecordSet($grantModel);
        
        $select = $this->_getAclSelectByContainerId($containerId)
            ->group(array('container_acl.container_id', 'container_acl.account_type', 'container_acl.account_id'));
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);

        $grantsData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        foreach($grantsData as $grantData) {
            $givenGrants = explode(',', $grantData['account_grants']);
            foreach($givenGrants as $grant) {
                $grantData[$grant] = TRUE;
            }
            
            $containerGrant = new $grantModel($grantData, TRUE);

            $grants->addRecord($containerGrant);
        }
        
        if ($_ignoreAcl !== TRUE) {
            if (TRUE !== $this->hasGrant(Tinebase_Core::getUser()->getId(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to get grants of container denied.');
            }
        }
        return $grants;
    }
    
    /**
     * get select with acl (grants) by container ID
     * 
     * @param integer $containerId
     * @return Zend_Db_Select
     */
    protected function _getAclSelectByContainerId($containerId)
    {
         $select = $this->_db->select()
            ->from(
                array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'),
                array('*', 'account_grants' => $this->_dbCommand->getAggregate('container_acl.account_grant'))
            )
            ->where("{$this->_db->quoteIdentifier('container_acl.container_id')} = ?", $containerId);
         return $select;
    }
    
    /**
     * get grants assigned to one account of one container
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Model_Container        $_containerId
     * @param   string                              $_grantModel
     * @return Tinebase_Model_Grants
     */
    public function getGrantsOfAccount($_accountId, $_containerId)
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $containerId        = Tinebase_Model_Container::convertContainerId($_containerId);
        $container          = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($_containerId);
        $grantModel         = $container->getGrantClass();

        $classCacheId = $accountId . $containerId . $container->seq . $grantModel;

        try {
            $grants = $this->loadFromClassCache(__FUNCTION__, $classCacheId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
            if ($grants instanceof Tinebase_Model_Grants) {
                return $grants;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Invalid data in cache ... fetching fresh data from DB');
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            // not found in cache
        }

        $select = $this->_getAclSelectByContainerId($containerId)
            ->group('container_acl.account_grant');

        $this->addGrantsSql($select, $accountId, '*');

        Tinebase_Backend_Sql_Abstract::traitGroup($select);

        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $grants = $this->_getGrantsFromArray($rows, $accountId, $grantModel);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $grants, Tinebase_Cache_PerRequest::VISIBILITY_SHARED, self::ACL_CACHE_TIMEOUT);

        return $grants;
    }
    
    /**
     * get grants assigned to given account of multiple records
     *
     * @param   Tinebase_Record_RecordSet   $_records records to get the grants for
     * @param   string|Tinebase_Model_User  $_accountId the account to get the grants for
     * @param   string                      $_containerProperty container property
     * @param   string                      $_grantModel
     * @throws  Tinebase_Exception_NotFound
     */
    public function getGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id')
    {
        $containers = $this->getContainerGrantsOfRecords($_records, $_accountId, $_containerProperty);
        
        if (!$containers) {
            return;
        }
        
        // add container & grants to records
        foreach ($_records as &$record) {
            if (!$containerId = $record->$_containerProperty) {
                continue;
            }
            
            if (! is_array($containerId) && ! $containerId instanceof Tinebase_Record_Interface && isset($containers[$containerId])) {
                if (isset($containers[$containerId]->path)) {
                    $record->$_containerProperty = $containers[$containerId];
                } else {
                    // if path is not determinable, skip this container
                    // @todo is it correct to remove record from recordSet???
                    $_records->removeRecord($record);
                }
            }
        }
    }
    
    /**
     * get grants for containers assigned to given account of multiple records
     *
     * @param   Tinebase_Record_RecordSet   $_records records to get the grants for
     * @param   string|Tinebase_Model_User  $_accountId the account to get the grants for
     * @param   string                      $_containerProperty container property
     * @param   string                      $_grantModel
     * @throws  Tinebase_Exception_NotFound
     * @return  array of containers|void
     */
    public function getContainerGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id')
    {
        $containerIds = array();
        foreach ($_records as $record) {
            if (isset($record[$_containerProperty])) {
                $containerId = Tinebase_Model_Container::convertContainerId($record[$_containerProperty]);
                if (!isset($containerIds[$containerId])) {
                    $containerIds[$containerId] = $containerId;
                }
            }
        }

        if (empty($containerIds)) {
            return array();
        }

        return $this->getContainerWithGrants($containerIds, $_accountId);
    }

    /**
     * @param array $_containerIds
     * @param string $_accountId
     * @return array
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Db_Select_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function getContainerWithGrants(array $_containerIds, $_accountId)
    {
        $accountId = $_accountId instanceof Tinebase_Record_Interface
            ? $_accountId->getId()
            : $_accountId;
        
        $select = $this->_getSelect('*', TRUE)
            ->where("{$this->_db->quoteIdentifier('container.id')} IN (?)", $_containerIds)
            ->joinLeft(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('*', 'account_grants' => $this->_dbCommand->getAggregate('container_acl.account_grant'))
            )
            ->group('container.id', 'container_acl.account_type', 'container_acl.account_id');
            
        $this->addGrantsSql($select, $accountId, '*');
        $where = $select->getPart(Zend_Db_Select::WHERE);
        $where[1] = rtrim($where[1], ')') . ') OR container_acl.account_type IS NULL)';
        $select->reset(Zend_Db_Select::WHERE);
        $select->where(join(' ', $where));
        $select->columns(['containerid' => 'id'], 'container');

        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $containers = array();
        // add results to container ids and get grants array
        foreach ($rows as $row) {
            // NOTE id is non-ambiguous
            $row['id']   = $row['containerid'];
            $container = new Tinebase_Model_Container($row, TRUE);
            
            $grantsArray = array_unique(explode(',', $row['account_grants']));
            $container->account_grants = $this->_getGrantsFromArray($grantsArray, $accountId,
                $container->getGrantClass())->toArray();
            
            $containers[$row['id']] = $container;
            
            try {
                $container->path = $container->getPath();
            } catch (Exception $e) {
                // @todo is it correct to catch all exceptions here?
                Tinebase_Exception::log($e);
            }
        }
        
        return $containers;
    }
    
    /**
     * set all grants for given container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   Tinebase_Record_RecordSet $_grants
     * @param   boolean $_ignoreAcl
     * @param   boolean $_failSafe don't allow to remove all admin grants for container
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Backend
     * @throws  Tinebase_Exception_SystemGeneric
     */
    public function setGrants($_containerId, Tinebase_Record_RecordSet $_grants, $_ignoreAcl = FALSE, $_failSafe = TRUE) 
    {
        $containerId = Tinebase_Model_Container::convertContainerId($_containerId);
        
        if($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to set grants of container denied.');
            }
        }
        
        // do failsafe check
        /** @var Tinebase_Model_Grants $grantsClass */
        $grantsClass = $_grants->getRecordClassName();
        if ($_failSafe && $grantsClass::doSetGrantFailsafeCheck()) {
            $adminGrant = FALSE;
            foreach ($_grants as $recordGrants) {
                if ($recordGrants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                    $adminGrant = TRUE;
                }
            }
            if (count($_grants) == 0 || ! $adminGrant) {
                // _('You are not allowed to remove all (admin) grants for this container.')
                throw new Tinebase_Exception_SystemGeneric('You are not allowed to remove all (admin) grants for this container.');
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting grants for container id ' . $containerId . ' ...');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_grants->toArray(), TRUE));
        
        try {

            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            $where = $this->_getContainerAclTable()->getAdapter()->quoteInto($this->_db->quoteIdentifier('container_id') . ' = ?', $containerId);
            $this->_getContainerAclTable()->delete($where);

            foreach ($_grants as $recordGrants) {
                $data = array(
                    'container_id' => $containerId,
                    'account_id' => $recordGrants['account_id'],
                    'account_type' => $recordGrants['account_type'],
                );

                foreach ($recordGrants as $grantName => $grant) {
                    if (in_array($grantName, $recordGrants->getAllGrants()) && $grant === TRUE) {
                        $data['id'] = $recordGrants->generateUID();
                        try {
                            $this->_getContainerAclTable()->insert($data + array('account_grant' => $grantName));
                        } catch (Zend_Db_Statement_Exception $zdse) {
                            if (! Tinebase_Exception::isDbDuplicate($zdse)) {
                                throw $zdse;
                            }
                        }
                    }
                }
            }

            $newGrants = $this->getGrantsOfContainer($containerId, true);
            $this->_writeModLog(
                new Tinebase_Model_Container(array('id' => $containerId, 'account_grants' => $newGrants), true),
                new Tinebase_Model_Container(array('id' => $containerId), true)
            );

            $this->_setRecordMetaDataAndUpdate($containerId, 'update', false);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Exception::log($e);
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
        
        return $this->getGrantsOfContainer($containerId, $_ignoreAcl);
    }
    
    /**
     * get owner (account_id) of container
     * 
     * @param Tinebase_Model_Container $_container
     * @return string|boolean
     */
    public function getContainerOwner(Tinebase_Model_Container $_container)
    {
        if ($_container->type !== Tinebase_Model_Container::TYPE_PERSONAL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Only personal containers have an owner.');
            return FALSE;
        }

        if ($_container->owner_id) {
            return $_container->owner_id;
        }

        $grants = (! $_container->account_grants) ? $this->getGrantsOfContainer($_container, true) : $_container->account_grants;
        
        if (count($grants) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Container ' . $_container->name . ' has no account grants.');
            return FALSE;
        }
        
        // return first admin user
        foreach ($grants as $grant) {
            if ($grant->{Tinebase_Model_Grants::GRANT_ADMIN} && $grant->account_type == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                return $grant->account_id;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Container ' . $_container->name . ' has no owner.');
        
        return FALSE;
    }
    
    /**
     * only one admin is allowed for personal containers
     * 
     * @param $_container
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Tinebase_Exception_UnexpectedValue
     *
     * @deprecated: could be removed because we have an owner property and could have multiple admins for a personal container now
     */
    public function checkContainerOwner(Tinebase_Model_Container $_container)
    {
        if ($_container->type !== Tinebase_Model_Container::TYPE_PERSONAL || empty($_container->account_grants)) {
            return;
        }
        
        if (! $_container->account_grants instanceof Tinebase_Record_RecordSet) {
            throw new Tinebase_Exception_UnexpectedValue('RecordSet of grants expected.');
        }

        $_container->account_grants->addIndices(array(Tinebase_Model_Grants::GRANT_ADMIN));
        $adminGrants = $_container->account_grants->filter(Tinebase_Model_Grants::GRANT_ADMIN, TRUE);
        if (count($adminGrants) > 1) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Multiple admin grants detected in container "' . $_container->name . '"');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($adminGrants->toArray(), TRUE));
            throw new Tinebase_Exception_SystemGeneric('Personal containers can have only one owner!'); // _('Personal containers can have only one owner!')
        }
    }
    
    /**
     * remove all container related entries from cache
     * 
     * @param int|Tinebase_Model_Container $containerId
     */
    protected function _clearCache($containerId) 
    {
        $containerId = Tinebase_Model_Container::convertContainerId($containerId);
        $cache = Tinebase_Core::getCache();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Removing all cache entries for container id ' . $containerId);

        $null = null;
        $idsToDelete = array(
            sha1($this->_getInClassCacheIdentifier() . 'getContainerById' . sha1($containerId . 'd0') . $null),
            sha1($this->_getInClassCacheIdentifier() . 'getContainerById' . sha1($containerId . 'd1') . $null),
        );
        
        foreach ($idsToDelete as $id) {
            $cache->remove($id);
        }
        
        $this->resetClassCache();
    }

    /**
     * lazy loading for containerACLTable
     * 
     * @return Tinebase_Db_Table
     */
    protected function _getContainerAclTable()
    {
        if (! $this->_containerAclTable) {
            $this->_containerAclTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container_acl'));
        }
        
        return $this->_containerAclTable;
    }
    
    /**
     * get grants record from an array with grant values
     *
     * @param array $_grantsArray
     * @param int $_accountId
     * @param string $_grantModel
     * @return Tinebase_Model_Grants (or child class)
     */
    protected function _getGrantsFromArray(array $_grantsArray, $_accountId, $_grantModel)
    {
        $grants = array();
        foreach($_grantsArray as $key => $value) {
            $grantValue = (is_array($value)) ? $value['account_grant'] : $value;
            $grants[$grantValue] = TRUE;
        }
        $grantsFields = array(
            'account_id'     => $_accountId,
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
        );
        $grantsFields = array_merge($grantsFields, $grants);

        /** @var Tinebase_Model_Grants $grants */
        $grants = new $_grantModel($grantsFields, TRUE);

        return $grants;
    }

    /**
     * increase content sequence of container
     * - should be increased for each create/update/delete operation in this container
     * 
     * @param integer|Tinebase_Model_Container $containerId
     * @param string $action
     * @param string $recordId
     * @return integer new content seq
     */
    public function increaseContentSequence($containerId, $action = NULL, $recordId = NULL)
    {
        $containerId = Tinebase_Model_Container::convertContainerId($containerId);

        $newContentSeq = NULL;
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
        
            $quotedIdentifier = $this->_db->quoteIdentifier('content_seq');
            $data = array(
                'content_seq' => new Zend_Db_Expr('(CASE WHEN ' . $quotedIdentifier . ' >= 1 THEN ' . $quotedIdentifier . ' + 1 ELSE 1 END)')
            );
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $containerId)
            );
            $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
            
            $newContentSeq = $this->getContentSequence($containerId);
            if ($newContentSeq === NULL) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Something strange happend: content seq of NULL has been detected for container ' . $containerId . ' . Setting it to 0.');
                $newContentSeq = 0;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Increased content seq of container ' . $containerId . ' to ' . $newContentSeq);
            }
            
            // create new entry in container_content table
            if ($action !== NULL && $recordId !== NULL) {
                $contentRecord = new Tinebase_Model_ContainerContent(array(
                    'container_id' => $containerId,
                    'action'       => $action,
                    'record_id'    => $recordId,
                    'time'         => Tinebase_DateTime::now(),
                    'content_seq'  => $newContentSeq,
                ));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Creating "' . $action . '" action content history record for record id ' . $recordId);
                $this->getContentBackend()->create($contentRecord);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->resetClassCache();
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $newContentSeq;
    }
    
    /**
     * get content history since given content_seq
     *
     * returns null if the lastContentSeq was not found in DB and was not -1 or 0
     * -1 is used when paging or for inbox which actually doesnt have a sync token
     * 0 is the initial container content_seq, thus the initial sync token for an empty container
     * 
     * @param integer|Tinebase_Model_Container $containerId
     * @param integer $lastContentSeq
     * @return Tinebase_Record_RecordSet|null
     */
    public function getContentHistory($containerId, $lastContentSeq = -1)
    {
        $filter = new Tinebase_Model_ContainerContentFilter(array(
            array('field' => 'container_id', 'operator' => 'equals',  'value' => Tinebase_Model_Container::convertContainerId($containerId)),
            array('field' => 'content_seq',  'operator' => 'greater', 'value' => ($lastContentSeq == -1 ? $lastContentSeq : $lastContentSeq - 1)),
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => 'content_seq'
        ));
        $result = $this->getContentBackend()->search($filter, $pagination);
        if ($lastContentSeq != -1 && $lastContentSeq != 0) {
            if ($result->count() === 0) {
                return null;
            }
            $firstRecord = $result->getFirstRecord();
            if ($firstRecord->content_seq != $lastContentSeq) {
                return null;
            }
            $result->removeRecord($firstRecord);
        }
        return $result;
    }

    /**
     * get content sequences for single container or array of ids
     * 
     * @param array|integer|Tinebase_Model_Container $containerIds
     * @return array with key = container id / value = content seq number | integer
     *
     * TODO improve function: should only have one param & return type
     */
    public function getContentSequence($containerIds)
    {
        if (empty($containerIds)) {
            return NULL;
        }
        
        $containerIds = (! is_array($containerIds))
            ? Tinebase_Model_Container::convertContainerId($containerIds)
            : $containerIds;
        
        $select = $this->_getSelect(array('id', 'content_seq'), TRUE);
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', (array) $containerIds));
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $result = $stmt->fetchAll();
        foreach ($result as $key => $value) {
            $result[$value['id']] = $value['content_seq'];
        }

        $result = (is_array($containerIds))
            ? $result
            : (is_scalar($containerIds) && isset($result[$containerIds]) ? $result[$containerIds] : NULL);
        return $result;
    }
    
    /**
     * checks if container to delete is a "system" container 
     * 
     * @param array|integer|Tinebase_Model_Container $containerIds
     * @throws Tinebase_Exception_Record_SystemContainer
     * 
     * @TODO: generalize when there are more "system" containers
     */
    public function checkSystemContainer($containerIds)
    {
        if (!is_array($containerIds)) $containerIds = array($containerIds);

        // at the moment, just the internal addressbook is checked
        $defaultAddressbookId = Addressbook_Controller::getDefaultInternalAddressbook();

        if ($defaultAddressbookId && in_array($defaultAddressbookId, $containerIds)) {
            // _('You are not allowed to delete this Container. Please define another container as the default addressbook for internal contacts!')
            throw new Tinebase_Exception_Record_SystemContainer('You are not allowed to delete this Container. Please define another container as the default addressbook for internal contacts!');
        }
    }

    /**
     * create a new system container
     * - by default user group gets READ grant
     * - by default admin group gets all grants
     *
     * NOTE: this should never be called in user land and only in admin/setup contexts
     *
     * @param Tinebase_Model_Application|string $application app record, app id or app name
     * @param string $model the model the container contains
     * @param string $name
     * @param string $idConfig save id in config if given
     * @param Tinebase_Record_RecordSet $grants use this to overwrite default grants
     * @return Tinebase_Model_Container
     */
    public function createSystemContainer($application, $model, $name, $configId = NULL, Tinebase_Record_RecordSet $grants = NULL)
    {
        $application = ($application instanceof Tinebase_Model_Application) ? $application : Tinebase_Application::getInstance()->getApplicationById($application);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Creating system container for model ' . $model);

        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $name,
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => $application->getId(),
            'model'             => $model
        ));

        $grants = ($grants) ? $grants : Tinebase_Model_Grants::getDefaultGrants();
        $newContainer = $this->addContainer($newContainer, $grants, TRUE);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Created new system container ' . $name . ' for application ' . $application->name);

        if ($configId !== NULL) {
            $configClass = $application->name . '_Config';
            if (@class_exists($configClass)) {
                $config = call_user_func(array($configClass, 'getInstance'));

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Setting system container config "' . $configId . '" = ' . $newContainer->getId());

                $config->set($configId, $newContainer->getId());
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Could not find preferences class ' . $configClass);
            }
        }

        $this->resetClassCache();

        return $newContainer;
    }

    /**
     * Updates existing container and clears the cache entry of the container
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_updateDeleted = false
     * @param boolean $_fireEvent = true
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record, $_updateDeleted = false, $_fireEvent = true)
    {
        $this->_clearCache($_record);

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        // use get (avoids cache) or getContainerById, guess its better to avoid the cache
        $oldContainer = $this->get($_record->getId(), $_updateDeleted);

        $result = parent::update($_record);

        unset($result->account_grants);
        unset($oldContainer->account_grants);
        $mods = $this->_writeModLog($result, $oldContainer);
        Tinebase_Notes::getInstance()->addSystemNote($result, Tinebase_Core::getUser(),
            Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $mods);

        if ($_fireEvent) {
            $event = new Tinebase_Event_Record_Update();
            $event->observable = $result;
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent($event);
        }

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        return $result;
    }

    public function setContainerOwners()
    {
        $select = $this->_getSelect('id')
            ->where('type = ?', Tinebase_Model_Container::TYPE_PERSONAL)
            ->where('owner_id is null or owner_id = ?', '');

        $stmt = $this->_db->query($select);
        $containers = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $count = 0;
        foreach ($containers as $container) {
            $id = $container['id'];
            $grants = $this->getGrantsOfContainer($id, /* ignore acl */ true);
            foreach ($grants as $grant) {
                if ($grant->adminGrant && $grant->account_type == 'user') {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Set owner for container id ' . $id . ': ' .  $grant->account_id);
                    $where  = array(
                        $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $id),
                    );

                    $this->_db->update($this->_tablePrefix . $this->_tableName, array('owner_id' => $grant->account_id), $where);

                    $count++;
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Set owner for ' . $count . ' containers.');
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
                $record = new $model($diff->diff);
                $this->addContainer($record, null, true);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                if (isset($diff->diff['account_grants'])) {
                    $container = $this->getContainerById($_modification->record_id);
                    $this->setGrants($container, new Tinebase_Record_RecordSet($container->getGrantClass(), $diff->diff['account_grants']['added']), true, false);
                } else {
                    $record = $this->get($_modification->record_id, true);
                    $record->applyDiff($diff);
                    $this->update($record, true);
                }
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                $this->deleteContainer($_modification->record_id, true);
                break;

            default:
                throw new Tinebase_Exception('unknown Tinebase_Model_ModificationLog->change_type: ' . $_modification->change_type);
        }
    }

    /**
     * returns container numberable config if present in xprops
     *
     * @param $record
     * @return array
     */
    public function getNumberableConfig($record)
    {
        if ($record->has('container_id')) {
            $container = $this->getContainerById($record->container_id);
            if (isset($container->xprops()[Tinebase_Numberable::CONFIG_XPROPS]) && is_array($container->xprops()[Tinebase_Numberable::CONFIG_XPROPS])) {
                return $container->xprops()[Tinebase_Numberable::CONFIG_XPROPS];
            }
        }

        return [];
    }

    /**
     * forces containers that support sync token to resync via WebDAV sync tokens
     *
     * this will DELETE the complete content history for the affected containers
     * this will increate the sequence for all records in all affected containers
     * this will increate the sequence of all affected containers
     *
     * this will cause 2 BadRequest responses to sync token requests
     * the first one as soon as the client notices that something changed and sends a sync token request
     * eventually the client receives a false sync token (as we increased content sequence, but we dont have a content history entry)
     * eventually not (if something really changed in the calendar in the meantime)
     *
     * in case the client got a fake sync token, the clients next sync token request (once something really changed) will fail again
     * after something really changed valid sync tokens will be handed out again
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function forceSyncTokenResync(Tinebase_Model_ContainerFilter $filter)
    {
        $contentBackend = $this->getContentBackend();
        $db = Tinebase_Core::getDb();
        $modelBackendCache = [];
        $oldDoSearchAcl = $this->_doSearchAclFilter;
        $this->_doSearchAclFilter = false;

        try {
            /** @var Tinebase_Model_Container $container */
            foreach ($this->search($filter) as $container) {
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                if (!isset($modelBackendCache[$container->model])) {
                    $recordsBackend = Tinebase_Core::getApplicationInstance($container->model)->getBackend();
                    $modelBackendCache[$container->model] = $recordsBackend;
                } else {
                    $recordsBackend = $modelBackendCache[$container->model];
                }

                if (method_exists($recordsBackend, 'increaseSeqsForContainerId')) {
                    // increase sequence for all records in this container
                    $recordsBackend->increaseSeqsForContainerId($container->getId());

                    // increase sequence on this container
                    $this->increaseContentSequence($container->getId());

                    // delete content history for this container
                    $numDeletedContentHistory = $contentBackend->deleteByProperty($container->getId(), 'container_id');
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                            __LINE__ . ' ' . $container->getId() . ' ' . $container->name .
                            ' deleted content history entries: ' . $numDeletedContentHistory);
                    }
                }
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            }
        } finally {
            $this->_doSearchAclFilter = $oldDoSearchAcl;
        }
    }

    /**
     * Delete duplicate container without contents.
     *
     * @param $application
     * @param null $dryrun
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_SystemContainer
     * @return integer
     *
     * TODO also check translated names
     * TODO allow to move records into the older container
     */
    public function deleteDuplicateContainer($application, $dryrun = null)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($application);

        $filter = new Tinebase_Model_ContainerFilter([
            ['field' => 'type', 'operator' => 'equals', 'value' => 'personal'],
            ['field' => 'application_id', 'operator' => 'equals', 'value' => $application->getId()]
        ]);

        Tinebase_Container::getInstance()->doSearchAclFilter(false);

        $containers = Tinebase_Container::getInstance()->search($filter);

        Tinebase_Container::getInstance()->doSearchAclFilter(true);

        $removeCount = 0;
        foreach ($containers as $container) {
            $duplicate = $containers->filter('name', $container['name']);
            $duplicate->sort('creation_time', 'ASC');

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Container: . ' . $duplicate->getFirstRecord()['id'] . ' is the default Container');

            $duplicate->removeFirst();
            if ($duplicate->count() > 0) {

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Duplicates found. ' . $duplicate);

                foreach ($duplicate as $dupContainer) {
                    if ($dupContainer['content_seq'] == 0) {
                        if ($dryrun) {

                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                . ' Dry run: Duplicate ' . $dupContainer['name'] . ' ' . $dupContainer['id'] . ' will remove.');

                        } else {
                            Tinebase_Container::getInstance()->deleteContainer($dupContainer['id'], true);

                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                . ' Duplicate ' . $dupContainer['name'] . ' ' . $dupContainer['id'] . ' remove.');

                        }
                        $removeCount++;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Duplicate ' . $dupContainer['name'] . ' ' . $dupContainer['id'] . ' don´t remove, because in container exist records');

                    }
                }
            }
            $containers->removeRecords($duplicate);
        }

        return $removeCount;
    }

    public function getModel()
    {
        return Tinebase_Model_Container::class;
    }
}
