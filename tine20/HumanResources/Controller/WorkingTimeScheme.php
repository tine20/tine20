<?php
/**
 * WorkingTimeScheme controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * WorkingTimeScheme controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_WorkingTimeScheme extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = [['title']];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_backend = new HumanResources_Backend_WorkingTimeScheme();
        $this->_modelName = HumanResources_Model_WorkingTimeScheme::class;
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
    }

    /**
     * check rights
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

        if (self::ACTION_GET !== $_action && !Tinebase_Core::getUser()
                ->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' working time schemes.');
        }
        parent::_checkRight($_action);
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        /** @var HumanResources_Model_FreeTime $_record */
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        // if you dont have manage_employee, you may only GET
        if (self::ACTION_GET !== $_action) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }

        if (HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL !==
                $_record->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE}) {
            // if we have update employee data on any division -> we see all shared / templates everything
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Division::class, [
                ['field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'],
            ]);
            $filter->setRequiredGrants([HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA]);
            $divisionCtrl = HumanResources_Controller_Division::getInstance();
            $oldValue = $divisionCtrl->doContainerACLChecks(false);
            try {
                if (0 < $divisionCtrl->searchCount($filter)) {
                    return true;
                }
            } finally {
                $divisionCtrl->doContainerACLChecks($oldValue);
            }
        }

        // if we see a contract with this working time scheme, we do see the working time scheme
        if (0 < HumanResources_Controller_Contract::getInstance()->searchCount(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Contract::class, [
                    ['field' => HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME, 'operator' => 'equals', 'value' => $_record->getId()],
                ]))) {
            return true;
        }

        if ($_throw) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        return false;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks) {
            return;
        }

        // DO NOT CALL PARENT, we do not have a container! this is a pure fake filter acl function!

        // if we have manage_employee right, we need no acl filter
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return;
        }

        if (self::ACTION_GET !== $_action) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' working time schemes.');
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
        }

        $orWrapper = new Tinebase_Model_Filter_FilterGroup([], Tinebase_Model_Filter_FilterGroup::CONDITION_OR);

        // if we have update employee data on any division -> we see all shared / templates
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Division::class, [
            ['field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'],
        ]);
        $filter->setRequiredGrants([HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA]);
        $divisionCtrl = HumanResources_Controller_Division::getInstance();
        $oldValue = $divisionCtrl->doContainerACLChecks(false);
        try {
            if (0 < $divisionCtrl->searchCount($filter)) {
                // add where type !== individual
                $orWrapper->addFilter(
                    $_filter->createFilter(['field' => HumanResources_Model_WorkingTimeScheme::FLDS_TYPE, 'operator' => 'not', 'value' => HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL])
                );
            }
        } finally {
            $divisionCtrl->doContainerACLChecks($oldValue);
        }

        // add or where id in (?) <- distinct wts ids from contracts that we see
        $wtsIds = array_keys(HumanResources_Controller_Contract::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Contract::class),
            /*Pagi*/null, /*Rels*/ false, [HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME]
        ));
        $orWrapper->addFilter($_filter->createFilter(['field' => 'id', 'operator' => 'in', 'value' => $wtsIds]));

        $_filter->addFilterGroup($orWrapper);
    }


    /**
     * get working time account for given employee
     *
     * NOTE: only one generic ta yet!
     *
     * @param ?HumanResources_Model_Employee $employee
     * @return Timetracker_Model_Timeaccount
     */
    public function getWorkingTimeAccount(?HumanResources_Model_Employee $employee)
    {
        $timeaccountId = HumanResources_Config::getInstance()->get(HumanResources_Config::WORKING_TIME_TIMEACCOUNT);
        $tac = Timetracker_Controller_Timeaccount::getInstance();
        $aclUsage = $tac->assertPublicUsage();

        try {
            if ($timeaccountId) {
                try {
                    $timeaccount = $tac->get($timeaccountId);
                } catch (Tinebase_Exception_NotFound $e) {
                    $timeaccount = null;
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__
                        . " configured workingtime account $timeaccountId can not be found");

                }
            }

            if (! ($timeaccountId && $timeaccount)) {
                $i18n = Tinebase_Translation::getTranslation('HumanResources');
                $timeaccount = $tac->create(new Timetracker_Model_Timeaccount([
                    'number' => 'HRWT',
                    'title' => $i18n->translate('HR Empoyee Working Time'),

                ]), false);
                HumanResources_Config::getInstance()->set(HumanResources_Config::WORKING_TIME_TIMEACCOUNT, $timeaccount->getId());
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__
                    . " created new workingtime account {$timeaccount->getId()}");

            }
        } finally {
            $aclUsage();
        }

        return $timeaccount;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_sortBLPipe($_record);
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $this->_sortBLPipe($_record);
    }

    protected function _sortBLPipe($_record)
    {
        if (!empty($_record->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE})) {
            if (is_array($_record->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE})) {

                $_record->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE} = new Tinebase_Record_RecordSet(
                    HumanResources_Model_BLDailyWTReport_Config::class,
                    $_record->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE});

                $_record->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE}->sort(
                    function(Tinebase_Model_BLConfig $val1, Tinebase_Model_BLConfig $val2) {
                        return $val1->{Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD}
                            ->cmp($val2->{Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD});
                    });
            }
        }
    }
}
