<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
class Tinebase_Application
{
    const ENABLED  = 'enabled';
    
    const DISABLED = 'disabled';
    
    /**
     * the table object for the SQL_TABLE_PREFIX . applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $applicationTable;

    private function __construct() {
        $this->applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'applications'));
    }
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Application
     */
    private static $instance = NULL;
    
    /**
     * Returns instance of Tinebase_Application
     *
     * @return Tinebase_Application
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Application;
        }
        
        return self::$instance;
    }
    
    
    /**
     * returns one application identified by id
     *
     * @param int $_applicationId the id of the application
     * @todo code still needs some testing
     * @throws Exception if $_applicationId is not integer and not greater 0
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationById($_applicationId)
    {
        $applicationId = (int)$_applicationId;
        if($applicationId != $_applicationId) {
            throw new InvalidArgumentException('$_applicationId must be integer');
        }
        
        $row = $this->applicationTable->fetchRow('`id` = ' . $applicationId);
        
        $result = new Tinebase_Model_Application($row->toArray());
        
        return $result;
    }

    /**
     * returns one application identified by application name
     *
     * @param string $$_applicationName the name of the application
     * @todo code still needs some testing
     * @throws InvalidArgumentException, Exception
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationByName($_applicationName)
    {
        if(empty($_applicationName)) {
            throw new InvalidArgumentException('$_applicationName can not be empty');
        }
        
        $where = $this->applicationTable->getAdapter()->quoteInto('`name` = ?', $_applicationName);
        if(!$row = $this->applicationTable->fetchRow($where)) {
            throw new Exception("application $_applicationName not found");
        }
        
        $result = new Tinebase_Model_Application($row->toArray());
        
        return $result;
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return Tinebase_RecordSet_Application
     */
    public function getApplications($_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->applicationTable->getAdapter()->quoteInto('`name` LIKE ?', '%' . $_filter . '%');
        }
        
