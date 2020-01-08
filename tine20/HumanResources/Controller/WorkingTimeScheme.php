<?php
/**
 * WorkingTimeScheme controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    private function __construct() {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_backend = new HumanResources_Backend_WorkingTimeScheme();
        $this->_modelName = HumanResources_Model_WorkingTimeScheme::class;
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_WorkingTimeScheme
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_WorkingTimeScheme
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    /**
     * get working time account for given employee
     *
     * NOTE: only one generic ta yet!
     *
     * @param HumanResources_Model_Employee $employee
     * @return Timetracker_Model_Timeaccount
     */
    public function getWorkingTimeAccount(HumanResources_Model_Employee $employee)
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
