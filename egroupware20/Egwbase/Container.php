<?php
/**
 * the class provides functions to handle applications
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Application.php 314 2007-11-20 18:09:35Z lkneschke $
 *
 */

class Egwbase_Container
{
    /**
     * the table object for the egw_container table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $containerTable;

    /**
     * the table object for the egw_container_acl table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $containerAclTable;

    const INTERNAL = 'internal';
    
    const PERSONAL = 'personal';
    
    const SHARED = 'shared';
    
    /**
     * the constructor
     *
     * until we have finnished the table setup infrastructure, we also create the 
     * needed tables in this class on demand
     */
    private function __construct() {
        try {
            $this->containerTable = new Egwbase_Db_Table(array('name' => 'egw_container'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createContainerTable();
            $this->containerTable = new Egwbase_Db_Table(array('name' => 'egw_container'));

            $application = Egwbase_Application::getInstance()->getApplicationByName('addressbook');
            
            $data = array(
                'container_name'    => 'Internal Addressbook',
                'container_type'    => Egwbase_Container::INTERNAL,
                'container_backend' => Addressbook_Backend::SQL,
                'application_id'    => $application->app_id
            );
            $this->containerTable->insert($data);

            $data = array(
                'container_name'    => 'Default Addressbook',
                'container_type'    => Egwbase_Container::PERSONAL,
                'container_backend' => Addressbook_Backend::SQL,
                'application_id'    => $application->app_id
            );
            $this->containerTable->insert($data);
            
            $data = array(
                'container_name'    => 'Shared 1',
                'container_type'    => Egwbase_Container::SHARED,
                'container_backend' => Addressbook_Backend::SQL,
                'application_id'    => $application->app_id
            );
            $this->containerTable->insert($data);
        }
        
        try {
            $this->containerAclTable = new Egwbase_Db_Table(array('name' => 'egw_container_acl'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createContainerAclTable();
            $this->containerAclTable = new Egwbase_Db_Table(array('name' => 'egw_container_acl'));

            $application = Egwbase_Application::getInstance()->getApplicationByName('addressbook');
            $accountId = Zend_Registry::get('currentAccount')->account_id;
            
            $data = array(
                'container_id'   => 1,
                'application_id' => $application->app_id,
                'account_id'     => NULL,
                'account_grant'  => Egwbase_Acl_Grants::READ
            );
            $this->containerAclTable->insert($data);

            $data = array(
                'container_id'   => 2,
                'application_id' => $application->app_id,
                'account_id'     => $accountId,
                'account_grant'  => Egwbase_Acl_Grants::ANY
            );
            $this->containerAclTable->insert($data);
            
            $data = array(
                'container_id'   => 3,
                'application_id' => $application->app_id,
                'account_id'     => $accountId,
                'account_grant'  => Egwbase_Acl_Grants::ANY
            );
            $this->containerAclTable->insert($data);
        }
    }
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Container
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Container
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Container;
        }
        
        return self::$instance;
    }

    /**
     * temporary function to create the egw_container table on demand
     *
     */
    protected function createContainerTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_container');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE egw_container (
            	container_id int(11) NOT NULL auto_increment, 
            	container_name varchar(256), 
            	container_type enum('personal', 'shared', 'internal') NOT NULL,
            	container_backend varchar(64) NOT NULL,
            	application_id int(11) NOT NULL,
            	PRIMARY KEY  (`container_id`),
            	UNIQUE KEY `egw_container_container_id` (`container_id`, `application_id`),
            	KEY `egw_container_container_type` (`container_type`),
            	KEY `egw_container_container_application_id` (`application_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }

    /**
     * temporary function to create the egw_container_acl table on demand
     *
     */
    protected function createContainerAclTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_container_acl');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE egw_container_acl (
                acl_id int(11) NOT NULL auto_increment,
            	container_id int(11) NOT NULL, 
            	/* application_id int(11) NOT NULL, */
            	account_id int(11),
            	/* account_type int(11) NOT NULL, */
            	account_grant int(11) NOT NULL,
            	PRIMARY KEY (`acl_id`),
            	UNIQUE KEY `egw_container_acl_primary` (`container_id`, `account_id`),
            	/*KEY `egw_container_acl_application_id` (`application_id`), */
            	KEY `egw_container_acl_account_id` (`account_id`)
            	) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    public function addContainer($_application, $_name, $_type, $_backend)
    {
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        
        $data = array(
            'container_name'    => $_name,
            'container_type'    => $_type,
            'container_backend' => $_name,
            'application_id'    => $application->app_id
        );
        $this->containerTable->insert($data);
        
        $containerId = $this->containerTable->lastInsertId();
        
        return $containerId;
    }
    
    public function addACL($_application, $_name, $_type, $_backend)
    {
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        
        $data = array(
            'container_name'    => $_name,
            'container_type'    => $_type,
            'container_backend' => $_name,
            'application_id'    => $application->app_id
        );
        $this->containerTable->insert($data);
        
        $containerId = $this->containerTable->lastInsertId();
        
        return $containerId;
    }
    
    public function getInternalContainer($_application)
    {
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from('egw_container_acl', array())
            ->join('egw_container', 'egw_container_acl.container_id = egw_container.container_id')
            ->where('egw_container_acl.account_id IN (?) OR egw_container_acl.account_id IS NULL', $accountId)
            ->where('egw_container_acl.account_grant & ?', Egwbase_Acl_Grants::READ)
            ->where('egw_container.container_type = ?', Egwbase_Container::INTERNAL)
            ->where('egw_container.application_id = ?', $application->app_id)
            ->order('egw_container.container_name')
            ->group('egw_container.container_id');
            
        //error_log("getInternalContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        
        if($stmt->rowCount() == 0) {
            throw new Exception('internal container not found or not accessible');
        }

        $result = new Egwbase_Record_Container($stmt->fetch(Zend_Db::FETCH_ASSOC), true);
        
        return $result;
        
    }
    
    /**
     * return all container, which are visible for the user(aka the user has read rights for)
     *
     * used to read all contacts available
     * 
     * @param int $_applicationId the application id
     * @param int $_accountId the account id
     * @param array $_containerType array containing int of containertypes
     * @return Egwbase_Record_RecordSet
     */
    public function getContainerIdsByACL($_application, $_right)
    {
        $right = (int)$_right;
        if($right != $_right) {
            throw new InvalidArgumentException('$_right must be integer');
        }
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
               
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from('egw_container')
            ->join('egw_container_acl','egw_container.container_id = egw_container_acl.container_id', array())
            ->where('egw_container.application_id = ?', $application->app_id)
            ->where('egw_container_acl.account_id IN (?) OR egw_container_acl.account_id IS NULL', $accountId)
            ->where('egw_container_acl.account_grant & ?', $right)
            ->order('egw_container.container_name')
            ->group('egw_container.container_id');

        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }

    public function getPersonalContainer($_application, $_owner)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        
        $accountId = Zend_Registry::get('currentAccount')->account_id;
                
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => 'egw_container_acl'), array())
            ->join(array('user' => 'egw_container_acl'),'owner.container_id = user.container_id', array())
            ->join('egw_container', 'owner.container_id = egw_container.container_id')
            ->where('owner.account_id = ?', $_owner)
            ->where('owner.account_grant & ?', Egwbase_Acl_Grants::ADMIN)
            ->where('user.account_id IN (?)', $accountId)
            ->where('user.account_grant & ?', Egwbase_Acl_Grants::READ)
            ->where('egw_container.application_id = ?', $application->app_id)
            ->where('egw_container.container_type = ?', Egwbase_Container::PERSONAL)
            ->order('egw_container.container_name')
            ->group('egw_container.container_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }
    
    public function getSharedContainer($_application)
    {
        $accountId = Zend_Registry::get('currentAccount')->account_id;
                
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from('egw_container_acl', array())
            ->join('egw_container', 'egw_container_acl.container_id = egw_container.container_id')
            ->where('egw_container_acl.account_id IN (?)', $accountId)
            ->where('egw_container_acl.account_grant & ?', Egwbase_Acl_Grants::READ)
            ->where('egw_container.application_id = ?', $application->app_id)
            ->where('egw_container.container_type = ?', Egwbase_Container::SHARED)
            ->order('egw_container.container_name')
            ->group('egw_container.container_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }
    
    public function getOtherUsers($_application)
    {
        $accountId = Zend_Registry::get('currentAccount')->account_id;
                
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => 'egw_container_acl'), array('account_id'))
            ->join(array('user' => 'egw_container_acl'),'owner.container_id = user.container_id', array())
            ->join('egw_container', 'user.container_id = egw_container.container_id', array())
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant & ?', Egwbase_Acl_Grants::ADMIN)
            ->where('user.account_id IN (?)', $accountId)
            ->where('user.account_grant & ?', Egwbase_Acl_Grants::READ)
            ->where('egw_container.application_id = ?', $application->app_id)
            ->where('egw_container.container_type = ?', Egwbase_Container::PERSONAL)
            ->order('egw_container.container_name')
            ->group('owner.account_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $result;
    }
    
    public function getOtherUsersContainer($_application)
    {
        $accountId = Zend_Registry::get('currentAccount')->account_id;
                
        $db = Zend_Registry::get('dbAdapter');
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);

        $select = $db->select()
            ->from(array('owner' => 'egw_container_acl'))
            ->join(array('user' => 'egw_container_acl'),'owner.container_id = user.container_id', array())
            ->join('egw_container', 'user.container_id = egw_container.container_id')
            ->where('owner.account_id != ?', $accountId)
            ->where('owner.account_grant & ?', Egwbase_Acl_Grants::ADMIN)
            ->where('user.account_id IN (?) or user.account_id IS NULL', $accountId)
            ->where('user.account_grant & ?', Egwbase_Acl_Grants::READ)
            ->where('egw_container.application_id = ?', $application->app_id)
            ->where('egw_container.container_type = ?', Egwbase_Container::PERSONAL)
            ->order('egw_container.container_name')
            ->group('egw_container.container_id');
            
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }
    
    public function deleteContainer()
    {
        
    }
    
    public function setContainer()
    {
        
    }
    
    public function hasAccess($_application, $_containerId, $_right) 
    {
        $containerId = (int)$_containerId;
        if($containerId != $_containerId) {
            throw new InvalidArgumentException('$_containerId must be integer');
        }
        
        $right = (int)$_right;
        if($right != $_right) {
            throw new InvalidArgumentException('$_right must be integer');
        }
        
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from('egw_container_acl', array('container_id'))
            ->join('egw_container', 'egw_container_acl.container_id = egw_container.container_id', array())
            ->where('egw_container_acl.account_id IN (?) OR egw_container_acl.account_id IS NULL', $accountId)
            ->where('egw_container_acl.account_grant & ?', $right)
            ->where('egw_container.container_id = ?', $containerId);
                    
        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);
        
        if($stmt->rowCount() == 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}