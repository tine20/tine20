<?php
/**
 * Timeaccount controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Timeaccount controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timeaccount extends Tinebase_Controller_Record_Container
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timeaccount();
        $this->_modelName = 'Timetracker_Model_Timeaccount';
        $this->_grantsModel = 'Timetracker_Model_TimeaccountGrants';
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
        $this->_manageRight = Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
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
            self::$_instance = new self();
        }
        
        return self::$_instance;
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
     * inspects delete action
     *
     * @param array $_ids
     * @return array records to actually delete
     */
    protected function _inspectDelete(array $_ids): array
    {
        $inUseIds = [];

        foreach ($_ids as $id) {
            $timeSheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($id);
            if ($timeSheets->count() > 0) {
                array_push($inUseIds, $id);
            }
        }

        $timeAccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple($inUseIds);

        if ($timeAccounts->count() > 0) {
            $context = $this->getRequestContext();

            if (!$context || (!array_key_exists('confirm', $context) &&
                    (!isset($context['clientData']) || !array_key_exists('confirm', $context['clientData'])))) {
                $translation = Tinebase_Translation::getTranslation($this->_applicationName);
                $exception = new Tinebase_Exception_Confirmation(
                    $translation->_('Timeaccounts are still in use! Are you sure you want to delete them?'));

                $timeAccountTitles = null;

                foreach ($timeAccounts as $timeaccount) {
                    $timeAccountTitles .= '<br />' . $timeaccount->number . ', ' . $timeaccount->title;
                }

                // todo: show more info about in used time accounts ?
                //$exception->setInfo($timeAccountTitles);
                throw $exception;
            }
        }

        return parent::_inspectDelete($_ids);
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
        if (! $this->_doRightChecks) {
            return;
        }
        
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

        parent::_checkRight($_action);
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
        if ($_action == 'create' || $this->_doGrantChecks == FALSE) {
            // no check here because the MANAGE_TIMEACCOUNTS right has been already checked before
            return TRUE;
        }
        
        $hasGrant = Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->getId(), Tinebase_Model_Grants::GRANT_ADMIN);
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    $hasGrant
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->getId(), array(
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
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @throws Timetracker_Exception_UnexpectedValue
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
    /**
     * 
     * @param Sales_Model_CostCenter|string $costCenterId
     * @return Tinebase_Record_RecordSet
     */
    public function getTimeaccountsBySalesCostCenter($costCenterId)
    {
        $costCenterId = is_string($costCenterId) ? $costCenterId : $costCenterId->getId();
        
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Sales_Model_CostCenter'),
            array('field' => 'related_id', 'operator' => 'equals', 'value' => $costCenterId),
            array('field' => 'own_model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'),
            array('field' => 'type', 'operator' => 'equals', 'value' => 'COST_CENTER'),
        ), 'AND');
        
        return Timetracker_Controller_Timeaccount::getInstance()->getMultiple(Tinebase_Relations::getInstance()->search($filter)->own_id);
    }
    
    /**
     * @param Sales_Model_Contract $contractId
     * @return Tinebase_Record_RecordSet
     */
    public function getTimeaccountsBySalesContract($contractId)
    {
        $contractId = is_string($contractId) ? $contractId : $contractId->getId();
        
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Sales_Model_Contract'),
            array('field' => 'related_id', 'operator' => 'equals', 'value' => $contractId),
            array('field' => 'own_model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'),
            array('field' => 'type', 'operator' => 'equals', 'value' => 'TIME_ACCOUNT'),
        ), 'AND');
        
        return Sales_Controller_Contract::getInstance()->getMultiple(Tinebase_Relations::getInstance()->search($filter)->own_id);
    }

    /**
     * @param Tinebase_Model_Container $_container
     * @param bool $_ignoreAcl
     * @param null $_filter
     */
    public function deleteContainerContents(Tinebase_Model_Container $_container, $_ignoreAcl = FALSE, $_filter = null)
    {
        // don't do anything here - timeaccount "contents" aka timesheets are deleted in _deleteLinkedObjects()
    }
}
