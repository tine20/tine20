<?php

/**
 * the class needed to access the acl table
 *
 * @see Egwbase_Acl_Sql_Rights
 */
require_once 'Egwbase/Acl/Sql/Rights.php';

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
     * list of supported rights
     * 
     * this is just a temporary list, until we have moved the rights to a separate table
     *
     * @var array
     */
    protected $supportedRights = array('run', 'admin');
    
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
        $this->rightsTable = new Egwbase_Db_Table(array(
        	'name' => 'egw_acl',
        ));
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
     * @param int $_accountId the numeric id of a user account
     * @param string $_application the name of the application
     * @param string $_right the name of the right
     * @return bool
     */
    public function hasRight($_accountId, $_application, $_right) 
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
    	
        $egwbaseApplication = Egwbase_Application::getInstance();
        $application = $egwbaseApplication->getApplicationByName($_application);

        $where = array(
            $this->rightsTable->getAdapter()->quoteInto('acl_appname = ?', $application->app_name),
            $this->rightsTable->getAdapter()->quoteInto('acl_location = ?', $_right),
            // check if the account or the groups of this account has the given right
            $this->rightsTable->getAdapter()->quoteInto('acl_account IN (?)', $groupMemberships)
        );
        
        if(!$row = $this->rightsTable->fetchRow($where)) {
        	return false;
        } else {
        	return true;
        }
    }

    public function getRights($_accountId, $_application) 
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;
    	
        $egwbaseApplication = Egwbase_Application::getInstance();
        $application = $egwbaseApplication->getApplicationByName($_application);

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
        
        $accounts = new Egwbase_Account_Sql();
        $groupMemberships = $accounts->getAccountGroupMemberships($accountId);
        $groupMemberships[] = $accountId;

        $egwbaseApplication = Egwbase_Application::getInstance();
        
        $where = array(
            $this->rightsTable->getAdapter()->quoteInto('acl_location = ?', 'run'),
            // check if the account or the groups of this account has the given right
            $this->rightsTable->getAdapter()->quoteInto('acl_account IN (?)', $groupMemberships)
        );
        
        $rowSet = $this->rightsTable->fetchAll($where);
        
        if(empty($rowSet)) {
            throw new UnderFlowException('no applications found');
        }
        
        $resultSet = new Egwbase_Record_RecordSet();
        
        foreach($rowSet as $row) {
            try {
                $application = $egwbaseApplication->getApplicationByName($row->acl_appname);
                $resultSet->addRecord($application);
            } catch (Exception $e) {
                // application does not exist anymore, but is still in the acl table
            }
        }
        
        return $resultSet;
    }
}
