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

class Egwbase_Acl
{
    /**
     * the numeric id of the user the read the acl from the sql table
     *
     * @var int
     */
    protected $accountId;
    
    private static $instance = NULL;
    
    /**
     * constant for no rights
     *
     */
    const NONE = 0;
    /**
     * constant for read right
     *
     */
    const READ = 1;
    /**
     * constant for add right
     *
     */
    const ADD = 2;
    /**
     * constant for edit right
     *
     */
    const EDIT = 4;
    /**
     * constant for delete right
     *
     */
    const DELETE = 8;
    /**
     * constant for personal right
     *
     */
    const PERSONAL = 16;
    /**
     * constant for all rights
     *
     */
    const ANY = 31;
    
    const ANY_GRANTS = 0;
    
    const GROUP_GRANTS = 1;
    
    const ACCOUNT_GRANTS = 2;

    private function __construct() {}
    private function __clone() {}
    
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Acl;
        }
        
        return self::$instance;
    }
    
    /**
     * get the grants for the user identified by $accountId for a specific application
     *
     * @param int $accountId the accountid of the user
     * @param string $appName the name of the application to return the rights for
     * @param bool $enumerateGroupAcls if TRUE the acl for the groupmembers gets returned too
     * @return array the grants
     */
    public function getApplicationGrants($accountId, $appName, $enumerateGroupAcls = TRUE)
    {
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        
        $aclTable = new Egwbase_Acl_Sql();
        $where = array(
            $aclTable->getAdapter()->quoteInto('acl_appname = ?', $appName),
            // who gave rights to me and my groups
            $aclTable->getAdapter()->quoteInto('acl_location IN (?)', $groupMemberships)
        );
        $rowSet = $aclTable->fetchAll($where);

        $grants = array();
        
        foreach($rowSet as $row) {
            $grantedBy        = $row->acl_account;
            $grantedRights    = $row->acl_rights;
            
            // initialize grants to Egwbase_Acl::NONE
            if(!isset($grants[$grantedBy])) {
                $grants[$grantedBy] = Egwbase_Acl::NONE;
            }
            $grants[$grantedBy] |= $grantedRights;

            // if it is a group(negative id) fetch the group members acl too
            if ($grantedBy < 0 && $enumerateGroupAcls === TRUE) {
                $groupMembers = $accounts->getGroupMembers($grantedBy);
                
                foreach($groupMembers as $grantedBy) {
                    // Don't allow to override private with group ACL's!
                    $grantedRights    &= ~Egwbase_Acl::PERSONAL;
                    
                    if(!isset($grants[$grantedBy])) {
                        $grants[$grantedBy] = Egwbase_Acl::NONE;
                    }
                    
                    $grants[$grantedBy] |= $grantedRights;
                }
            }
        }
        
        // the user has always access to his own data
        $grants[$accountId] = Egwbase_Acl::ANY;
            
        return $grants;
    }
    
    /**
     * get the grants for the currently set accountId for a spefic application
     *
     * @param int $accountId the accountid of the user
     * @param string $appName the name of the application to return the rights for
     * @param int $requiredRight which rights needs to be set, to get the group returned
     * @param int $grantType which type of grants to return (Egwbase_Acl::ANY_GRANTS, Egwbase_Acl::GROUP_GRANTS, Egwbase_Acl::ACCOUNT_GRANTS)
     * @return array the grants
     */
    public function getGrants($accountId, $appName, $requiredRight, $grantType = Egwbase_Acl::ANY_GRANTS)
    {
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
        
        $aclTable = new Egwbase_Acl_Sql();
        $where = array(
            $aclTable->getAdapter()->quoteInto('acl_appname = ?', $appName),
            // who gave rights to $accountId's groups and $accountId itself
            $aclTable->getAdapter()->quoteInto('acl_location IN (?)', $groupMemberships)
        );

        if($grantType === Egwbase_Acl::GROUP_GRANTS) {
            // return groups only (negative id)
            $where[] = 'acl_account < 0';
        } elseif ($grantType === Egwbase_Acl::ACCOUNT_GRANTS) {
            // return accounts only (positive id)
            $where[] = 'acl_account > 0';
        }
        
        $rowSet = $aclTable->fetchAll($where);

        $grants = array();
        
        foreach($rowSet as $row) {
            $grantedBy        = $row->acl_account;
            $grantedRights    = $row->acl_rights;
            
            if (!!($grantedRights & $requiredRight)) {
                // initialize grants to Egwbase_Acl::NONE
                if(!isset($grants[$grantedBy])) {
                    $grants[$grantedBy] = Egwbase_Acl::NONE;
                }
                $grants[$grantedBy] |= $grantedRights;
            }

        }
        
        // the user has always access to his own data
        $grants[$accountId] = Egwbase_Acl::ANY;
            
        return $grants;
    }
    
    public function checkPermissions($_grantedTo, $_appName, $_grantedBy, $_requiredRight)
    {
        $accounts = new Egwbase_Account_Sql();
        $grantedTo = $accounts->getAccountGroupMemberships($_grantedTo);
        $grantedTo[] = $accountId;
        
        $aclTable = new Egwbase_Acl_Sql();
        $where = array(
            $aclTable->getAdapter()->quoteInto('acl_appname = ?', $_appName),
            // me and my groups
            $aclTable->getAdapter()->quoteInto('acl_location IN (?)', $grantedTo),
            $aclTable->getAdapter()->quoteInto('acl_account = ?', $_grantedBy),
        );
        
        $rowSet = $aclTable->fetchAll($where);
        
        foreach($rowSet as $row) {
            $grantedRights    = $row->acl_rights;
            
            if(!!($grantedRights & $row->acl_rights)) {
                return true;
            }
        }
        
        return false;
        
    }
}
