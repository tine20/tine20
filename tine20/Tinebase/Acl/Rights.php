<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * this class handles the rights for a given application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Acl_Rights
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
     * the Zend_Dd_Table object
     *
     * @var Tinebase_Db_Table
     */
    protected $rightsTable;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Acl_Rights
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
        $this->rightsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_rights'));
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Acl_Rights;
        }
        
        return self::$instance;
    }
        
    /**
     * returns list of applications the user is able to use
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set and the application must be enabled
     * 
     * @param int $_accountId the numeric account id
     * @return array list of enabled applications for this account
     */
    public function getApplications($_accountId)
    {
        $accountId = Tinebase_Account::convertAccountIdToInt($_accountId);
        
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);

        $db = Zend_Registry::get('dbAdapter');

        //@todo what should happen if user is in no groups? getGroupMemberships() doesn't fetch the primary group at the moment ...
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array())
            ->join(SQL_TABLE_PREFIX . 'applications', SQL_TABLE_PREFIX . 'application_rights.application_id = ' . SQL_TABLE_PREFIX . 'applications.id')
            
            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'application_rights.group_id IN (?)', $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_id = ?', $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_id IS NULL AND ' . SQL_TABLE_PREFIX . 'application_rights.group_id IS NULL)')
            
            ->where(SQL_TABLE_PREFIX . 'application_rights.right = ?', Tinebase_Acl_Rights::RUN)
            ->where(SQL_TABLE_PREFIX . 'applications.status = ?', Tinebase_Application::ENABLED)

            ->group(SQL_TABLE_PREFIX . 'application_rights.application_id');
            
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $db->query($select);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
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
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        if($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        $groupMemberships   = Tinebase_Account::getInstance()->getGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array('account_rights' => 'BIT_OR(' . SQL_TABLE_PREFIX . 'application_rights.right)'))
            ->where(SQL_TABLE_PREFIX . 'application_rights.account_id IN (?) OR ' . SQL_TABLE_PREFIX . 'application_rights.account_id IS NULL', $groupMemberships)
            ->where(SQL_TABLE_PREFIX . 'application_rights.application_id = ?', $application->id)
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
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        if($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        $groupMemberships   = Tinebase_Account::getInstance()->getGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        

        $where = array(
            $this->rightsTable->getAdapter()->quoteInto('application_id = ?', $application->id),
            $this->rightsTable->getAdapter()->quoteInto('right = ?', $right),
            // check if the account or the groups of this account has the given right
            $this->rightsTable->getAdapter()->quoteInto('account_id IN (?) OR account_id IS NULL', $groupMemberships)
        );
        
        if(!$row = $this->rightsTable->fetchRow($where)) {
            return false;
        } else {
            return true;
        }
    }

    public function addRight(Tinebase_Acl_Model_Right $_right) 
    {
        if(!$_right->isValid()) {
            throw new Exception('invalid Tinebase_Acl_Model_Right object passed');
        }
        
        $data['right'] = $_right->right;
        $data['application_id'] = Tinebase_Application::convertApplicationIdToInt($_right->application_id);
        switch($_right->account_type) {
            case 'group':
                $data['group_id'] = Tinebase_Group::convertGroupIdToInt($_right->account_id);
                break;
                
            default:
                throw new Exception('invalid account_type passed');
                break;
        }
        
        $this->rightsTable->insert($data);
    }
}
