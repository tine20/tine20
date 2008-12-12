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
    protected $_applicationTable;

    /**
     * the db adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = '';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'applications'));
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

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
     * @param Tinebase_Model_Application|string $_applicationId the id of the application
     * @todo code still needs some testing
     * @throws Tinebase_Exception_InvalidArgument if $_applicationId is not integer and not greater 0
     * @return Tinebase_Model_Application the information about the application
     */
    public function getApplicationById($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?' , $applicationId);
        $row = $this->_applicationTable->fetchRow($where);
        
        $result = new Tinebase_Model_Application($row->toArray());
        
        return $result;
    }

    /**
     * returns one application identified by application name
     *
     * @param string $$_applicationName the name of the application
     * @return Tinebase_Model_Application the information about the application
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo code still needs some testing
     * @todo add caching
     */
    public function getApplicationByName($_applicationName)
    {
        if(empty($_applicationName)) {
            throw new Tinebase_Exception_InvalidArgument('$_applicationName can not be empty.');
        }
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_applicationName);
        if(!$row = $this->_applicationTable->fetchRow($where)) {
            throw new Tinebase_Exception_NotFound("Application $_applicationName not found.");
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
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        
        $rowSet = $this->_applicationTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

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
            throw new Tinebase_Exception_InvalidArgument('$_status can be only Tinebase_Application::ENABLED or Tinebase_Application::DISABLED');
        }
        $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', $_status);
        
        $rowSet = $this->_applicationTable->fetchAll($where);

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
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' LIKE ?', '%' . $_filter . '%');
        }
        $count = $this->_applicationTable->getTotalCount($where);
        
        return $count;
    }
    
    /**
     * set application state
     *
     * @param   array $_applicationIds application ids to set new state for
     * @param   string $_state the new state
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setApplicationState(array $_applicationIds, $_state)
    {
        if($_state != Tinebase_Application::DISABLED && $_state != Tinebase_Application::ENABLED) {
            throw new Tinebase_Exception_InvalidArgument('$_state can be only Tinebase_Application::DISABLED  or Tinebase_Application::ENABLED');
        }
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $_applicationIds)
        );
        
        $data = array(
            'status' => $_state
        );
        
        $affectedRows = $this->_applicationTable->update($data, $where);
        
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
        if (empty($_application->id)) {
            $newId = $_application->generateUID();
            $_application->setId($newId);
        }
        
        $data = $_application->toArray();
        unset($data['tables']);
        
        $this->_applicationTable->insert($data);

        $result = $this->getApplicationById($_application->id);
        
        return $result;
    }
    
    /**
     * get application account rights
     *
     * @param   int $_applicationId  app id
     * @return  array with account rights for the application
     * @deprecated no longer needed because of the new role management 
     */
    public function getApplicationPermissions($_applicationId)
    {
        /*
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
                    $account = Tinebase_User::getInstance()->getUserById($tineRight->account_id);
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
        
        return $result;
        */
    }
    
    /**
     * set application account rights
     *
     * @param   int $_applicationId  app id
     * @param   array $_applicationRights  application account rights
     * @return  int number of rights set
     * @deprecated no longer needed because of the new role management
     */
    public function setApplicationPermissions($_applicationId, $_applicationRights)
    {
        /*
        $tineAclRights = Tinebase_Acl_Rights::getInstance();
        
        $tineRights = new Tinebase_Record_RecordSet('Tinebase_Model_RoleRight');
        foreach ( $_applicationRights as $right ) {
            $right['application_id'] = $_applicationId;
            
            $allRights = $this->getAllRights($_applicationId);
            
            foreach ( $allRights as $key ) {
                if ( isset($right[$key]) && $right[$key] === TRUE ) {
                    unset ( $right['id'] );
                    $right['right'] = $key;
                    $tineRight = new Tinebase_Model_RoleRight ( $right );
                    $tineRights->addRecord( $tineRight );
                }
            }
        }
        
        return $tineAclRights->setApplicationPermissions($_applicationId, $tineRights);
        */
    }

    /**
     * get all possible application rights
     *
     * @param   int application id
     * @return  array   all application rights
     */
    public function getAllRights($_applicationId)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_applicationId);
        
        // call getAllApplicationRights for application (if it has specific rights)
        $appAclClassName = $application->name . '_Acl_Rights';
        if ( @class_exists($appAclClassName) ) {
            $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
            $allRights = $appAclObj->getAllApplicationRights();
        } else {
            $allRights = Tinebase_Acl_Rights::getInstance()->getAllApplicationRights($application->name);   
        }
        
        return $allRights;
    }

    /**
     * get right description
     *
     * @param   int     application id
     * @param   string  right
     * @return  array   right description
     */
    public function getRightDescription($_applicationId, $_right)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_applicationId);
        
        // call getAllApplicationRights for application (if it has specific rights)
        $appAclClassName = $application->name . '_Acl_Rights';
        if ( @class_exists($appAclClassName) ) {
            $appAclObj = call_user_func(array($appAclClassName, 'getInstance'));
            $description = $appAclObj->getRightDescription($_right);
        } else {
            $description = Tinebase_Acl_Rights::getInstance()->getRightDescription($_right);   
        }
        
        return $description;
    }
    
    /**
     * get tables of application
     *
     * @param Tinebase_Model_Application $_applicationId
     * @return array
     */
    public function getApplicationTables($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'application_tables', array('name'))
            ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $applicationId);
            
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        if($rows === NULL) {
            return array();
        }
        
        $tables = array();
        foreach($rows as $row) {
            $tables[] = $row['name'];
        }
        return $tables;
    }
    
    /**
     * remove table from application_tables table
     *
     * @param Tinebase_Model_Application|string $_applicationId the applicationId
     * @param string $_tableName the table name
     */
    public function removeApplicationTable($_applicationId, $_tableName)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('application_id') . '= ?', $applicationId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . '= ?', $_tableName)
        );
        
        $this->_db->delete(SQL_TABLE_PREFIX . 'application_tables', $where);
    }
    
    /**
     * remove application from applications table
     *
     * @param Tinebase_Model_Application|string $_applicationId the applicationId
     */
    public function deleteApplication($_applicationId)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
                
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . '= ?', $applicationId)
        );
        
        $this->_db->delete(SQL_TABLE_PREFIX . 'applications', $where);        
    }
    
    /**
     * add table to tine registry
     *
     * @param Tinebase_Model_Application
     * @param string name of table
     * @param int version of table
     * @return int
     */
    public function addApplicationTable($_applicationId, $_name, $_version)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $applicationData = array(
            'application_id'    => $applicationId,
            'name'              => $_name,
            'version'           => $_version
        );
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'application_tables', $applicationData);
    }        
}
