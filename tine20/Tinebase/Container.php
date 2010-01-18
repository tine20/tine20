<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        refactor that: remove code duplication, remove Zend_Db_Table_Abstract usage
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
class Tinebase_Container
{
	/**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
		
    /**
     * the table object for the SQL_TABLE_PREFIX .container table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $containerTable;

    /**
     * the table object for the SQL_TABLE_PREFIX . container_acl table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $containerAclTable;
    
    /**
     * container backend class
     *
     * @var Tinebase_Container_Backend
     */
    protected $_backend;

    /**
     * the constructor
     */
    private function __construct() {
        $this->containerTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container'));
        $this->containerAclTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container_acl'));
        $this->_db = Tinebase_Core::getDb();
        $this->_backend = new Tinebase_Container_Backend();
    }
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
     * the singleton pattern
     *
     * @return Tinebase_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Container;
        }
        
        return self::$_instance;
    }

    /**
     * creates a new container
     *
     * @param   Tinebase_Model_Container $_container the new container
     * @param   Tinebase_Record_RecordSet $_grants the grants for the new folder 
     * @param   bool  $_ignoreAcl
     * @param   integer $_accountId
     * @return  Tinebase_Model_Container the newly created container
     * @throws  Tinebase_Exception_Record_Validation
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addContainer(Tinebase_Model_Container $_container, $_grants = NULL, $_ignoreAcl = FALSE, $_accountId = NULL)
    {
        if(!$_container->isValid()) {
            throw new Tinebase_Exception_Record_Validation('Invalid container object supplied.');
        }
        
        if ( $_accountId !== NULL ) {
            $accountId = $_accountId;
        } else {
            $accountId = Tinebase_Core::getUser()->getId();
        }
        
        if($_ignoreAcl !== TRUE) {
            switch($_container->type) {
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
        
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_container, 'create');
        $container = $this->_backend->create($_container);
        
        if($_grants === NULL) {
            if($container->type === Tinebase_Model_Container::TYPE_SHARED) {
    
                // add all grants to creator
                // add read grants to any other user
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    array(
                        'account_id'     => $accountId,
                        'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                        Tinebase_Model_Container::READGRANT      => true,
                        Tinebase_Model_Container::ADDGRANT       => true,
                        Tinebase_Model_Container::EDITGRANT      => true,
                        Tinebase_Model_Container::DELETEGRANT    => true,
                        Tinebase_Model_Container::ADMINGRANT     => true
                    ),            
                    array(
                        'account_id'      => '0',
                        'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                        Tinebase_Model_Container::READGRANT       => true
                    )            
                ));
            } else {
                // add all grants to creator only
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    array(
                        'account_id'     => $accountId,
                        'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                        Tinebase_Model_Container::READGRANT      => true,
                        Tinebase_Model_Container::ADDGRANT       => true,
                        Tinebase_Model_Container::EDITGRANT      => true,
                        Tinebase_Model_Container::DELETEGRANT    => true,
                        Tinebase_Model_Container::ADMINGRANT     => true
                    )            
                ));
            }
        } else {
            $grants = $_grants;
        }
        
        $this->setGrants($container->getId(), $grants, TRUE);
        
        return $container;
    }
    
    /**
     * add grants to container
     *
     * @todo    check that grant is not already given to container/type/accout combi
     * @param   int $_containerId
     * @param   int $_accountId
     * @param   array $_grants list of grants to add
     * @return  boolean
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function addGrants($_containerId, $_accountType, $_accountId, array $_grants, $_ignoreAcl = FALSE)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if($_ignoreAcl !== TRUE and !$this->hasGrant(Tinebase_Core::getUser(), $_containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
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
        
        //$existingGrants = $this->getGrantsOfAccount($accountId, $containerId);
        $id = Tinebase_Record_Abstract::generateUID();
         
        foreach($_grants as $grant) {
            $data = array(
                'id'            => $id,
                'container_id'  => $containerId,
                'account_type'  => $_accountType,
                'account_id'    => $accountId,
                'account_grant' => $grant
            );
            $this->containerAclTable->insert($data);
        }

        $this->_removeFromCache($containerId);
        
        return true;
    }
    
    
    /**
     * return all container, which the user has the requested right for
     * - cache the results because this function is called very often
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param   int    $_accountId
     * @param   string $_application the application name
     * @param   int    $_grant the required grant
     * @param   bool   $_onlyIds return only ids
     * @return  Tinebase_Record_RecordSet|array
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerByACL($_accountId, $_application, $_grant, $_onlyIds = FALSE)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' app: ' . $_application . ' / account: ' . $_accountId);

        $cache = Tinebase_Core::get('cache');
        $cacheId = convertCacheId('getContainerByACL' . $accountId . $_application . $_grant . $_onlyIds);
        $result = $cache->load($cacheId);
        
        if (!$result) {
            $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
            if(count($groupMemberships) === 0) {
                throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
            }
            
            $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_application)->getId();
            
            $tableContainer = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container');
            $tableContainerAcl = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container_acl');
            $colId = $this->_db->quoteIdentifier('id');
            #$colName = $this->_db->quoteIdentifier('name');
            $colContainerId = $this->_db->quoteIdentifier('container_id');
            $colApplicationId = $this->_db->quoteIdentifier('application_id');
            $colAccountGrant = $this->_db->quoteIdentifier('account_grant');
            $colAccountId = $this->_db->quoteIdentifier('account_id');
            $colAccountType = $this->_db->quoteIdentifier('account_type');
            
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'container', $_onlyIds ? 'id' : '*')
                ->join(
                    SQL_TABLE_PREFIX . 'container_acl',
                    $tableContainer . '.' . $colId . ' = ' . $tableContainerAcl . '.' . $colContainerId , 
                    array()
                )
                ->where($tableContainer . '.' . $colApplicationId . ' = ?', $applicationId)
                ->where($tableContainerAcl . '.' . $colAccountGrant . ' = ?', $_grant)
                ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0')
                
                # beware of the extra parenthesis of the next 3 rows
                ->where('(' . $tableContainerAcl . '.' . $colAccountId . ' = ? AND ' . 
                    $tableContainerAcl . "." . $colAccountType . " = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
                ->orWhere($tableContainerAcl . '.' . $colAccountId . ' IN (?) AND ' . 
                    $tableContainerAcl . "." . $colAccountType . " = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
                ->orWhere($tableContainerAcl . '.' . $colAccountType . ' = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
                
                ->group(SQL_TABLE_PREFIX . 'container.id')
                ->order(SQL_TABLE_PREFIX . 'container.name');
    
            $stmt = $this->_db->query($select);
            
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            if(empty($rows)) {
                // no containers found. maybe something went wrong when creating the initial folder
                // any account should have at least one personal folder
                // let's check if the controller of the application has a function to create the needed folders
                $result = ($_onlyIds) ? array() : new Tinebase_Record_RecordSet('Tinebase_Model_Container');
                try {
                    $application = Tinebase_Core::getApplicationInstance($_application);
                    
                    if($application instanceof Tinebase_Container_Interface) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' create personal folders for application ' . $_application);
                        $personalContainers = $application->createPersonalFolder($_accountId);
                        $result = ($_onlyIds) ? $personalContainers->getArrayOfIds() : $personalContainers;
                    }
                } catch (Tinebase_Exception_NotFound $enf) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' no containers available in application ' . $_application);
                }
            } else {
                if ($_onlyIds) {
                    $result = array();
                    foreach ($rows as $row) {
                        $result[] = $row['id'];
                    }
                } else {
                    $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $rows);
                }
            }
            
            // save result and tag it with 'container'
            $cache->save($result, $cacheId, array('container'));
        }
            
        return $result;
        
    }
    
    /**
     * return a container by containerId
     * - cache the results because this function is called very often
     *
     * @param   int|Tinebase_Model_Container $_containerId the id of the container
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerById($_containerId)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        // load from cache
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $result = $cache->load('getContainerById' . $containerId);

        if(!$result) {
            $result = $this->_backend->get($containerId);

            $cache->save($result, 'getContainerById' . $containerId);
        }
        
        return $result;
        
    }
    
    /**
     * return a container by container name
     *
     * @param   int|Tinebase_Model_Container $_containerId the id of the container
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function getContainerByName($_application, $_containerName, $_type)
    {
        if($_type !== Tinebase_Model_Container::TYPE_INTERNAL and $_type !== Tinebase_Model_Container::TYPE_PERSONAL and $_type !== Tinebase_Model_Container::TYPE_SHARED) {
            throw new Tinebase_Exception_UnexpectedValue ('Invalid type $_type supplied.');
        }
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_application)->getId();
        
        $colName = $this->containerTable->getAdapter()->quoteIdentifier('name');
        $colType = $this->containerTable->getAdapter()->quoteIdentifier('type');
        $colApplicationId = $this->containerTable->getAdapter()->quoteIdentifier('application_id');
        $colIsDeleted = $this->containerTable->getAdapter()->quoteIdentifier('is_deleted');
        
        $select = $this->containerTable->select()
            ->where($colName . ' = ?', $_containerName)
            ->where($colType . ' = ?', $_type)
            ->where($colApplicationId . ' = ?', $applicationId)
            ->where($colIsDeleted . ' = 0');

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $row = $this->containerTable->fetchRow($select);
        
        if ($row === NULL) {
            throw new Tinebase_Exception_NotFound('Container ' . $_containerName . ' not found.');
        }
        
        $result = new Tinebase_Model_Container($row->toArray());
        
        return $result;
        
    }
    
    /**
     * returns the internal conatainer for a given application
     *
     * @param   int|Tinebase_Model_User $_accountId
     * @param   string $_application name of the application
     * @return  Tinebase_Model_Container the internal container
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getInternalContainer($_accountId, $_application)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_application)->getId();
        
        $select  = $this->containerTable->select()
            ->where('type = ?', Tinebase_Model_Container::TYPE_INTERNAL)
            ->where('application_id = ?', $applicationId);

        $row = $this->containerTable->fetchRow($select);
        
        if($row === NULL) {
            throw new Tinebase_Exception_NotFound('No internal container found.');
        }

        $container = new Tinebase_Model_Container($row->toArray());
        
        if(!$this->hasGrant($_accountId, $container, Tinebase_Model_Container::GRANT_READ)) {
            throw new Tinebase_Exception_AccessDenied('Permission to container denied.');
        }
        
        return $container;        
    }
    
    /**
     * returns the personal container of a given account accessible by a another given account
     *
     * @param   int|Tinebase_Model_User $_accountId
     * @param   string                  $_application
     * @param   int|Tinebase_Model_User $_owner
     * @param   int                     $_grant
     * @param   bool                    $_ignoreACL
     * @return  Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getPersonalContainer($_accountId, $_application, $_owner, $_grant, $_ignoreACL=false)
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        if(count($groupMemberships) === 0) {
            throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
        }
        $ownerId            = Tinebase_Model_User::convertUserIdToInt($_owner);
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $this->_db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(
                array('user' => SQL_TABLE_PREFIX . 'container_acl'),
                'owner.container_id = user.container_id', 
                array()
            )
            ->join(SQL_TABLE_PREFIX . 'container', 'owner.container_id = ' . SQL_TABLE_PREFIX . 'container.id')
            ->where('owner.account_id = ?', $ownerId)
            ->where('owner.account_grant = ?', Tinebase_Model_Container::GRANT_ADMIN)
            
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->getId())
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', Tinebase_Model_Container::TYPE_PERSONAL)
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container.is_deleted') . ' = 0')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        if ($_ignoreACL !== true) {
            $select->where('user.account_grant = ?', $_grant)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
            ->orWhere('user.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        }
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $this->_db->query($select);
        
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if(empty($rows) and $accountId === $ownerId) {
            // no containers found. maybe something went wrong when creating the initial folder
            // let's check if the controller of the application has a function to create the needed folders
            $application = Tinebase_Core::getApplicationInstance($application);
            
            if($application instanceof Tinebase_Container_Interface) {
                return $application->createPersonalFolder($accountId);
            }
        }

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $rows);
        
        return $result;
    }
    
    /**
     * gets default container of given user for given app
     *  - returns personal first container at the moment
     *
     * @param string $_accountId
     * @param string $_applicationName
     * @return Tinebase_Model_Container
     * 
     * @todo return default container from preferences if available
     * @todo create new default/personal container if it was deleted
     */
    public function getDefaultContainer($_accountId, $_applicationName)
    {
        return $this->getPersonalContainer(
            $_accountId, 
            $_applicationName, 
            $_accountId, 
            Tinebase_Model_Container::GRANT_ADD
        )->getFirstRecord();
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param   int|Tinebase_Model_User $_accountId
     * @param   string $_application the name of the application
     * @param   int $_grant
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getSharedContainer($_accountId, $_application, $_grant)
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        if(count($groupMemberships) === 0) {
            throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl', array())
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id')

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . 
                SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . 
                SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
            
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->getId())
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', Tinebase_Model_Container::TYPE_SHARED)
            ->where(SQL_TABLE_PREFIX . 'container_acl.account_grant = ?', $_grant)
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container.is_deleted') . ' = 0')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $this->_db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * return users which made personal containers accessible to current account
     *
     * @param   int|Tinebase_Model_User $_accountId
     * @param   string $_application the name of the application
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_User
     * @throws  Tinebase_Exception_NotFound
     */
    public function getOtherUsers($_accountId, $_application, $_grant)
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        if(count($groupMemberships) === 0) {
            throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $this->_db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array('account_id'))
            ->join(array('user' => SQL_TABLE_PREFIX . 'container_acl'),'owner.container_id = user.container_id', array())
            ->join(array('contacts' => SQL_TABLE_PREFIX . 'addressbook'),'owner.account_id = contacts.account_id', array())
            ->join(SQL_TABLE_PREFIX . 'container', 'user.container_id = ' . SQL_TABLE_PREFIX . 'container.id', array())
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant = ?', Tinebase_Model_Container::GRANT_ADMIN)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
            ->orWhere('user.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
            
            ->where('user.account_grant = ?', $_grant)
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->getId())
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', Tinebase_Model_Container::TYPE_PERSONAL)
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container.is_deleted') . ' = 0')
            
            ->order('contacts.n_fileas')
            ->group('owner.account_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_User');
        
        foreach($rows as $row) {
         try {
                $account = Tinebase_User::getInstance()->getUserById($row['account_id']);
                $result->addRecord($account);
            } catch (Tinebase_Exception_NotFound $e) {
                // user does not exist any longer (hotfix)
            }
        }
        
        return $result;
    }
    
    /**
     * return set of all personal container of other users made accessible to the current account 
     *
     * @param   int|Tinebase_Model_User $_accountId
     * @param   string $_application the name of the application
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getOtherUsersContainer($_accountId, $_application, $_grant)
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        if(count($groupMemberships) === 0) {
            throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $this->_db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(
                array('user' => SQL_TABLE_PREFIX . 'container_acl'),
                'owner.container_id = user.container_id', 
                array())
            ->join(SQL_TABLE_PREFIX . 'container', 'user.container_id = ' . SQL_TABLE_PREFIX . 'container.id')
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant = ?', Tinebase_Model_Container::GRANT_ADMIN)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
            ->orWhere('user.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
            
            ->where('user.account_grant = ?', $_grant)
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->getId())
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', Tinebase_Model_Container::TYPE_PERSONAL)
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'container.is_deleted') . ' = 0')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $this->_db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * gets path of given container
     *
     * @param  Tinebase_Model_Container $_container
     * @return string path
     */
    public function getPath($_container)
    {
        $path = "/{$_container->type}";
        switch ($_container->type) {
            case 'internal':
                break;
            case 'shared':
                $path .= "/{$_container->getId()}";
                break;
            case 'personal':
                // we need to find out who has admin grant
                $allGrants = $this->getGrantsOfContainer($_container, true);
                
                $userId = NULL;
                foreach ($allGrants as $grants) {
                    if ($grants->adminGrant === true) {
                        $userId = $grants->account_id;
                        break;
                    }
                }
                if (! $userId) {
                    throw new Exception('could not find container admin');
                }
                
                $path .= "/$userId/{$_container->getId()}";
                break;
            default:
                throw new Exception("unknown container type: '{$_container->type}'");
                break;
        }
        return $path;
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
        
        if($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to delete container denied.');
            }
            
            if($container->type !== Tinebase_Model_Container::TYPE_PERSONAL and $container->type !== Tinebase_Model_Container::TYPE_SHARED) {
                throw new Tinebase_Exception_InvalidArgument('Can delete personal or shared containers only.');
            }
        }
        
        //$this->_backend->delete($containerId);
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($container, 'delete', $container);
        $this->_backend->update($container);
        
        $this->_removeFromCache($containerId);
        
        /*
        // move all contained objects to next available personal container and try again to delete container
        $app = Tinebase_Application::getApplicationById($container->application_id);

        // get personal containers
        $personalContainers = $this->getPersonalContainer(
            Tinebase_Core::getUser(),
            $app->name,
            $container->owner,
            Tinebase_Model_Container::GRANT_ADD
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
     * delete container by application id
     * 
     * @param string $_applicationId
     * @return integer numer of deleted containers 
     */
    public function deleteContainerByApplicationId($_applicationId)
    {
        return $this->_backend->deleteByProperty($_applicationId, 'application_id');
    }    
    
    /**
     * set container name, if the user has the required right
     *
     * @param   int $_containerId
     * @param   string $_containerName the new name
     * @return  Tinebase_Model_Container
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function setContainerName($_containerId, $_containerName)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);

        if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Permission to rename container denied.');
        }
        
        $where = array(
            $this->containerTable->getAdapter()->quoteInto('id = ?', $containerId)
        );
        
        $data = array(
            'name' => $_containerName
        );
        
        $this->containerTable->update($data, $where);

        $this->_removeFromCache($containerId);
        
        return $this->getContainerById($_containerId);
    }
    
    /**
     * check if the given user user has a certain grant
     *
     * @param   int $_accountId
     * @param   int|Tinebase_Model_Container $_containerId
     * @param   int $_grant
     * @return  boolean
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_NotFound
     */
    public function hasGrant($_accountId, $_containerId, $_grant) 
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        $grant = (int)$_grant;
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = convertCacheId('hasGrant' . $accountId . $containerId . $grant);
        $result = $cache->load($cacheId);
        
        if (! $result) {
            if($grant != $_grant) {
                throw new Tinebase_Exception_InvalidArgument('$_grant must be integer');
            }
            
            $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
            if(count($groupMemberships) === 0) {
                throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
            }
            
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'container_acl', array())
                ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id', array('id'))
    
                # beware of the extra parenthesis of the next 3 rows
                ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . 
                    SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
                ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . 
                    SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
                ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
                
                ->where(SQL_TABLE_PREFIX . 'container_acl.account_grant = ?', $grant)
                ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId)
                ;
                        
            //error_log("getContainer:: " . $select->__toString());
    
            $stmt = $this->_db->query($select);
            
            $grants = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            if(empty($grants)) {
                $result = FALSE;
            } else {
                $result = TRUE;
            }
            
            // save result and tag it with 'container'
            $cache->save($result, $cacheId, array('container'));
        }
        
        return $result;
    }
    
    /**
     * get all grants assigned to this container
     *
     * @param   int|Tinebase_Model_Container $_containerId
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = FALSE) 
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if($_ignoreAcl !== TRUE) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to get grants of container denied.');
            }            
        }
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'container', array('id'))
            ->join(SQL_TABLE_PREFIX . 'container_acl', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id', 
                array('id', 'account_type', 'account_id', 'account_grants' => 'GROUP_CONCAT(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)'))
            ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId)
            ->group(array(SQL_TABLE_PREFIX . 'container.id', SQL_TABLE_PREFIX . 'container_acl.account_type', SQL_TABLE_PREFIX . 'container_acl.account_id'));

        //error_log("getAllGrants:: " . $select->__toString());

        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');

        // @todo use _getGrantsFromArray here
        foreach($rows as $row) {
        	
            $grants = explode(',', $row['account_grants']);
            foreach($grants as $grant) {
                $row[Tinebase_Model_Container::$GRANTNAMEMAP[$grant]] = TRUE;
            }
        	
            $containerGrant = new Tinebase_Model_Grants($row);

            $result->addRecord($containerGrant);
        }

        return $result;
    }
    
    /**
     * get grants assigned to one account of one container
     *
     * @param int|Tinebase_Model_User $_accountId the account to get the grants for
     * @param int|Tinebase_Model_Container $_containerId
     * @return Tinebase_Model_Grants
     */
    public function getGrantsOfAccount($_accountId, $_containerId, $_ignoreAcl = FALSE) 
    {
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $containerId        = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $cacheKey = convertCacheId('getGrantsOfAccount' . $containerId . $accountId . (int)$_ignoreAcl);
        // load from cache
        $cache = Tinebase_Core::get('cache');
        $grants = $cache->load($cacheKey);

        if(!$grants) {
            $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
            
            #if($_ignoreAcl !== TRUE) {
            #    if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
            #        throw new Exception('permission to get grants of container denied');
            #    }            
            #}
            
            if(count($groupMemberships) === 0) {
                throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
            }
            
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'container_acl', array('account_grant'))
                ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id')
    
                # beware of the extra parenthesis of the next 3 rows
                ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . 
                    SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
                ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . 
                    SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
                ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
    
                ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId)
                ->group(SQL_TABLE_PREFIX . 'container_acl.account_grant');
    
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
    
            $stmt = $this->_db->query($select);
    
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
    
	        $grants = $this->_getGrantsFromArray($rows, $accountId);
            
            $cache->save($grants, $cacheKey);
        }
        return $grants;
    }
    
    /**
     * get grants assigned to multiple records
     *
     * @param   Tinebase_Record_RecordSet $_records records to get the grants for
     * @param   int|Tinebase_Model_User $_accountId the account to get the grants for
     * @param   string $_containerProperty container property
     * @throws  Tinebase_Exception_NotFound
     */
    public function getGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId, $_containerProperty = 'container_id')
    {
        if (count($_records) === 0) {
            return;
        }
        
        $accountId          = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        if(count($groupMemberships) === 0) {
            throw new Tinebase_Exception_NotFound('Account must be in at least one group.');
        }

        // get container ids
        $containers = array();
        foreach ($_records as $record) {
            if ($record[$_containerProperty] && !isset($containers[$record[$_containerProperty]])) {
                $containers[Tinebase_Model_Container::convertContainerIdToInt($record[$_containerProperty])] = array();
            }
        }
        
        if (empty($containers)) {
        	return;
        }
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl', array('account_grants' => 'GROUP_CONCAT(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)'))
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id')

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_USER . "'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type = '" . Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP . "'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)

            ->where(SQL_TABLE_PREFIX . 'container.id IN (?)', array_keys($containers))
            
            ->group(SQL_TABLE_PREFIX . 'container.id');

        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // add results to container ids and get grants array
        foreach ($rows as $row) {
            $grantsArray = array_unique(explode(',', $row['account_grants']));
            $row['account_grants'] = $this->_getGrantsFromArray($grantsArray, $accountId)->toArray();
            $containers[$row['id']] = $row;
        }
        
        // add container & grants to records
        foreach ($_records as &$record) {
            $containerId = $record[$_containerProperty];
            
            if (! is_array($containerId) && ! $containerId instanceof Tinebase_Record_Abstract && ! empty($containers[$containerId])) {
                $record[$_containerProperty] = $containers[$containerId];
            }
        }
    }
    
    /**
     * set all grant for given container
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
            // if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
            if(!$this->hasGrant(Tinebase_Core::getUser(), $containerId, Tinebase_Model_Container::GRANT_ADMIN)) {
                throw new Tinebase_Exception_AccessDenied('Permission to set grants of container denied.');
            }            
        }
        
        // do failsafe check
        if ($_failSafe) {
            $adminGrant = FALSE;
            foreach ($_grants as $recordGrants) {
                if ($recordGrants->{Tinebase_Model_Container::ADMINGRANT}) {
                    $adminGrant = TRUE;
                }
            }
            if (count($_grants) == 0 || ! $adminGrant) {
                throw new Tinebase_Exception_UnexpectedValue('You are not allowed to remove all (admin) grants for this container.');
            }
        }
        
        $container = $this->getContainerById($containerId);
       
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            $where = $this->containerAclTable->getAdapter()->quoteInto('container_id = ?', $containerId);
            $this->containerAclTable->delete($where);
            
            foreach($_grants as $recordGrants) {
                $data = array(
                    'id'            => $recordGrants->getId(),
                    'container_id'  => $containerId,
                    'account_id'    => $recordGrants['account_id'],
                    'account_type'  => $recordGrants['account_type'],
                );
                if(empty($data['id'])) {
                    $data['id'] = $recordGrants->generateUID();
                }
                
                foreach ($recordGrants as $grantName => $grant) {
                    if (in_array($grantName, array_values(Tinebase_Model_Container::$GRANTNAMEMAP)) && $grant === true) {
                        $this->containerAclTable->insert($data + array('account_grant' => array_value($grantName, array_flip(Tinebase_Model_Container::$GRANTNAMEMAP))));
                    }
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->_removeFromCache($containerId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();            
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
        
        return $this->getGrantsOfContainer($containerId, $_ignoreAcl);
    }
    
    /**
     * remove container from cache if cache is available
     *
     * @param string $_cacheId
     * 
     * @todo memcached can't use tags -> clear complete cache or don't use tags in caching?
     */
    protected function _removeFromCache($_containerId) 
    {        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        
        if (!$cache || !$cache->getOption('caching')) {
            return;
        }

        if (ucfirst(Tinebase_Core::getConfig()->caching->backend) !== 'Memcached') {
            try {
                $accountId          = Tinebase_Model_User::convertUserIdToInt(Tinebase_Core::getUser());
                $cache->remove('getGrantsOfAccount' . $_containerId . $accountId . 0);                
                $cache->remove('getGrantsOfAccount' . $_containerId . $accountId . 1);                
            } catch (Exception $e) {
                // no user account set
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'No user account set. Error: ' . $e->getMessage());
            }
            $cache->remove('getContainerById' . $_containerId);
            $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('container'));
        } else {
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);                
        }
    }

    /**
     * get grants record from an array with grant values
     *
     * @param array $_grantsArray
     * @param int $_accountId
     * @return Tinebase_Model_Grants
     */
    protected function _getGrantsFromArray(array $_grantsArray, $_accountId)
    {
        $grants = array();
        foreach($_grantsArray as $key => $value) {
            $grantValue = (is_array($value)) ? $value['account_grant'] : $value; 
            $grants[Tinebase_Model_Container::$GRANTNAMEMAP[$grantValue]] = TRUE;
        }
        $grantsFields = array(
            'account_id'     => $_accountId,
            'account_type'   => 'account',
        );
        $grantsFields = array_merge($grantsFields, $grants);
        
        $grants = new Tinebase_Model_Grants($grantsFields);

        return $grants;
    }
    
    /**
     * move records to container
     * 
     * @param string $_targetContainerId
     * @param array $_recordIds
     * @param string $_applicationName
     * @param string $_modelName
     * @param string $_containerProperty
     * @return void
     * @throws Tinebase_Exception_AccessDenied|Tinebase_Exception_NotFound
     */
    public function moveRecordsToContainer($_targetContainerId, $_recordIds, $_applicationName, $_modelName, $_containerProperty = 'container_id')
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Moving ' 
            . count($_recordIds) . ' records to ' . $_applicationName . ' / ' . $_modelName . ' container ' . $_targetContainerId
        );
        
        $userId = Tinebase_Core::getUser()->getId();
        
        // check add grant in target container
        if (! $this->hasGrant($userId, $_targetContainerId, Tinebase_Model_Container::GRANT_ADD)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Permission denied to add records to container.');
            throw new Tinebase_Exception_AccessDenied('You are not allowed to move records to this container');
        }
        
        // get records
        $recordController = Tinebase_Core::getApplicationInstance($_applicationName, $_modelName);
        $records = $recordController->getMultiple($_recordIds);
        
        // check delete grant in source container
        $containerIdsWithDeleteGrant = $this->getContainerByACL($userId, $_applicationName, Tinebase_Model_Container::GRANT_DELETE, TRUE);
        foreach ($records as $index => $record) {
            if (! in_array($record->{$_containerProperty}, $containerIdsWithDeleteGrant)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Permission denied to remove record ' . $record->getId() . ' from container ' . $record->{$_containerProperty}
                ); 
                unset($records[$index]);
            }
        }
        
        // move (update container id)
        $filterClass = $_applicationName . '_Model_' . $_modelName . 'Filter';
        if (! class_exists($filterClass)) {
            throw new Tinebase_Exception_NotFound('Filter class ' . $filterClass . ' not found!');
        }
        $filter = new $filterClass(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $records->getArrayOfIds())
        ));
        $data[$_containerProperty] = $_targetContainerId;
        $recordController->updateMultiple($filter, $data);
    }
}
