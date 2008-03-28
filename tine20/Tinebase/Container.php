<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
     * constant for no grants
     *
     */
    const GRANT_NONE = 0;

    /**
     * constant for read grant
     *
     */
    const GRANT_READ = 1;

    /**
     * constant for add grant
     *
     */
    const GRANT_ADD = 2;

    /**
     * constant for edit grant
     *
     */
    const GRANT_EDIT = 4;

    /**
     * constant for delete grant
     *
     */
    const GRANT_DELETE = 8;

    /**
     * constant for admin grant
     *
     */
    const GRANT_ADMIN = 16;

    /**
     * constant for all grants
     *
     */
    const GRANT_ANY = 31;
    
    /**
     * type for internal contaier
     * 
     * for example the internal addressbook
     *
     */
    const TYPE_INTERNAL = 'internal';
    
    /**
     * type for personal containers
     *
     */
    const TYPE_PERSONAL = 'personal';
    
    /**
     * type for shared container
     *
     */
    const TYPE_SHARED = 'shared';
    
    /**
     * the constructor
     */
    private function __construct() {
        $this->containerTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container'));
        $this->containerAclTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'container_acl'));
    }
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
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
     * creates a shared container and gives all rights to the owner and read rights to anyone
     *
     * @param int $_accountId the accountId of the owner of the newly created container
     * @param string $_application name of the application
     * @param string $_containerName displayname of the container
     * @return Tinebase_Model_Container
     */
    public function addSharedContainer($_accountId, $_application, $_containerName)
    {
        $containerId = $this->addContainer($_application, $_containerName, self::TYPE_SHARED, 'Sql');

        // add all grants to creator
        $this->addGrants($containerId, $_accountId, array(
            self::GRANT_READ, 
            self::GRANT_ADD, 
            self::GRANT_EDIT, 
            self::GRANT_DELETE, 
            self::GRANT_ADMIN
        ));
        // add read grants to any other user
        $this->addGrants($containerId, 'anyone', NULL, array(
            self::GRANT_READ
        ));
        return $this->getContainerById($containerId);
    }
    
    /**
     * creates a personal container and gives all rights to the owner
     *
     * @param int $_accountId the accountId of the owner of the newly created container
     * @param string $_application name of the application
     * @param string $_containerName displayname of the container
     * @return Tinebase_Model_Container
     */
    public function addPersonalContainer($_accountId, $_application, $_containerName)
    {
        $containerId = $this->addContainer($_application, $_containerName, self::TYPE_PERSONAL, 'Sql');
        
        // add all grants to creator
        $this->addGrants($containerId, 'account', $_accountId, array(
            self::GRANT_READ, 
            self::GRANT_ADD, 
            self::GRANT_EDIT, 
            self::GRANT_DELETE, 
            self::GRANT_ADMIN
        ));
        return $this->getContainerById($containerId);
    }
    
    /**
     * creates a new container
     *
     * @param string $_application the name of the application
     * @param string $_name the name of the container
     * @param int $_type the type of the container(Tinebase_Container::TYPE_SHARED, Tinebase_Container::TYPE_PERSONAL. Tinebase_Container::TYPE_INTERNAL)
     * @param string $_backend type of the backend. for eaxmple: sql, ldap, ...
     * @return int the id of the newly create container
     */
    public function addContainer($_application, $_name, $_type, $_backend)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        $data = array(
            'name'    => $_name,
            'type'    => $_type,
            'backend' => $_backend,
            'application_id'    => $application->id
        );
        $containerId = $this->containerTable->insert($data);
        
        if($containerId < 1) {
            throw new UnexpectedValueException('$containerId can not be 0');
        }
        
        return $containerId;
    }
    
    /**
     * creates a new container
     *
     * @param Tinebase_Model_Container $_container the new container
     * @return Tinebase_Model_Container the newly created container
     */
    public function new_addContainer(Tinebase_Model_Container $_container)
    {
        if(!$_container->isValid()) {
            throw new Exception('invalid container object supplied');
        }
        
        $data = array(
            'name'              => $_container->name,
            'type'              => $_container->type,
            'backend'           => $_container->backend,
            'application_id'    => $_container->application_id
        );
        
        $containerId = $_container->getId();
        
        if($containerId === NULL) {
            $containerId = $this->containerTable->insert($data);
        } else {
            $data['id'] = $containerId;
            $this->containerTable->insert($data);
        }
        
        if($containerId < 1) {
            throw new UnexpectedValueException('$containerId can not be 0');
        }
        
        return $this->getContainer($containerId);
    }
    
    /**
     * add grants to container
     *
     * @param int $_containerId
     * @param int $_accountId
     * @param array $_grants list of grants to add
     * @return boolean
     */
    public function addGrants($_containerId, $_accountType, $_accountId, array $_grants)
    {
        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        switch($_accountType) {
            case 'account':
                $accountId = Tinebase_Account::convertAccountIdToInt($_accountId);
                break;
            case 'group':
                $accountId = Tinebase_Group::convertGroupIdToInt($_accountId);
                break;
            case 'anyone':
                $accountId = NULL;
                break;
            default:
                throw new InvalidArgumentException('invalid $_accountType');
                break;
        }
        
        foreach($_grants as $grant) {
            $grant = (int)$grant;
            
            if($grant === 0 || $grant > self::GRANT_ADMIN) {
                throw new InvalidArgumentException('$_grant must be integer and can not be greater than ' . self::GRANT_ADMIN);
            }
            if($grant > 1 && $grant % 2 !== 0) {
                throw new InvalidArgumentException('you can only set one grant(1,2,4,8,...) at once');
            }
            
            $data = array(
                'container_id'  => $containerId,
                'account_type'  => $_accountType,
                'account_id'    => $accountId,
                'account_grant' => $grant
            );
            $this->containerAclTable->insert($data);
        }
                        
        return true;
    }
    
    /**
     * returns the internal conatainer for a given application
     *
     * @param string $_application name of the application
     * @return Tinebase_Model_Container the internal container
     */
    public function getInternalContainer($_application)
    {
        $accountId          = Zend_Registry::get('currentAccount')->accountId;
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        $application        = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        if(count($groupMemberships) === 0) {
            throw new Exception('account must be in at least one group');
        }
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl', array('account_grants' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)'))
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id')

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', self::TYPE_INTERNAL)
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->having('account_grants & ?', self::GRANT_READ)
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getInternalContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        $result = new Tinebase_Model_Container($stmt->fetch(Zend_Db::FETCH_ASSOC), true);
        
        if(empty($result)) {
            throw new Exception('internal container not found or not accessible');
        }

        return $result;
        
    }
    
    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     * 
     * @param int $_accountId
     * @param string $_application the application name
     * @param int $_right the required right
     * @return Tinebase_Record_RecordSet
     */
    public function getContainerByACL($_accountId, $_application, $_right)
    {
        $accountId = Tinebase_Account::convertAccountIdToInt($_accountId);
        
        $right = (int)$_right;
        if($right != $_right) {
            throw new InvalidArgumentException('$_right must be integer');
        }
        
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        #$groupMemberships[] = $accountId;
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
               
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container')
            ->join(
                SQL_TABLE_PREFIX . 'container_acl',
                SQL_TABLE_PREFIX . 'container.id = ' . SQL_TABLE_PREFIX . 'container_acl.container_id', 
                array('account_grants' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)')
            )
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            
            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->having('account_grants & ?', $right)
            ->order(SQL_TABLE_PREFIX . 'container.name');

        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * return a container by containerId
     *
     * @param int $_containerId the id of the container
     * @return Tinebase_Model_Container
     */
    public function getContainerById($_containerId)
    {
        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        
        $groupMemberships   = Zend_Registry::get('currentAccount')->getGroupMemberships();
        #$groupMemberships[] = $accountId;
        

        if(!$this->hasGrant($accountId, $containerId, self::GRANT_READ)) {
            throw new Exception('permission to container denied');
        }
        
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container')
            ->join(
                SQL_TABLE_PREFIX . 'container_acl',
                SQL_TABLE_PREFIX . 'container.id = ' . SQL_TABLE_PREFIX . 'container_acl.container_id', 
                array('account_grants' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)')
            )
            ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId)

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->order(SQL_TABLE_PREFIX . 'container.name');

        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        $result = new Tinebase_Model_Container($stmt->fetch(Zend_Db::FETCH_ASSOC));
        
        if(empty($result)) {
            throw new UnderflowException('container not found');
        }
        
        return $result;
        
    }
    
    /**
     * return a container by containerId
     *
     * @todo move acl check to another place
     * @param int|Tinebase_Model_Container $_containerId the id of the container
     * @return Tinebase_Model_Container
     */
    public function getContainer($_containerId)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        
        if(!$this->hasGrant($accountId, $containerId, self::GRANT_READ)) {
            throw new Exception('permission to container denied');
        }
        
        $groupMemberships   = Zend_Registry::get('currentAccount')->getGroupMemberships();
        
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container')
            ->join(
                SQL_TABLE_PREFIX . 'container_acl',
                SQL_TABLE_PREFIX . 'container.id = ' . SQL_TABLE_PREFIX . 'container_acl.container_id', 
                array('account_grants' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)')
            )
            ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId)

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->order(SQL_TABLE_PREFIX . 'container.name');

        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        $result = new Tinebase_Model_Container($stmt->fetch(Zend_Db::FETCH_ASSOC));
        
        if(empty($result)) {
            throw new UnderflowException('container not found');
        }
        
        return $result;
        
    }
    
    /**
     * returns the personal container of a given account accessible by the current user
     *
     * @param string $_application the name of the application
     * @param int $_owner the numeric account id of the owner
     * @todo remove this function
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function _getPersonalContainer($_application, $_owner)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' depricated use');
        
        return $this->getPersonalContainer2(Zend_Registry::get('currentAccount'), $_application, $_owner, self::GRANT_READ);
    }
    
    /**
     * returns the personal container of a given account accessible by a another given account
     *
     * @param Tinebase_Account_Model_Account $_account
     * @param unknown_type $_application
     * @param unknown_type $_owner
     * @param unknown_type $_grant
     * @todo rename this function to getPersonalContainer
     * @return unknown
     */
    public function getPersonalContainer(Tinebase_Account_Model_Account $_account, $_application, $_owner, $_grant)
    {
        $accountId  = Tinebase_Account::convertAccountIdToInt($_account);
        $ownerId    = Tinebase_Account::convertAccountIdToInt($_owner);
        
        $groupMemberships   = $_account->getGroupMemberships();
        
        if(count($groupMemberships) === 0) {
            throw new Exception('account must be in at least one group');
        }
        
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(
                array('user' => SQL_TABLE_PREFIX . 'container_acl'),
                'owner.container_id = user.container_id', 
                array('account_grants' => 'BIT_OR(user.account_grant)')
            )
            ->join(SQL_TABLE_PREFIX . 'container', 'owner.container_id = ' . SQL_TABLE_PREFIX . 'container.id')
            ->where('owner.account_id = ?', $ownerId)
            ->where('owner.account_grant = ?', self::GRANT_ADMIN)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type ='account'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type ='group'", $groupMemberships)
            ->orWhere('user.account_type = ?)', 'anyone')
            
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', self::TYPE_PERSONAL)
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->having('account_grants & ?', $_grant)
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param string $_application the name of the application
     * @todo remove this functions
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function _getSharedContainer($_application)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' depricated use');
        
        return $this->getSharedContainer2(Zend_Registry::get('currentAccount'), $_application, self::GRANT_READ);
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param string $_application the name of the application
     * @todo rename this function to getSharedContainer
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getSharedContainer(Tinebase_Account_Model_Account $_account, $_application, $_grant)
    {
        $groupMemberships   = $_account->getGroupMemberships();
        $accountId          = $_account->accountId;
        
        if(count($groupMemberships) === 0) {
            throw new Exception('account must be in at least one group');
        }
        
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl', array('account_grants' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'container_acl.account_grant)'))
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id')

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', self::TYPE_SHARED)
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->having('account_grants & ?', $_grant)
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * return users which made personal containers accessible to current account
     *
     * @param string $_application the name of the application
     * @todo remove this function
     * @return array list of accountids
     */
    public function _getOtherUsers($_application)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' depricated use');
        
        return $this->getOtherUsers2(Zend_Registry::get('currentAccount'), $_application, self::GRANT_READ);
    }
    
    /**
     * return users which made personal containers accessible to current account
     *
     * @param string $_application the name of the application
     * @todo rename this function to getOtherUsers
     * @return Tinebase_Record_RecordSet set of Tinebase_Account_Model_Account
     */
    public function getOtherUsers(Tinebase_Account_Model_Account $_account, $_application, $_grant)
    {
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        
        $groupMemberships   = $_account->getGroupMemberships();

        if(count($groupMemberships) === 0) {
            throw new Exception('account must be in at least one group');
        }
        
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array('account_id'))
            ->join(array('user' => SQL_TABLE_PREFIX . 'container_acl'),'owner.container_id = user.container_id', array())
            ->join(SQL_TABLE_PREFIX . 'container', 'user.container_id = ' . SQL_TABLE_PREFIX . 'container.id', array())
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant = ?', self::GRANT_ADMIN)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type ='account'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type ='group'", $groupMemberships)
            ->orWhere('user.account_type = ?)', 'anyone')
            
            ->where('user.account_grant = ?', $_grant)
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', self::TYPE_PERSONAL)
            ->order(SQL_TABLE_PREFIX . 'container.name')
            ->group('owner.account_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Tinebase_Record_RecordSet('Tinebase_Account_Model_Account');
        $accountsBackend = Tinebase_Account::getInstance();
        
        foreach($rows as $row) {
            $account = $accountsBackend->getAccountById($row['account_id']);
            $result->addRecord($account);
        }
        
        return $result;
    }
    
    /**
     * return set of all personal container of other users made accessible to the current account 
     *
     * @param string $_application the name of the application
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getOtherUsersContainer(Tinebase_Account_Model_Account $_account, $_application, $_grant)
    {
        $accountId = $_account->accountId;
        
        $groupMemberships   = $_account->getGroupMemberships();

        if(count($groupMemberships) === 0) {
            throw new Exception('account must be in at least one group');
        }
        
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => SQL_TABLE_PREFIX . 'container_acl'), array())
            ->join(
                array('user' => SQL_TABLE_PREFIX . 'container_acl'),
                'owner.container_id = user.container_id', 
                array('account_grants' => 'BIT_OR(user.account_grant)'))
            ->join(SQL_TABLE_PREFIX . 'container', 'user.container_id = ' . SQL_TABLE_PREFIX . 'container.id')
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant = ?', self::GRANT_ADMIN)

            # beware of the extra parenthesis of the next 3 rows
            ->where("(user.account_id = ? AND user.account_type ='account'", $accountId)
            ->orWhere("user.account_id IN (?) AND user.account_type ='group'", $groupMemberships)
            ->orWhere('user.account_type = ?)', 'anyone')
            
            ->where(SQL_TABLE_PREFIX . 'container.application_id = ?', $application->id)
            ->where(SQL_TABLE_PREFIX . 'container.type = ?', self::TYPE_PERSONAL)
            ->group(SQL_TABLE_PREFIX . 'container.id')
            ->having('account_grants & ?', $_grant)
            ->order(SQL_TABLE_PREFIX . 'container.name');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        return $result;
    }
    
    /**
     * delete container if user has the required right
     *
     * @param int|Tinebase_Model_Container $_containerId
     * @return void
     */
    public function deleteContainer($_containerId)
    {
        if (!$this->hasGrant(Zend_Registry::get('currentAccount'), $_containerId, self::GRANT_ADMIN)) {
            throw new Exception('admin permission to container denied');
        }
        
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);

        $where = array(
            $this->containerTable->getAdapter()->quoteInto('id = ?', $containerId)
        );
        $this->containerTable->delete($where);
        
        $where = array(
            $this->containerTable->getAdapter()->quoteInto('container_id = ?', $containerId)
        );
        $this->containerAclTable->delete($where);
    }
    
    /**
     * rename container, if the user has the required right
     *
     * @param int $_containerId
     * @param string $_containerName the new name
     */
    public function renameContainer($_containerId, $_containerName)
    {
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        
        if (!$this->hasGrant($accountId, $_containerId, self::GRANT_ADMIN)) {
            throw new Exception('admin permission to container denied');
        }
        
        $where = array(
            $this->containerTable->getAdapter()->quoteInto('id = ?', (int)$_containerId)
        );
        
        $data = array(
            'name' => $_containerName
        );
        
        $this->containerTable->update($data, $where);
        return $this->getContainerById($_containerId);
    }
    
    /**
     * check if the given user user has a certain grant
     *
     * @param int $_accountId
     * @param int $_containerId
     * @param int $_grant
     * @return boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant) 
    {
        $accountId = Tinebase_Account::convertAccountIdToInt($_accountId);

        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        $grant = (int)$_grant;
        if($grant != $_grant) {
            throw new InvalidArgumentException('$_grant must be integer');
        }
        
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        //$groupMemberships[] = $accountId;
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl', array())
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id', array('id'))

            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'container_acl.account_id = ? AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='account'", $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_id IN (?) AND ' . SQL_TABLE_PREFIX . "container_acl.account_type ='group'", $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'container_acl.account_type = ?)', 'anyone')
            
            ->where(SQL_TABLE_PREFIX . 'container_acl.account_grant = ?', $grant)
            ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId);
                    
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        
        $grants = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        if(empty($grants)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    public function getAllGrants($_containerId) 
    {
        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        
        if(!$this->hasGrant($accountId, $containerId, self::GRANT_ADMIN)) {
            throw new Exception('permission to container denied');
        }
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'container_acl')
            ->join(SQL_TABLE_PREFIX . 'container', SQL_TABLE_PREFIX . 'container_acl.container_id = ' . SQL_TABLE_PREFIX . 'container.id', array('id'))
            ->where(SQL_TABLE_PREFIX . 'container.id = ?', $containerId);
                    
        //error_log("getAllGrants:: " . $select->__toString());

        $stmt = $db->query($select);
        
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $resultArray = array();

        $tineBaseAccounts = Tinebase_Account::getInstance();
        
        foreach($rows as $row) {
        	if (! isset($resultArray[$row['account_id']])) {
	            if($row['account_id'] === NULL) {
	                $displayName = 'Anyone';
	            } else {
	                $account = $tineBaseAccounts->getAccountById($row['account_id']);
	                $displayName = $account->accountDisplayName;
	            }
	                
	            $containerGrant = new Tinebase_Model_Grants( array(
	                'accountId'     => $row['account_id'],
	                'accountName'   => $displayName
	            ), true);
                $resultArray[$row['account_id']] = $containerGrant;
        	}
        	
            switch($row['account_grant']) {
                case self::GRANT_READ:
                    $containerGrant->readGrant = TRUE; 
                    break;
                case self::GRANT_ADD:
                    $containerGrant->addGrant = TRUE; 
                    break;
                case self::GRANT_EDIT:
                    $containerGrant->editGrant = TRUE; 
                    break;
                case self::GRANT_DELETE:
                    $containerGrant->deleteGrant = TRUE; 
                    break;
                case self::GRANT_ADMIN:
                    $containerGrant->adminGrant = TRUE; 
                    break;
            }
        }
        
        return  new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $resultArray, true);;
    }
    
    public function setAllGrants($_containerId, Tinebase_Record_RecordSet $_grants) 
    {
        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        $currentAccountId = Zend_Registry::get('currentAccount')->accountId;
        
        if(!$this->hasGrant($currentAccountId, $containerId, self::GRANT_ADMIN)) {
            throw new Exception('permission to container denied');
        }
        
        $container = $this->getContainerById($containerId);
        if($container->type === Tinebase_Container::TYPE_PERSONAL) {
            // make sure that only the current user has admin rights
            foreach($_grants as $key => $recordGrants) {
                $_grants[$key]->adminGrant = false;
            }
            
            if(isset($_grants[$currentAccountId])) {
                $_grants[$currentAccountId]->readGrant = true;
                $_grants[$currentAccountId]->addGrant = true;
                $_grants[$currentAccountId]->editGrant = true;
                $_grants[$currentAccountId]->deleteGrant = true;
                $_grants[$currentAccountId]->adminGrant = true;
            } else {
                $_grants[$currentAccountId] = new Tinebase_Model_Grants(
                    array(
                        'accountId'     => $currentAccountId,
                        'accountName'   => 'not used',
                        'readGrant'     => true,
                        'addGrant'      => true,
                        'editGrant'     => true,
                        'editGrant'     => true,
                        'adminGrant'    => true
                    ), true);
            }
        }
        
        //error_log(print_r($_grants->toArray(), true));
        
        $where = $this->containerAclTable->getAdapter()->quoteInto('container_id = ?', $containerId);
        $this->containerAclTable->delete($where);
        
        foreach($_grants as $recordGrants) {
            $data = array(
                'container_id'  => $containerId,
                'account_id'    => $recordGrants['accountId'],
            );
            if($recordGrants->readGrant === true) {
                $this->containerAclTable->insert($data + array('account_grant' => Tinebase_Container::GRANT_READ));
            }
            if($recordGrants->addGrant === true) {
                $this->containerAclTable->insert($data + array('account_grant' => Tinebase_Container::GRANT_ADD));
            }
            if($recordGrants->editGrant === true) {
                $this->containerAclTable->insert($data + array('account_grant' => Tinebase_Container::GRANT_EDIT));
            }
            if($recordGrants->deleteGrant === true) {
                $this->containerAclTable->insert($data + array('account_grant' => Tinebase_Container::GRANT_DELETE));
            }
            if($recordGrants->adminGrant === true) {
                $this->containerAclTable->insert($data + array('account_grant' => Tinebase_Container::GRANT_ADMIN));
            }
        }
        
        return true;
    }
    
}