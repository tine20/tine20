<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * this class handles the rights for a given application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * @package     Egwbase
 * @subpackage  Acl
 */
class Egwbase_Acl_Rights
{
    /**
     * the right to be an administrative account for an application
     *
     */
    const ADMIN = 2;
        
    /**
     * the right to run an application
     *
     */
    const RUN = 1;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Acl_Rights
     */
    private static $instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() {}
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {
        try {
            $this->rightsTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_rights'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createApplicationAclTable();
            $this->rightsTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_rights'));

            $accountId = Zend_Registry::get('currentAccount')->accountId;
            
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
     * temporary function to create the egw_application_rights table on demand
     *
     */
    protected function createApplicationAclTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'application_rights');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "application_rights (
                acl_id int(11) NOT NULL auto_increment,
                application_id int(11) NOT NULL,
                application_right int(11) NOT NULL,
                account_id int(11),
                PRIMARY KEY  (`acl_id`),
                UNIQUE KEY `' . SQL_TABLE_PREFIX . 'application_rightsid` (`application_id`, `application_right`, `account_id`),
                KEY `' . SQL_TABLE_PREFIX . 'application_rights_application_right` (`application_right`),
                KEY `' . SQL_TABLE_PREFIX . 'application_rights_account_id` (`account_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
        
    /**
     * returns list of applications the user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set 
     * 
     * @param int $_accountId the numeric account id
     * @return array list of enabled applications for this account
     */
    public function getApplications($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $groupMemberships   = Egwbase_Account::getInstance()->getGroupMemberships($accountId);
        $groupMemberships[] = $accountId;

        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array())
            ->join(SQL_TABLE_PREFIX . 'applications', SQL_TABLE_PREFIX . 'application_rights.application_id = ' . SQL_TABLE_PREFIX . 'applications.app_id')
            ->where(SQL_TABLE_PREFIX . 'application_rights.account_id IN (?) OR ' . SQL_TABLE_PREFIX . 'application_rights.account_id IS NULL', $groupMemberships)
            ->where(SQL_TABLE_PREFIX . 'application_rights.application_right = ?', Egwbase_Acl_Rights::RUN)
            ->group(SQL_TABLE_PREFIX . 'application_rights.application_id');
            
        $stmt = $db->query($select);

        $result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Application');
        
        return $result;
    }

    /**
     * returns a bitmask of rights for given application and accountId
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric account id
     * @return int bitmask of rights
     */
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
        
        $groupMemberships   = Egwbase_Account::getInstance()->getGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array('account_rights' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'application_rights.application_right)'))
            ->where(SQL_TABLE_PREFIX . 'application_rights.account_id IN (?) OR ' . SQL_TABLE_PREFIX . 'application_rights.account_id IS NULL', $groupMemberships)
            ->where(SQL_TABLE_PREFIX . 'application_rights.application_id = ?', $application->app_id)
            ->group(SQL_TABLE_PREFIX . 'application_rights.application_id');
            
        $stmt = $db->query($select);

        $result = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if($result === false) {
            throw new UnderFlowException('no rights found for accountId ' . $accountId);
        }

        return (int)$result['account_rights'];
    }

    /**
     * check if the user has a given right for a given application
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric id of a user account
     * @param int $_right the right to check for
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
        
        $groupMemberships   = Egwbase_Account::getInstance()->getGroupMemberships($accountId);
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
}
