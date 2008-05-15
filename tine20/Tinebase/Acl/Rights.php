<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        remove deprecated code?
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
    const ADMIN = 'admin';
        
    /**
     * the right to run an application
     *
     */
    const RUN = 'run';
    
    /**
     * the right to manage shared tags
     */
    const MANAGE_SHARED_TAGS = 'manage_shared_tags';
    
    /**
     * the Zend_Dd_Table object
     *
     * @var Tinebase_Db_Table
     */
    protected $_rightsTable;

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {        
    }
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {
        //$this->_rightsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_rights'));
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Acl_Rights;
        }
        
        return self::$_instance;
    }
        
    /**
     * returns list of applications the user is able to use, calls Tinebase_Acl_Roles::getApplications()
     *
     * this function takes group memberships into account. Applications the accounts is able to use
     * must have the 'run' right set and the application must be enabled
     * 
     * @param int $_accountId the numeric account id
     * @return array list of enabled applications for this account
     */
    public function getApplications($_accountId)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);

        /*
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array())
            ->join(SQL_TABLE_PREFIX . 'applications', 
                $db->quoteIdentifier(SQL_TABLE_PREFIX . 'application_rights.application_id') . ' = ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'applications.id'))            
            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'application_rights.account_type = \'group\' and ' . 
                SQL_TABLE_PREFIX . 'application_rights.account_id IN (?)', $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_id = ?', $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_type = \'anyone\' )')
            ->where(SQL_TABLE_PREFIX . 'application_rights.right = ?', Tinebase_Acl_Rights::RUN)
            ->where(SQL_TABLE_PREFIX . 'applications.status = ?', Tinebase_Application::ENABLED)
            ->group(SQL_TABLE_PREFIX . 'application_rights.application_id');
            
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $db->query($select);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Application', $stmt->fetchAll(Zend_Db::FETCH_ASSOC));
        
        $applicationsFromRoles = Tinebase_Acl_Roles::getInstance()->getApplications($accountId);
        
        // add applications from roles
        foreach ( $applicationsFromRoles as $application ) {            
            if ( !in_array($application->toArray(), $result->toArray()) ) {
                $result->addRecord($application);
            }
        }
        */
        
        $result = Tinebase_Acl_Roles::getInstance()->getApplications($accountId);
        
        return $result;
    }

    /**
     * returns rights for given application and accountId, calls Tinebase_Acl_Roles::getApplicationRights()
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric account id
     * @return array list of rights
     */
    public function getRights($_application, $_accountId) 
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        if ($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }

        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        
        /*
        $groupMemberships   = Tinebase_Group::getInstance()->getGroupMemberships($accountId);                        

        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array('account_rights' => 'GROUP_CONCAT(' . SQL_TABLE_PREFIX . 'application_rights.right)'))
            
            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'application_rights.account_type = \'group\' and ' . SQL_TABLE_PREFIX . 'application_rights.account_id IN (?)', $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_id = ?', $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_type = \'anyone\' )')

            ->where(SQL_TABLE_PREFIX . 'application_rights.application_id = ?', $application->id)
            ->group(SQL_TABLE_PREFIX . 'application_rights.application_id');
            
        $stmt = $db->query($select);

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if ($row === false) {
            $rightsFromApplicationRights = array();
        } else {
            $rightsFromApplicationRights = explode(',', $row['account_rights']);            
        }
        
        $rightsFromRoles = Tinebase_Acl_Roles::getInstance()->getApplicationRights($application->getId(), $accountId);
        $rights = array_merge ($rightsFromApplicationRights, $rightsFromRoles);
        
        // remove duplicates
        $result = array();
        foreach ( $rights as $right ) {
            if ( !in_array($right, $result) ) {
                $result[] = $right;
            }
        }
        */
        
        $result = Tinebase_Acl_Roles::getInstance()->getApplicationRights($application->getId(), $accountId);
        
        return $result;
    }

    /**
     * check if the user has a given right for a given application, calls Tinebase_Acl_Roles::hasRight()
     *
     * @param string $_application the name of the application
     * @param int $_accountId the numeric id of a user account
     * @param int $_right the right to check for
     * @return bool
     */
    public function hasRight($_application, $_accountId, $_right) 
    {
        $result = false;
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        if ($application->status != 'enabled') {
            throw new Exception('user has no rights. the application is disabled.');
        }
        
        /*
        // check application rights
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);        
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($accountId);

        $select = $this->_rightsTable->select()
            # beware of the extra parenthesis of the next 3 rows
            ->where('(' . SQL_TABLE_PREFIX . 'application_rights.account_type = \'group\' and ' . 
                SQL_TABLE_PREFIX . 'application_rights.account_id IN (?)', $groupMemberships)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_id = ?', $accountId)
            ->orWhere(SQL_TABLE_PREFIX . 'application_rights.account_type = \'anyone\' )')

            ->where(SQL_TABLE_PREFIX . 'application_rights.application_id = ?', $application->getId())
            ->where(SQL_TABLE_PREFIX . 'application_rights.right = ?', $_right);
        
        if (!$row = $this->_rightsTable->fetchRow($select)) {
            $result = false;
        } else {
            $result = true;
        }

        // check role rights
        if ( !$result ) {
            $result = Tinebase_Acl_Roles::getInstance()->hasRight($application->getId(), $_accountId, $_right);
        }
        */
        
        $result = Tinebase_Acl_Roles::getInstance()->hasRight($application->getId(), $_accountId, $_right);
        
        return $result;
    }

    /**
     * add right
     *
     * @param Tinebase_Acl_Model_Right $_right
     * @deprecated no longer used because of new role management
     */
    public function addRight(Tinebase_Acl_Model_Right $_right) 
    {
        /*
        if (!$_right->isValid()) {
            throw new Exception('invalid Tinebase_Acl_Model_Right object passed');
        }
                
        $data = $_right->toArray();
                
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
                        
        $this->_rightsTable->insert($data);
        */
    }
    
    /**
     * get application account rights
     *
     * @param   int $_applicationId  app id
     * @return  Tinebase_Record_RecordSet of Tinebase_Acl_Model_Right with account rights for the application
     * @deprecated no longer used because of new role management
     */
    public function getApplicationPermissions($_applicationId)
    {
        /*
        //  
        // $applicationId = Tinebase_Application::convertApplicationIdToInt($_applicationId);
                
        $select = $this->_rightsTable->select()
            ->from(SQL_TABLE_PREFIX . 'application_rights', array( 
                '*', 
                'right' => 'GROUP_CONCAT(' . SQL_TABLE_PREFIX . 'application_rights.right)'
            ))
            ->where(SQL_TABLE_PREFIX . 'application_rights.application_id = ?', $_applicationId)
            ->group(array(SQL_TABLE_PREFIX . 'application_rights.application_id', SQL_TABLE_PREFIX . 'application_rights.account_type', SQL_TABLE_PREFIX . 'application_rights.account_id'));
            
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Acl_Model_Right');

        foreach ($rows as $row) {

            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' rights row: ' . print_r($row, true));
                        
            $applicationRight = new Tinebase_Acl_Model_Right( $row );

            $result->addRecord($applicationRight);
        }

        return $result;
        */
    }
    
    /**
     * set application account rights
     *
     * @param   int $_applicationId  app id
     * @param   Tinebase_Record_RecordSet $_applicationRights  app rights
     * @return  int number of rights set
     * @deprecated no longer used because of new role management
     */
    public function setApplicationPermissions($_applicationId, Tinebase_Record_RecordSet $_applicationRights)
    {
        /*
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' set rights: ' . print_r($_applicationRights, true));
        
        // delete all old rights for this application
        $where = $this->_rightsTable->getAdapter()->quoteInto($this->_rightsTable->getAdapter()->quoteIdentifier('application_id') . ' = ?', $_applicationId);
        $this->_rightsTable->delete($where);        
        
        $count = 0;
        foreach ( $_applicationRights as $right ) {
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' add right: ' . $right->right);
            $this->addRight($right);
            $count++;
        }
        
        return $count;
        */
    }
    
    /**
     * get all possible application rights
     *
     * @param   Tinebase_Record_RecordSet $_applicationRights  app rights (empty/NULL -> Tinebase)
     * @return  array   all application rights
     * 
     */
    public function getAllApplicationRights($_applicationId = NULL)
    {
        // check if tinebase application
        if ( $_applicationId === NULL ) {
            $allRights = array ( self::MANAGE_SHARED_TAGS );
        } else {
            $allRights = array ( self::RUN, self::ADMIN );
        }
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    private function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $rightDescriptions = array(
            self::ADMIN                 => array(
                'text'          => $translate->_('admin'),
                'description'   => $translate->_('admin right description'),
            ),
            self::RUN                   => array(
                'text'          => $translate->_('run'),
                'description'   => $translate->_('run right description'),
            ),
            self::MANAGE_SHARED_TAGS    => array(
                'text'          => $translate->_('manage shared tags'),
                'description'   => $translate->_('manage shared tags right description'),
            ),
        );
        
        return $rightDescriptions;
    }
    
    /**
     * get right description
     * 
     * @param   string right
     * @return  array with text + description
     */
    public function getRightDescription($_right)
    {        
        $result = array(
            'text'          => $_right,
            'description'   => $_right . " right",
        );
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
    
}