        $rowSet = $this->applicationTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $rowSet->toArray());

        return $result;
    }    
    
    /**
     * get enabled or disabled applications
     *
     * @param int $_state can be Tinebase_Application::ENABLED or Tinebase_Application::DISABLED
     * @return Tinebase_Record_RecordSet list of applications
     */
    public function getApplicationsByState($_status)
    {
        if($_status !== Tinebase_Application::ENABLED && $_status !== Tinebase_Application::DISABLED) {
            throw new InvalidArgumentException('$_status can be only Tinebase_Application::ENABLED or Tinebase_Application::DISABLED');
        }
        $where[] = $this->applicationTable->getAdapter()->quoteInto('`status` = ?', $_status);
        
        $rowSet = $this->applicationTable->fetchAll($where);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $rowSet->toArray());

        return $result;
    }    
    
    /**
     * return the total number of applications installed
     *
     * @param $_filter
     * 
     * @return int
     */
    public function getTotalApplicationCount($_filter = NULL)
    {
        $where = array();
        if($_filter !== NULL) {
            $where[] = $this->applicationTable->getAdapter()->quoteInto('`name` LIKE ?', '%' . $_filter . '%');
        }
        $count = $this->applicationTable->getTotalCount($where);
        
        return $count;
    }
    
    /**
     * set application state
     *
     * @param array $_applicationIds application ids to set new state for
     * @param string $_state the new state
     */
    public function setApplicationState(array $_applicationIds, $_state)
    {
        if($_state != Tinebase_Application::DISABLED && $_state != Tinebase_Application::ENABLED) {
            throw new OutOfRangeException('$_state can be only Tinebase_Application::DISABLED  or Tinebase_Application::ENABLED');
        }
        
        $where = array(
            $this->applicationTable->getAdapter()->quoteInto('`id` IN (?)', $_applicationIds)
        );
        
        $data = array(
            'status' => $_state
        );
        
        $affectedRows = $this->applicationTable->update($data, $where);
        
        //error_log("AFFECTED:: $affectedRows");
    }
    
    /**
     * add new appliaction 
     *
     * @param Tinebase_Model_Application $_application the new application object
     * @return Tinebase_Model_Application the new application with the applicationId set
     */
    public function addApplication(Tinebase_Model_Application $_application)
    {
        $data = $_application->toArray();
        unset($data['id']);
        unset($data['tables']);
        
        $applicationId = $this->applicationTable->insert($data);
        
        $_application->id = $applicationId;
        
        return $_application;
    }
    
    /**
     * get application account rights
     *
     * @param   int $_applicationId  app id
     * @return  array with account rights for the application
     * 
     * @todo    return recordset with Tinebase_Acl_Right records here?
     * @todo    translate 'Anyone'
     */
    public function getApplicationPermissions($_applicationId)
    {
        $applicationRights = Tinebase_Acl_Rights::getInstance()->getApplicationPermissions($_applicationId);

        $result = array();
        foreach ( $applicationRights as $tineRight ) {

            $rightArray = $tineRight->toArray();
            
            // set display name
            switch ( $tineRight->account_type ) {
                case 'anyone':
                    // @todo translate
                    $displayName = 'Anyone';
                    break;
                case 'group':
                    // get group name
                    $group = Tinebase_Group::getInstance()->getGroupById($tineRight->account_id);
                    $displayName = $group->name;
                    break;
                case 'user':
                    // get account name
                    $account = Tinebase_Account::getInstance()->getAccountById($tineRight->account_id);
                    $displayName = $account->accountDisplayName;
                    break;
                default:
                    throw Exception ('not a valid account type');
            }
            $rightArray['accountDisplayName'] = $displayName;

            // @todo it's a bit dirty to fill up the rightArray with the rights, is there a better solution? 

            // set rights array and remove single right value
            unset($rightArray['right']);
            $rights = explode(',', $tineRight->right);
            $allRights = $this->getAllRights($_applicationId); 
            foreach ( $allRights as $key ) {
                if ( in_array($key, $rights) ) {
                    $rightArray[$key] = TRUE;
                } else {
                    $rightArray[$key] = FALSE;                    
                }
            }
            
            $result[] = $rightArray;
        }

        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' rights record: ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * set application account rights
     *
     * @param   int $_applicationId  app id
     * @param   array $_applicationRights  application account rights
     */
    public function setApplicationPermissions($_applicationId, $_applicationRights)
    {
        $tineAclRights = Tinebase_Acl_Rights::getInstance();
        
        $tineRights = new Tinebase_Record_RecordSet('Tinebase_Acl_Model_Right');
        foreach ( $_applicationRights as $right ) {
            $right['application_id'] = $_applicationId;
            
            $allRights = $this->getAllRights($_applicationId); 
            foreach ( $allRights as $key ) {
                if ( isset($right[$key]) && $right[$key] === TRUE ) {
                    unset ( $right['id'] );
                    $right['right'] = $key;
                    $tineRight = new Tinebase_Acl_Model_Right ( $right );
                    $tineRights->addRecord( $tineRight );
                }
            }
        }
        
        return $tineAclRights->setApplicationPermissions($_applicationId, $tineRights);
    }

    /**
     * get all possible application rights
     *
     * @param   Tinebase_Record_RecordSet $_applicationRights  app rights
     * @return  array   all application rights
     */
    public function getAllRights($_applicationId)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_applicationId);
        
        // call getAllApplicationRights for application (if it has specific rights)
        if ( file_exists ( $application->name."/Acl/Rights.php") ) {
            $allRights = call_user_func(array($application->name . "_Acl_Rights", 'getAllApplicationRights'), $_applicationId);
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' get all rights from ' . $application->name . 
            //    "_Acl_Rights ( " . $application->name."/Acl/Rights.php" ." )");
        } else {
            $allRights = Tinebase_Acl_Rights::getAllApplicationRights($_applicationId);   
        }
        
        return $allRights;
    }
    
}
