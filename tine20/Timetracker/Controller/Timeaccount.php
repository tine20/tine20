<?php
/**
 * Timeaccount controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Timeaccount controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timeaccount extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timeaccount();
        $this->_modelName = 'Timetracker_Model_Timeaccount';
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Timetracker_Controller_Timeaccount
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timeaccount
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timetracker_Controller_Timeaccount();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
    
    /**
     * add one record
     * - create new container as well
     *
     * @param   Timetracker_Model_Timeaccount $_record
     * @return  Timetracker_Model_Timeaccount
     * 
     * @todo    check if container name exists ?
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $this->_checkRight('create');
        
        // create container and add container_id to record
        $containerName = $_record->title;
        if (!empty($_record->number)) {
            $containerName = $_record->number . ' ' . $containerName;
        }
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $containerName,
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => $this->_backend->getType(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'model'             => 'Timetracker_Model_Timeaccount'
        ));
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Timetracker_Model_TimeaccountGrants::BOOK_OWN           => TRUE,
            Timetracker_Model_TimeaccountGrants::VIEW_ALL           => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_ALL           => TRUE,
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE    => TRUE,
            Tinebase_Model_Grants::GRANT_EXPORT                     => TRUE,
            Tinebase_Model_Grants::GRANT_ADMIN                      => TRUE,
        )));
        
        // add container with grants (all grants for creator) and ignore ACL here
        $container = Tinebase_Container::getInstance()->addContainer(
            $newContainer, 
            $grants, 
            TRUE
        );

        $_record->container_id = $container->getId();
        
        $timeaccount = parent::create($_record);
        
        // save grants
        if (count($_record->grants) > 0) {
            Timetracker_Model_TimeaccountGrants::setTimeaccountGrants($timeaccount, $_record->grants);
        }        

        return $timeaccount;
    }    
    
    /**
     * Returns a set of leads identified by their id's
     * - overwritten because we use different grants here (MANAGE_TIMEACCOUNTS)
     * 
     * @param   array $_ids       array of record identifiers
     * @param   bool  $_ignoreACL don't check acl grants
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = FALSE)
    {
        $this->_checkRight('get');
        
        $filter = new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'id',          'operator' => 'in',     'value' => $_ids)
        ));
        $records = $this->search($filter);

        return $records;
    }
    
    /**
     * update one record
     * - save timeaccount grants
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $timeaccount = parent::update($_record);

        // save grants
        if (count($_record->grants) > 0) {
            Timetracker_Model_TimeaccountGrants::setTimeaccountGrants($timeaccount, $_record->grants);
        }

        return $timeaccount;
    }
    
    /**
     * delete linked objects / timesheets
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        // delete linked timesheets
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($_record->getId());
        Timetracker_Controller_Timesheet::getInstance()->delete($timesheets->getArrayOfIds());
        
        // delete other linked objects
        parent::_deleteLinkedObjects($_record);
    }

    /**
     * check timeaccount rights
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        $hasRight = $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
        
        switch ($_action) {
            case 'create':
                $hasRight = $this->checkRight(Timetracker_Acl_Rights::ADD_TIMEACCOUNTS, FALSE);
            case 'get':
                // is allowed for everybody
                $hasRight = TRUE;
                break;
        }
        
        if (! $hasRight) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' timeaccounts.');
        }
         
        return;
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Timetracker_Model_Timeaccount $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if ($_action == 'create') {
            // no check here because the MANAGE_TIMEACCOUNTS right has been already checked before
            return TRUE;
        }
        
        $hasGrant = Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Tinebase_Model_Grants::GRANT_ADMIN);
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    $hasGrant
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), array(
                        Timetracker_Model_TimeaccountGrants::VIEW_ALL, 
                        Timetracker_Model_TimeaccountGrants::BOOK_OWN, 
                        Timetracker_Model_TimeaccountGrants::BOOK_ALL, 
                        Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                    ))
                );
            case 'delete':
            case 'update':
                $hasGrant = (
                    $hasGrant
                    || $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
                );
                break;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }

    /**
     * Removes containers where current user has no access to
     * 
     * @param Timetracker_Model_TimeaccountFilter $_filter
     * @param string $_action
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        switch ($_action) {
            case 'get':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'update':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'export':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_EXPORT,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            default:
                throw new Timetracker_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
    }         
}
