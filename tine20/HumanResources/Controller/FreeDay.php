<?php
/**
 * FreeDay controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeDay controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeDay extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeDay();
        $this->_modelName = HumanResources_Model_FreeDay::class;
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_traitDelegateAclField = 'freetime_id';
        $this->_traitGetOwnGrants = [
            HumanResources_Model_DivisionGrants::READ_OWN_DATA,
            HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST
        ];
    }

    protected function _getCheckFilterACLTraitFilter()
    {
        return new Tinebase_Model_Filter_ForeignId('freetime_id', 'definedBy', [
            ['field' => 'employee_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ],
            ]], [
                'controller' => HumanResources_Controller_FreeTime::class,
                'filtergroup' => HumanResources_Model_FreeTimeFilter::class,
            ]);
    }

    // we do not implement this at all, we solely depend on free times doing its job (vie delegated acl)
    // protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $_record->event = null;
        $this->inspectFreeDay($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $this->inspectFreeDay($_record, $_oldRecord);
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);
        if ($record->event) {
            $raii = new Tinebase_RAII(Calendar_Controller_Event::getInstance()->assertPublicUsage());
            Calendar_Controller_Event::getInstance()->delete($record->event);
            unset($raii);
        }
    }

    public function inspectFreeDay(HumanResources_Model_FreeDay $freeDay, ?HumanResources_Model_FreeDay $oldFreeDay = null): void
    {
        if ($oldFreeDay) {
            $freeDay->event = $oldFreeDay->event;
        }

        $freeTime = $this->_getFreeTime($freeDay->freetime_id);
        if (!$freeTime->employee_id->division_id->{HumanResources_Model_Division::FLD_FREE_TIME_CAL} ||
                !$freeTime->type->allow_planning || HumanResources_Config::FREE_TIME_PROCESS_STATUS_DECLINED ===
                    $freeTime->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS}) {
            if ($freeDay->event) {
                $raii = new Tinebase_RAII(Calendar_Controller_Event::getInstance()->assertPublicUsage());
                Calendar_Controller_Event::getInstance()->delete($freeDay->event);
                unset($raii);
            }
            return;
        }

        if ((!$oldFreeDay || !$oldFreeDay->event) && !$freeDay->event) {
            $account = Tinebase_User::getInstance()->getFullUserById($freeTime->employee_id->account_id);
            $raii = new Tinebase_RAII(Calendar_Controller_Event::getInstance()->assertPublicUsage());
            $event = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
                'dtstart' => $freeDay->date->getClone()->setTime(0,0,0,0),
                'is_all_day_event' => true,
                'container_id' => $freeTime->employee_id->division_id->{HumanResources_Model_Division::FLD_FREE_TIME_CAL},
                'organizer' => $account->contact_id,
                'description' => 'absence',
                'attendee' => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [[
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                        'user_id' => $account->contact_id,
                        'status' => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED ===
                            $freeTime->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} ?
                            Calendar_Model_Attender::STATUS_ACCEPTED : Calendar_Model_Attender::STATUS_TENTATIVE,
                    ]])
            ]));
            unset($raii);

            $freeDay->event = $event->getId();
            return;
        }

        if ($oldFreeDay && $oldFreeDay->event) {
            $freeDay->event = $oldFreeDay->event;
        }

        $raii = new Tinebase_RAII(Calendar_Controller_Event::getInstance()->assertPublicUsage());
        $event = Calendar_Controller_Event::getInstance()->get($freeDay->event);
        $updateEvent = false;
        if (!$event->dtstart->equals($freeDay->date->getClone()->setTime(0,0,0,0))) {
            $event->dtstart = $freeDay->date->getClone()->setTime(0,0,0,0);
            $updateEvent = true;
        }
        if ((HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED ===
                $freeTime->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} ?
                Calendar_Model_Attender::STATUS_ACCEPTED : Calendar_Model_Attender::STATUS_TENTATIVE) !==
                $event->attendee->getFirstRecord()->status) {
            $event->attendee->getFirstRecord()->status = HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED ===
                $freeTime->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} ?
                Calendar_Model_Attender::STATUS_ACCEPTED : Calendar_Model_Attender::STATUS_TENTATIVE;
            $updateEvent = true;
        }
        if ($updateEvent) {
            Calendar_Controller_Event::getInstance()->update($event);
        }
        unset($raii);
    }

    protected function _getFreeTime(string $id): HumanResources_Model_FreeTime
    {
        if (!isset($this->_freetime[$id])) {
            $ft = HumanResources_Controller_FreeTime::getInstance()->get($id);
            (new Tinebase_Record_Expander(HumanResources_Model_FreeTime::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => ['type' => []]
            ]))->expand(new Tinebase_Record_RecordSet(HumanResources_Model_FreeTime::class, [$ft]));
            $this->_freetime[$id] = $ft;
        }
        return $this->_freetime[$id];
    }

    /**
     * @var array<HumanResources_Model_FreeTime>
     */
    protected $_freetime = [];
}
