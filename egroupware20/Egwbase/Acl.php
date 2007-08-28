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
    const FULL = 31;
    
    /**
     * the construtor
     *
     * @param int $accountId the accountId to read the acl from
     */
    public function __construct($accountId = NULL)
    {
        if($accountId === NULL) {
            $currentAccount = Zend_Registry::get('currentAccount');
            
            $this->accountId = $currentAccount->account_id;
        } else {
            $this->accountId = $accountId;
        }
    }
    
    /**
     * get the grants for the currently set accountId for a spefic application
     *
     * @param string $appName the name of the application to return the rights for
     * @param bool $enumerateGroupAcls if TRUE the acl for the groupmembers gets returned too
     * @return array the grants
     */
    public function getGrants($appName, $enumerateGroupAcls = TRUE)
    {
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($this->accountId);
        $groupMemberships[] = $this->accountId;
        
        $aclTable = new Egwbase_Acl_Sql();
        $where = array(
            $aclTable->getAdapter()->quoteInto('acl_appname = ?', $appName),
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

            // if it is a group(negative Id) fetch the group members acl too
            if ($grantedBy < 0 && $enumerateGroupAcls === TRUE) {
                $groupMembers = $accounts->getGroupMembers($grantedBy);
                
                foreach($groupMembers as $accountId) {
                    $grantedBy        = $accountId;
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
        $grants[$this->accountId] = Egwbase_Acl::FULL;
            
        return $grants;
    }
    
    /**
     * set the accountId
     *
     * @param int $accountId the accountId
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
    }
}
