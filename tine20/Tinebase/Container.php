<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tinebase_Container extends Tinebase_Backend_Sql_Abstract
{
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
    
    /**
     * get content backend
     * 
     * @return Tinebase_Backend_Sql
     * 
     * @todo move this to constructor when this no longer extends Tinebase_Backend_Sql_Abstract
     */
    protected function _getContentBackend()
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
     * creates a new container
     *
     * @param   Tinebase_Model_Container $_container the new container
     * @param   Tinebase_Record_RecordSet $_grants the grants for the new folder 
     * @param   bool  $_ignoreAcl
     * @return  Tinebase_Model_Container the newly created container
     * @throws  Tinebase_Exception_Record_Validation
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addContainer(Tinebase_Model_Container $_container, $_grants = NULL, $_ignoreAcl = FALSE)
    {
        $_container->isValid(TRUE);
        
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
                    

                    if(!$manageRight && !Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::ADMIN)) {
                        throw new Tinebase_Exception_AccessDenied('Permission to add shared container denied.');
                    }
                    break;
                    
                default:
                    throw new Tinebase_Exception_InvalidArgument('Can add personal or shared folders only when ignoring ACL.');
                    break;
            }
        }
        
        if (!empty($_container->owner_id)) {
            $accountId = $_container->owner_id instanceof Tinebase_Model_User ? $_container->owner_id->getId() : $_container->owner_id;
        } else {
            $accountId = (is_object(Tinebase_Core::getUser())) ? Tinebase_Core::getUser()->getId() : NULL;
        }
        
        if($_grants === NULL || count($_grants) == 0) {
            $creatorGrants = array(
                'account_id'     => $accountId,
                'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                Tinebase_Model_Grants::GRANT_EXPORT    => true,
                Tinebase_Model_Grants::GRANT_SYNC      => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
            );
            
            if (    $_container->type === Tinebase_Model_Container::TYPE_SHARED 
                 && ! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
    
                // add all grants to creator and
                // add read grants to any other user
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    $creatorGrants,
                    array(
                        'account_id'      => '0',
                        'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                        Tinebase_Model_Grants::GRANT_READ    => true,
                        Tinebase_Model_Grants::GRANT_EXPORT  => true,
                        Tinebase_Model_Grants::GRANT_SYNC    => true,
                    )
                ), TRUE);
            } else {
                // add all grants to creator only
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    $creatorGrants
                ), TRUE);
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
        $this->setGrants($container->getId(), $grants, TRUE, FALSE);
        
        return $container;
    }
    
    /**
     * add grants to container
     *
     * @todo    check that grant is not already given to container/type/accout combi
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   int $_accountId
     * @param   array $_grants list of grants to add
     * @return  boolean
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addGrants($_containerId, $_accountType, $_accountId, array $_grants, $_ignoreAcl = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
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
            default:
                throw new Tinebase_Exception_InvalidArgument('invalid $_accountType');
                break;
        }
        
        $containerGrants = $this->getGrantsOfContainer($containerId, TRUE);
        $containerGrants->addIndices(array('account_type', 'account_id'));
        $existingGrants = $containerGrants->filter('account_type', $_accountType)->filter('account_id', $_accountId)->getFirstRecord();
        
        $id = Tinebase_Record_Abstract::generateUID();
        
        foreach($_grants as $grant) {
            if ($existingGrants === NULL || ! $existingGrants->{$grant}) {
                $data = array(
                    'id'            => $id,
                    'container_id'  => $containerId,
                    'account_type'  => $_accountType,
                    'account_id'    => $accountId,
                    'account_grant' => $grant
                );
                $this->_getContainerAclTable()->insert($data);
            }
        }
        
        $this->_setRecordMetaDataAndUpdate($containerId, 'update');
        
        return true;
    }
    
    /**
     * resolves the $_recordClass argument for legacy handling: $_recordClass was before $_application
     * in getDefaultContainer and getPersonalContainer
     * @param string|Tinebase_Record_Interface $_recordClass
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _resolveRecordClassArgument($_recordClass)
    {
        $ret = array();
        if(is_string($_recordClass)) {
            $split = explode('_', $_recordClass);
            switch(count($split)) {
                case 1:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using application name is deprecated. Please use the classname of the model or the class itself.');
                    $ret['appName'] = $_recordClass;
                    if(! $ret['recordClass'] = Tinebase_Core::getApplicationInstance($_recordClass)->getDefaultModel()) {
                        throw new Tinebase_Exception_NotFound('A default model could not be found for application ' . $_recordClass);
                    }
                    break;
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
     * @return Tinebase_Model_Container
     */
    protected function _setRecordMetaDataAndUpdate($container, $action)
    {
        if (! $container instanceof Tinebase_Model_Container) {
            $container = $this->getContainerById($container);
        }
        Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($container, $action, $container);
        
        $this->_clearCache($container);
        
        return $this->update($container);
    }
    
    /**
    * get the basic select object to fetch records from the database
    *
    * @param array|string $_cols columns to get, * per default
    * @param boolean $_getDeleted get deleted records (if modlog is active)
    * @return Zend_Db_Select
    */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        if ($_cols === '*') {
            $select->joinLeft(
                /* table  */ array('owner' => SQL_TABLE_PREFIX . 'container_acl'),
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')} AND ".
                             "{$this->_db->quoteIdentifier('container.type')} = {$this->_db->quote(Tinebase_Model_Container::TYPE_PERSONAL)} AND " .
                             "{$this->_db->quoteIdentifier('owner.account_type')} = {$this->_db->quote('user')} AND " .
                             "{$this->_db->quoteIdentifier('owner.account_grant')} = {$this->_db->quote(Tinebase_Model_Grants::GRANT_ADMIN)}",
                /* select */ array('owner_id' => 'account_id')
            );
        }
        
        return $select;
    }
    
    /**
     * get containers with bad names (name == uuid)
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function getContainersWithBadNames()
    {
        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier('uuid') . ' = ' . $this->_db->quoteIdentifier('name'));
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $rows, TRUE);
        
        return $result;
    }
    
    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param   string|Tinebase_Model_User          $accountId
     * @param   string|Tinebase_Model_Application   $recordClass
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
        if (is_array($grant)) {
            sort($grant);
        }
        
        $classCacheId = $accountId . $applicationId . implode('', (array)$grant) . (int)$onlyIds . (int)$ignoreACL;
        
        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }
        
        $select = $this->_getSelect($onlyIds ? 'id' : '*')
            ->distinct()
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $applicationId);
            
        if (!$onlyIds) {
            // we only need to order by name if we fetch all container data (legacy, maybe this can be removed)
            $select->order('container.name');
        }
        
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
            $personalContainer = $this->getDefaultContainer($meta['appName'], $accountId);
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
     */
    public function getContainerById($_containerId, $_getDeleted = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $cacheId = $containerId . 'd' . (int)$_getDeleted;
        
        try {
            return $this->loadFromClassCache(__FUNCTION__, $cacheId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue...
        }
        
        $result = $this->get($containerId, $_getDeleted);
        
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
     * @param   string|Tinebase_Model_Application  $recordClass app name
     * @param   int|Tinebase_Model_Container       $containerName
     * @param   string                             $type
     * @param   string                             $ownerId
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
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
            $select->where("{$this->_db->quoteIdentifier('owner.account_id')} = ?", $ownerId);
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
    public function getPersonalContainer($_accountId, $_recordClass, $_owner, $_grant, $_ignoreACL = false)
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
            ->distinct()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(array(
                /* table  */ 'user' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('user.container_id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')}"
            )
            
            ->where("{$this->_db->quoteIdentifier('owner.account_id')} = ?", $ownerId)
            ->where("{$this->_db->quoteIdentifier('owner.account_grant')} = ?", Tinebase_Model_Grants::GRANT_ADMIN)
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            
            ->order('container.name');
            
        $this->addGrantsSql($select, $accountId, $grant, 'user');
        
        if ($meta['recordClass']) {
            $select->where("{$this->_db->quoteIdentifier('container.model')} = ?", $meta['recordClass']);
        }
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $containersData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // if no containers where found, maybe something went wrong when creating the initial folder
        // let's check if the controller of the application has a function to create the needed folders
        if (empty($containersData) and $accountId === $ownerId) {
            $application = Tinebase_Core::getApplicationInstance($application->name);
            
            if ($application instanceof Tinebase_Container_Interface) {
                return $application->createPersonalFolder($accountId);
            }
        }

        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $containersData, TRUE);
        
        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);

        return $containers;
    }
    
    /**
     * appends container_acl sql 
     * 
     * @param  Zend_Db_Select    $_select
     * @param  String            $_accountId
     * @param  Array|String      $_grant
     * @param  String            $_aclTableName
     * @param  bool              $_andGrants
     * @return void
     */
    public static function addGrantsSql($_select, $_accountId, $_grant, $_aclTableName = 'container_acl', $_andGrants = FALSE)
    {
        $accountId = $_accountId instanceof Tinebase_Record_Abstract
            ? $_accountId->getId()
            : $_accountId;
        
        $db = $_select->getAdapter();
        
        $grants = is_array($_grant) ? $_grant : array($_grant);
        


        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        $quotedActId   = $db->quoteIdentifier("{$_aclTableName}.account_id");
        $quotedActType = $db->quoteIdentifier("{$_aclTableName}.account_type");
        
        $accountSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        $accountSelect
            ->orWhere("{$quotedActId} = ? AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER), $accountId)
            ->orWhere("{$quotedActId} IN (?) AND {$quotedActType} = " . $db->quote(Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP), empty($groupMemberships) ? ' ' : $groupMemberships);
        
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
            $accountSelect->orWhere("{$quotedActType} = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        }
        $accountSelect->appendWhere(Zend_Db_Select::SQL_AND);
        
        // we only need to filter, if the filter does not contain %
        if (!in_array('*', $grants)) {
            // @todo fetch wildcard from specific db adapter
            $grants = str_replace('*', '%', $grants);
        
            $quotedGrant   = $db->quoteIdentifier("{$_aclTableName}.account_grant");
            
            $grantsSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            foreach ($grants as $grant) {
                if ($_andGrants) {
                    $grantsSelect->where("{$quotedGrant} LIKE ?", $grant);
                } else {
                    $grantsSelect->orWhere("{$quotedGrant} LIKE ?", $grant);
                }
            }

            // admin grant includes all other grants
            if (! in_array(Tinebase_Model_Grants::GRANT_ADMIN, $grants)) {
                $grantsSelect->orWhere("{$quotedGrant} LIKE ?", Tinebase_Model_Grants::GRANT_ADMIN);
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
     * @param   string|Tinebase_Model_User          $accountId
     * @param   string                              $defaultContainerPreferenceName
     * @return  Tinebase_Model_Container
     * 
     * @todo get default container name from app/translations?
     */
    public function getDefaultContainer($recordClass, $accountId = NULL, $defaultContainerPreferenceName = NULL)
    {
        // legacy handling
        $meta = $this->_resolveRecordClassArgument($recordClass);
        
        if ($defaultContainerPreferenceName !== NULL) {
            $defaultContainerId = Tinebase_Core::getPreference($meta['appName'])->getValue($defaultContainerPreferenceName);
            try {
                $result = $this->getContainerById($defaultContainerId);
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Got default container from preferences: ' . $result->name);
                return $result;
            } catch (Tinebase_Exception $te) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Default container not found (' . $te->getMessage() . ')');
                // default may be gone -> remove default adb pref
                $appPref = Tinebase_Core::getPreference($this->_applicationName);
                if ($appPref) {
                    $appPref->deleteUserPref($defaultContainerPreferenceName);
                }
            }
        }
        
        $account = ($accountId !== NULL) ? Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $accountId) : Tinebase_Core::getUser();
        $result = $this->getPersonalContainer($account, $recordClass, $account, Tinebase_Model_Grants::GRANT_ADD)->getFirstRecord();
        
        if ($result === NULL) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Creating new default container for ' . $account->accountFullName . ' in application ' . $meta['appName']);
            
            $translation = Tinebase_Translation::getTranslation($meta['appName']);
            $result = $this->addContainer(new Tinebase_Model_Container(array(
                'name'              => sprintf($translation->_("%s's personal container"), $account->accountFullName),
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'owner_id'          => $account->getId(),
                'backend'           => 'Sql',
                'model'             => $meta['recordClass'],
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($meta['appName'])->getId() 
            )));
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
     * @param   string|Tinebase_Model_Application   $recordClass
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
        
        $select = $this->_getSelect()
            ->distinct()
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                array()
            )
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_SHARED)
            
            ->order('container.name');
        
        $this->addGrantsSql($select, $accountId, $grant, 'container_acl', $_andGrants);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC), TRUE);
        
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
        $meta = $this->_resolveRecordClassArgument($recordClass);
        $userIds = $this->_getOtherAccountIds($_accountId, $meta['appName'], $_grant, $_ignoreACL, $_andGrants);
        
        $users = Tinebase_User::getInstance()->getMultiple($userIds);
        $users->sort('accountDisplayName');
        
        return $users;
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

        // first grab all container ids ...
        $select = $this->_db->select()
            ->distinct()
            ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('container_id' => 'container.id')
            )
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE);

        $this->addGrantsSql($select, $accountId, $grant, 'container_acl', $_andGrants);

        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $containerIds = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        
        // no container ids found / can stop here
        if (empty($containerIds)) {
            return $containerIds;
        }
        
        // ... now get the owners of the containers 
        $select = $this->_db->select()
            ->distinct()
            ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array('account_id'))
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'accounts' => SQL_TABLE_PREFIX . 'accounts'),
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.account_id')} = {$this->_db->quoteIdentifier('accounts.id')}",
                /* select */ array()
            )
            ->where("{$this->_db->quoteIdentifier('container.id')} IN (?)", $containerIds)
            ->where("{$this->_db->quoteIdentifier('container_acl.account_id')} != ?", $accountId)
            ->where("{$this->_db->quoteIdentifier('container_acl.account_grant')} = ?", Tinebase_Model_Grants::GRANT_ADMIN)
            ->where("{$this->_db->quoteIdentifier('accounts.status')} = ?", 'enabled');
            
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
        $meta = $this->_resolveRecordClassArgument($recordClass);
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
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array('account_id'))
            ->join(array(
                /* table  */ 'user' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('user.container_id')}",
                /* select */ array()
            )
            ->join(array(
                /* table  */ 'container' => SQL_TABLE_PREFIX . 'container'), 
                /* on     */ "{$this->_db->quoteIdentifier('owner.container_id')} = {$this->_db->quoteIdentifier('container.id')}"
            )
            ->join(array(
                /* table  */ 'accounts' => SQL_TABLE_PREFIX . 'accounts'),
                /* on     */ "{$this->_db->quoteIdentifier('owner.account_id')} = {$this->_db->quoteIdentifier('accounts.id')}",
                /* select */ array()
            )
            #->join(array(
            #    /* table  */ 'contacts' => SQL_TABLE_PREFIX . 'addressbook'),
            #    /* on     */ "{$this->_db->quoteIdentifier('owner.account_id')} = {$this->_db->quoteIdentifier('contacts.account_id')}",
            #    /* select */ array()
            #)
            ->where("{$this->_db->quoteIdentifier('owner.account_id')} != ?", $accountId)
            ->where("{$this->_db->quoteIdentifier('owner.account_grant')} = ?", Tinebase_Model_Grants::GRANT_ADMIN)
            
            ->where("{$this->_db->quoteIdentifier('container.application_id')} = ?", $application->getId())
            ->where("{$this->_db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL)
            ->where("{$this->_db->quoteIdentifier('container.is_deleted')} = ?", 0, Zend_Db::INT_TYPE)
            ->where("{$this->_db->quoteIdentifier('accounts.status')} = ?", 'enabled')
            
            ->order('accounts.display_name')
            ->group('owner.account_id');
        
        $this->addGrantsSql($select, $accountId, $grant, 'user');
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC), TRUE);

        $this->saveInClassCache(__FUNCTION__, $classCacheId, $containers);
        
        return $containers;
    }
    
    /**
     * delete container if user has the required right
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   boolean $_ignoreAcl
     * @param   boolean $_tryAgain
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_InvalidArgument
     * 
     * @todo move records in deleted container to personal container?
     */
    public function deleteContainer($_containerId, $_ignoreAcl = FALSE, $_tryAgain = TRUE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        $container = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($containerId);
        $this->checkSystemContainer($containerId);
        
        $tm = Tinebase_TransactionManager::getInstance();   
        $myTransactionId = $tm->startTransaction(Tinebase_Core::getDb());

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleting container id ' . $containerId . ' ...');

        $deletedContainer = NULL;
        
        try {
            if($_ignoreAcl !== TRUE) {
                if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied('Permission to delete container denied.');
                }
                
                if($container->type !== Tinebase_Model_Container::TYPE_PERSONAL and $container->type !== Tinebase_Model_Container::TYPE_SHARED) {
                    throw new Tinebase_Exception_InvalidArgument('Can delete personal or shared containers only.');
                }
            }
            $this->deleteContainerContents($container, $_ignoreAcl);
            $deletedContainer = $this->_setRecordMetaDataAndUpdate($container, 'delete');
            
        } catch (Exception $e) {
            $tm->rollBack();
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
        // set records belonging to this container to deleted
        $model = $container->model;

        if (empty($model)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No container model defined');
            return;
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleting container contents ...');

        $controller = Tinebase_Core::getApplicationInstance($model);
        $filterName = $model . 'Filter';

        if ($_ignoreAcl === TRUE && method_exists($controller, 'doContainerACLChecks')) {
            $acl = $controller->doContainerACLChecks(FALSE);
        }
        if ($controller && class_exists($filterName)) {
            $filter = new $filterName(array(
                array(
                    'field'    => 'container_id',
                    'operator' => 'equals',
                    'value'    => intval($container->id)
                ),
                array(
                    'field'    => 'is_deleted',
                    'operator' => 'equals',
                    'value'    => 0
                )),
                'AND');
            $controller::getInstance()->deleteByFilter($filter);
        }

        if ($_ignoreAcl === TRUE && method_exists($controller, 'doContainerACLChecks')) {
            $controller->doContainerACLChecks($acl);
        }
    }
    
    /**
     * delete container by application id
     * 
     * @param string $_applicationId
     * @return integer numer of deleted containers 
     */
    public function deleteContainerByApplicationId($_applicationId)
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
     * @param   int|Tinebase_Model_Container        $_containerId
     * @param   array|string                        $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        try {
            $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
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
     * @param   string                       $_grantModel
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = FALSE, $_grantModel = 'Tinebase_Model_Grants') 
    {
        $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
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
            
            $containerGrant = new $_grantModel($grantData, TRUE);

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
    public function getGrantsOfAccount($_accountId, $_containerId, $_grantModel = 'Tinebase_Model_Grants') 
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $containerId        = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        $container          = ($_containerId instanceof Tinebase_Model_Container) ? $_containerId : $this->getContainerById($_containerId);
        
        $classCacheId = $accountId . $containerId . $container->seq . $_grantModel;
        
        try {
            return $this->loadFromClassCache(__FUNCTION__, $classCacheId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {
            
        }
        
        $select = $this->_getAclSelectByContainerId($containerId)
            ->group('container_acl.account_grant');
        
        $this->addGrantsSql($select, $accountId, '*');
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $grants = $this->_getGrantsFromArray($rows, $accountId, $_grantModel);
        
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
    public function getGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id', $_grantModel = 'Tinebase_Model_Grants')
    {
        $containers = $this->getContainerGrantsOfRecords($_records, $_accountId, $_containerProperty, $_grantModel);
        
        if (!$containers) {
            return;
        }
        
        // add container & grants to records
        foreach ($_records as &$record) {
            if (!$containerId = $record->$_containerProperty) {
                continue;
            }
            
            if (! is_array($containerId) && ! $containerId instanceof Tinebase_Record_Abstract && isset($containers[$containerId])) {
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
    public function getContainerGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id', $_grantModel = 'Tinebase_Model_Grants')
    {
        $containerIds = array();
        foreach ($_records as $record) {
            if (isset($record[$_containerProperty]) && !isset($containerIds[Tinebase_Model_Container::convertContainerIdToInt($record[$_containerProperty])])) {
                $containerIds[Tinebase_Model_Container::convertContainerIdToInt($record[$_containerProperty])] = null;
            }
        }
        
        if (empty($containerIds)) {
            return array();
        }
        
        $accountId = $_accountId instanceof Tinebase_Record_Abstract
            ? $_accountId->getId()
            : $_accountId;
        
        $select = $this->_getSelect('*', TRUE)
            ->where("{$this->_db->quoteIdentifier('container.id')} IN (?)", array_keys($containerIds))
            ->join(array(
                /* table  */ 'container_acl' => SQL_TABLE_PREFIX . 'container_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('container_acl.container_id')} = {$this->_db->quoteIdentifier('container.id')}",
                /* select */ array('*', 'account_grants' => $this->_dbCommand->getAggregate('container_acl.account_grant'))
            )
            ->group('container.id', 'container_acl.account_type', 'container_acl.account_id');
            
        $this->addGrantsSql($select, $accountId, '*');
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $containers = array();
        // add results to container ids and get grants array
        foreach ($rows as $row) {
            // NOTE id is non-ambiguous
            $row['id']   = $row['container_id'];
            
            $grantsArray = array_unique(explode(',', $row['account_grants']));
            $row['account_grants'] = $this->_getGrantsFromArray($grantsArray, $accountId, $_grantModel)->toArray();
            
            $containers[$row['id']] = new Tinebase_Model_Container($row, TRUE);
            
            try {
                $containers[$row['id']]->path = $containers[$row['id']]->getPath();
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
     * @throws  Tinebase_Exception_Record_NotAllowed
     */
    public function setGrants($_containerId, Tinebase_Record_RecordSet $_grants, $_ignoreAcl = FALSE, $_failSafe = TRUE) 
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Grants::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to set grants of container denied.');
            }
        }
        
        // do failsafe check
        if ($_failSafe) {
            $adminGrant = FALSE;
            foreach ($_grants as $recordGrants) {
                if ($recordGrants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                    $adminGrant = TRUE;
                }
            }
            if (count($_grants) == 0 || ! $adminGrant) {
                throw new Tinebase_Exception_UnexpectedValue('You are not allowed to remove all (admin) grants for this container.');
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
                    'id'            => $recordGrants->getId(),
                    'container_id'  => $containerId,
                    'account_id'    => $recordGrants['account_id'],
                    'account_type'  => $recordGrants['account_type'],
                );
                if (empty($data['id'])) {
                    $data['id'] = $recordGrants->generateUID();
                }
                
                foreach ($recordGrants as $grantName => $grant) {
                    if (in_array($grantName, $recordGrants->getAllGrants()) && $grant === TRUE) {
                        $this->_getContainerAclTable()->insert($data + array('account_grant' => $grantName));
                    }
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->_setRecordMetaDataAndUpdate($containerId, 'update');
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
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
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_UnexpectedValue
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
            throw new Tinebase_Exception_Record_NotAllowed('Personal containers can have only one owner!', 403);
        }
    }
    
    /**
     * remove all container related entries from cache
     * 
     * @param int|Tinebase_Model_Container $containerId
     */
    protected function _clearCache($containerId) 
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($containerId);
        $cache = Tinebase_Core::getCache();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Removing all cache entries for container id ' . $containerId);
        
        $idsToDelete = array(
            'getContainerById' . $containerId . 'd0',
            'getContainerById' . $containerId . 'd1',
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
    protected function _getGrantsFromArray(array $_grantsArray, $_accountId, $_grantModel = 'Tinebase_Model_Grants')
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
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($containerId);

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
                $this->_getContentBackend()->create($contentRecord);
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
     * @param integer|Tinebase_Model_Container $containerId
     * @param integer $lastContentSeq
     * @return Tinebase_Record_RecordSet
     */
    public function getContentHistory($containerId, $lastContentSeq = 0)
    {
        $filter = new Tinebase_Model_ContainerContentFilter(array(
            array('field' => 'container_id', 'operator' => 'equals',  'value' => Tinebase_Model_Container::convertContainerIdToInt($containerId)),
            array('field' => 'content_seq',  'operator' => 'greater', 'value' => $lastContentSeq),
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => 'content_seq'
        ));
        
        $result = $this->_getContentBackend()->search($filter, $pagination);
        
        return $result;
    }

    /**
     * get content sequences for single container or array of ids
     * 
     * @param array|integer|Tinebase_Model_Container $containerIds
     * @return array with key = container id / value = content seq number | integer
     */
    public function getContentSequence($containerIds)
    {
        if (empty($containerIds)) {
            return NULL;
        }
        
        $containerIds = (! is_array($containerIds)) ? Tinebase_Model_Container::convertContainerIdToInt($containerIds) : $containerIds;
        
        $select = $this->_getSelect(array('id', 'content_seq'), TRUE);
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', (array) $containerIds));
        $stmt = $this->_db->query('/*' . __FUNCTION__ . '*/' . $select);
        $result = $stmt->fetchAll();
        foreach ($result as $key => $value) {
            $result[$value['id']] = $value['content_seq'];
        }
        
        $result = (is_array($containerIds)) ? $result : ((isset($result[$containerIds])) ? $result[$containerIds] : NULL);
        return $result;
    }
    
    /**
     * checks if container to delete is a "system" container 
     * 
     * @param array|integer|Tinebase_Model_Container $containerIds
     * @throws Tinebase_Exception_Record_SystemContainer
     * 
     * @TODO: generalize when there are more "system" containers
     * @todo move Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK to adb config
     */
    public function checkSystemContainer($containerIds)
    {
        if (!is_array($containerIds)) $containerIds = array($containerIds);
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        // at the moment, just the internal addressbook is checked
        try {
            $defaultAddressbook = $this->get($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])->toArray();
        } catch (Tinebase_Exception_NotFound $e) {
            $defaultAddressbook = null;
        }

        if ($defaultAddressbook && in_array($defaultAddressbook['id'], $containerIds)) {
            // _('You are not allowed to delete this Container. Please define another container as the default addressbook for internal contacts!')
            throw new Tinebase_Exception_Record_SystemContainer('You are not allowed to delete this Container. Please define another container as the default addressbook for internal contacts!');
        }
    }
    
    /**
     * create a new system container
     * - by default user group gets READ grant
     * - by default admin group gets all grants
     * 
     * @param Tinebase_Model_Application|string $application app record, app id or app name
     * @param string $name
     * @param string $idConfig save id in config if given
     * @param Tinebase_Record_RecordSet $grants use this to overwrite default grants
     * @param string $model the model the container contains
     * @return Tinebase_Model_Container
     */
    public function createSystemContainer($application, $name, $configId = NULL, Tinebase_Record_RecordSet $grants = NULL, $model = NULL)
    {
        $application = ($application instanceof Tinebase_Model_Application) ? $application : Tinebase_Application::getInstance()->getApplicationById($application);
        if ($model === null) {
            $controller = Tinebase_Core::getApplicationInstance($application->name);
            $model = $controller->getDefaultModel();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Creating system container for model ' . $model);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $name,
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => $application->getId(),
            'model'             => $model
        ));
        $groupsBackend = Tinebase_Group::getInstance();
        $grants = ($grants) ? $grants : new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'      => $groupsBackend->getDefaultGroup()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ    => true,
                Tinebase_Model_Grants::GRANT_EXPORT  => true,
                Tinebase_Model_Grants::GRANT_SYNC    => true,
            ),
            array(
                'account_id'      => $groupsBackend->getDefaultAdminGroup()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ    => true,
                Tinebase_Model_Grants::GRANT_ADD     => true,
                Tinebase_Model_Grants::GRANT_EDIT    => true,
                Tinebase_Model_Grants::GRANT_DELETE  => true,
                Tinebase_Model_Grants::GRANT_ADMIN   => true,
                Tinebase_Model_Grants::GRANT_EXPORT  => true,
                Tinebase_Model_Grants::GRANT_SYNC    => true,
            ),
        ), TRUE);
        
        $newContainer = Tinebase_Container::getInstance()->addContainer($newContainer, $grants, TRUE);

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
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $this->_clearCache($_record);
        
        return parent::update($_record);
    }
}
