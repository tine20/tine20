<?php
/**
 * the class provides functions to handle ACL
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Acl_Rights
{
    /**
     * the right to run an application
     *
     */
    const RUN = 1;
    
    /**
     * the right to be an administrative account for an application
     *
     */
    const ADMIN = 2;
        
    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Acl_Rights
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     */
    private function __construct() {
        try {
            $this->rightsTable = new Egwbase_Db_Table(array('name' => 'egw_application_rights'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createApplicationAclTable();
            $this->rightsTable = new Egwbase_Db_Table(array('name' => 'egw_application_rights'));

            $accountId = Zend_Registry::get('currentAccount')->account_id;
            
            $application = Egwbase_Application::getInstance()->getApplicationByName('addressbook');
            $data = array(
                'application_right'  => Egwbase_Acl_Rights::ADMIN,
                'account_id'     => $accountId,
                'application_id' => $application->app_id
            );
            $this->rightsTable->insert($data);

            $data = array(
                'application_right'  => Egwbase_Acl_Rights::RUN,
                'account_id'     => NULL,
                'application_id' => $application->app_id
            );
            $this->rightsTable->insert($data);

            $application = Egwbase_Application::getInstance()->getApplicationByName('admin');
            $data = array(
                'application_right'  => Egwbase_Acl_Rights::ADMIN,
                'account_id'     => $accountId,
                'application_id' => $application->app_id
            );
            $this->rightsTable->insert($data);

            $data = array(
                'application_right'  => Egwbase_Acl_Rights::RUN,
                'account_id'     => NULL,
                'application_id' => $application->app_id
            );
            $this->rightsTable->insert($data);
        }
    }
    
    /**
     * temporary function to create the egw_application_rights table on demand
     *
     */
    protected function createApplicationAclTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_application_rights');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE egw_application_rights (
                acl_id int(11) NOT NULL auto_increment,
                application_id int(11) NOT NULL,
                application_right int(11) NOT NULL,
                account_id int(11),
                PRIMARY KEY  (`acl_id`),
                UNIQUE KEY `egw_application_rightsid` (`application_id`, `application_right`, `account_id`),
                KEY `egw_application_rights_application_right` (`application_right`),
                KEY `egw_application_rights_account_id` (`account_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() {}
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Acl_Rights;
        }
        
        return self::$instance;
    }
    
    /**
     * check if the user has a given right for a given application
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric id of a user account
     * @param string $_right the name of the right
     * @return bool
     */
    public function hasRight($_application, $_accountId, $_right) 
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $right = (int)$_right;
        if($right != $_right) {
            throw new InvalidArgumentException('$_right must be integer');
        }
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        if($application->app_enabled == 0) {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        //$accounts = new Egwbase_Account_Sql();
        //$groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
    	

        $where = array(
            $this->rightsTable->getAdapter()->quoteInto('application_id = ?', $application->app_id),
            $this->rightsTable->getAdapter()->quoteInto('application_right = ?', $right),
            // check if the account or the groups of this account has the given right
            $this->rightsTable->getAdapter()->quoteInto('account_id IN (?) OR account_id IS NULL', $groupMemberships)
        );
        
        if(!$row = $this->rightsTable->fetchRow($where)) {
        	return false;
        } else {
        	return true;
        }
    }

    public function getRights($_application, $_accountId) 
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        if($application->app_enabled == 0) {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        //$accounts = new Egwbase_Account_Sql();
        //$groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from('egw_application_rights', array('rights' => 'BIT_OR(egw_application_rights.application_right)'))
            ->where('egw_application_rights.account_id IN (?) OR egw_application_rights.account_id IS NULL', $groupMemberships)
            ->where('egw_application_rights.application_id = ?', $application->app_id)
            ->group('egw_application_rights.application_id');
            
        $stmt = $db->query($select);

        if($stmt->rowCount() == 0) {
            throw new UnderFlowException('no rights found for accountId ' . $accountId);
        }

        $result = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        return $result['rights'];
        
        $where = array(
            $this->rightsTable->getAdapter()->quoteInto('acl_appname = ?', $application->app_name),
            $this->rightsTable->getAdapter()->quoteInto('acl_account IN (?)', $groupMemberships),
            $this->rightsTable->getAdapter()->quoteInto('acl_location IN (?)', $this->supportedRights)
        );
        
        $rowSet = $this->rightsTable->fetchAll($where);
        
        if(empty($rowSet)) {
            throw new UnderFlowException('no rights for given application found');
        }
        
        $returnValue = array();
        
        foreach($rowSet as $row) {
            $returnValue[$row->acl_location] = true;
        }

         return array_keys($returnValue);
        
    }
        
    /**
     * returns list of applications the current user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set 
     * 
     * @param int $_accountId
     * @return array list of enabled applications for this account
     */
    public function getApplications($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        //$accounts = new Egwbase_Account_Sql();
        //$groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;

        //$egwbaseApplication = Egwbase_Application::getInstance();

        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from('egw_application_rights', array())
            ->join('egw_applications', 'egw_application_rights.application_id = egw_applications.app_id')
            ->where('egw_application_rights.account_id IN (?) OR egw_application_rights.account_id IS NULL', $groupMemberships)
            ->where('egw_application_rights.application_right = ?', Egwbase_Acl_Rights::RUN)
            ->group('egw_application_rights.application_id');
            
        $stmt = $db->query($select);

        if($stmt->rowCount() == 0) {
            throw new UnderFlowException('no applications found for accountId ' . $accountId);
        }

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Application');
        
        return $result;
    }
}
