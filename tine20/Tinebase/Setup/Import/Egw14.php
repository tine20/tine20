<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to import tinebase related data from egw14
 * 
 * @todo
 *  - import for global cats -> tags
 *  
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_Import_Egw14 extends Tinebase_Setup_Import_Egw14_Abstract
{
    /**
     * constructs a user and group import from egw14
     * 
     * @param Zend_Db_Adapter_Abstract  $_egwDb
     * @param Zend_Config               $_config
     * @param Zend_Log                  $_log
     */
    public function __construct($_config, $_log)
    {
        parent::__construct($_config, $_log);
    }
    
    /**
     * all imports 
     *
     */
    public function import()
    {
        //@TODO move to admin module
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' starting egw import for Admin');
        
        if ($this->_config->admin->import_groups) {
            $this->importGroups();
        }
        if ($this->_config->admin->import_users) {
            $this->importAccounts();
        }
        if ($this->_config->admin->import_groupmembers) {
            $this->importGroupMembers();
        }
        
        // walk apps
        $apps = Setup_Controller::getInstance()->searchApplications();
        foreach($apps['results'] as $app) {
            if (   $app['install_status'] == 'uptodate'
                && $app['name'] != 'Admin'
                && $this->_config->{strtolower($app['name'])}
                && $this->_config->{strtolower($app['name'])}->enabled) {
                
                $config = $this->_config->{strtolower($app['name'])}->toArray();
                
                $class_name = $app['name'] . '_Setup_Import_Egw14';
                if (! class_exists($class_name)) {
                    $this->_log->ERR(__METHOD__ . '::' . __LINE__ . " no import for {$app['name']} available");
                    continue;
                }
                
                try {
                    $importer = new $class_name($this->_config->{strtolower($app['name'])}, $this->_log);
                    $importer->import();
                } catch (Exception $e) {
                    $this->_log->ERR(__METHOD__ . '::' . __LINE__ . " import for {$app['name']} failed ". $e->getMessage());
                }
            }
        }
    }
    
    /**
     * import the accounts from eGroupWare 1.4
     *
     * @todo add primary group or use Admin_Controller_User::getInstance()->create
     * @todo import user password
     */
    protected function importAccounts()
    {
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' start importing egw users');
        
        $select = $this->_egwDb->select()
            ->from(array('accounts' => 'egw_accounts'))
            ->joinLeft(
                /* table  */ array('contacts' => 'egw_addressbook'), 
                /* on     */ $this->_egwDb->quoteIdentifier('accounts.account_id') . ' = ' . $this->_egwDb->quoteIdentifier('contacts.account_id')
            )
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('accounts.account_type') . ' = ?', 'u'));
        
        $accounts = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_OBJ);
        
        foreach($accounts as $account) {
            $user = new Tinebase_Model_FullUser(array(
                'accountId'                 => $account->account_id,
                'accountLoginName'          => $account->account_lid,
                'accountLastLogin'          => $account->account_lastlogin > 0 ? new Tinebase_DateTime($account->account_lastlogin) : NULL,
                'accountLastLoginfrom'      => $account->account_lastloginfrom,
                'accountLastPasswordChange' => $account->account_lastpwd_change > 0 ? new Tinebase_DateTime($account->account_lastpwd_change) : NULL,
                'accountStatus'             => $account->account_status == 'A' ? 'enabled' : 'disabled',
                'accountExpires'            => $account->account_expires > 0 ? new Tinebase_DateTime($account->account_expires) : NULL,
                'accountPrimaryGroup'       => abs($account->account_primary_group),
                'accountLastName'           => $account->n_family ? $account->n_family : 'Lastname',
                'accountFirstName'          => $account->n_given ? $account->n_given : 'Firstname',
                'accountEmailAddress'       => isset($account->email) ? $account->email : $account->contact_email
            ));
            
            $this->_log->DEBUG(__METHOD__ . '::' . __LINE__ .' user: ' . print_r($user->toArray(), true));
            
            // save user
            $user->sanitizeAccountPrimaryGroup();
            
            // update or create user
            try {
                Tinebase_User::getInstance()->getUserByProperty('accountId', $user->accountId);
                $user = Tinebase_User::getInstance()->updateUser($user);
            } catch (Tinebase_Exception_NotFound $ten) {
                $user = Tinebase_User::getInstance()->addUser($user);
            }
        
            // (re)set password
            Tinebase_User::getInstance()->setPassword($user, $account->account_pwd, FALSE);
            
            // plase user in his groups
            Tinebase_Group::getInstance()->addGroupMember($user->accountPrimaryGroup, $user);
        }
        
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' imported ' . count($accounts) . ' users from egw');
    }

    /**
     * import the groups from eGroupWare 1.4
     *
     */
    protected function importGroups()
    {
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' start importing egw groups');
        
        $select = $this->_egwDb->select()
            ->from(array('accounts' => 'egw_accounts'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('accounts.account_type') . ' = ?', 'g'));
        
        $groups = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_OBJ);
        
        foreach($groups as $group) {
            $groupObject = new Tinebase_Model_Group(array(
                'id'            => abs($group->account_id),
                'name'          => $group->account_lid,
                'description'   => 'imported by Tine 2.0 group importer'
            ));
            
            $this->_log->DEBUG(__METHOD__ . '::' . __LINE__ .' add group: ' . print_r($groupObject->toArray(), TRUE));
            try {
                Tinebase_Group::getInstance()->addGroup($groupObject);
            } catch (Exception $e) {
                $this->_log->WARN(__METHOD__ . '::' . __LINE__ .' Could not add group: ' . $groupObject->name . ' Error message: ' . $e->getMessage());
            }
        }
        
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' imported ' . count($groups) . ' groups from egw');
    }

    /**
     * import the group members from eGroupWare 1.4
     *
     */
    protected function importGroupMembers()
    {
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' start importing egw group members');
        
        $select = $this->_egwDb->select()
            ->from(array('acl' => 'egw_acl'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('acl.acl_appname') . ' = ?', 'phpgw_group'));
        
        $groupMembers = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_OBJ);
        
        foreach($groupMembers as $member) {
            $groupId = abs($member->acl_location);
            
            Tinebase_Group::getInstance()->addGroupMember($groupId, $member->acl_account);
        }
        
        $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . ' imported all group memberships from egw');
    }
}
