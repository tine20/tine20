<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Timetracker_AbstractTest Test class
 */
abstract class Timetracker_AbstractTest extends TestCase
{
    /**
     * @var Timetracker_Frontend_Json
     */
    protected $_json = array();

    /**
     * last record created by _getTime(account|sheet) or _getCustomField
     * @var Tinebase_Record_Abstract
     */
    protected $_lastCreatedRecord = null;

    protected $_deleteTimeAccounts = array();
    protected $_deleteTimeSheets = array();
    protected $_deletePersistentFilters = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        Tinebase_Acl_Roles::getInstance()->resetClassCache();
        $this->_deleteTimeAccounts = array();
        $this->_deleteTimeSheets = array();
        $this->_deletePersistentFilters = array();
        $this->_json = new Timetracker_Frontend_Json();
        
        Sales_Controller_Contract::getInstance()->setNumberPrefix();
        Sales_Controller_Contract::getInstance()->setNumberZerofill();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();

        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        if (count($this->_deleteTimeAccounts) > 0) {
            Timetracker_Controller_Timeaccount::getInstance()->delete($this->_deleteTimeAccounts);
        }
        if (count($this->_deleteTimeSheets) > 0) {
            Timetracker_Controller_Timesheet::getInstance()->delete($this->_deleteTimeSheets);
        }
        if (count($this->_deletePersistentFilters) > 0) {
            Tinebase_PersistentFilter::getInstance()->delete($this->_deleteTimeSheets);
        }
    }

    /************ protected helper funcs *************/

    /**
     * get Timesheet
     *
     * @param array $_data
     * @param boolean $_forceCreation
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount($_data = array(), $_forceCreation = false)
    {
        $defaultData = array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
        );

        $data = array_replace($defaultData, $_data);

        $ta = new Timetracker_Model_Timeaccount($data, true);

        if($_forceCreation) {
            $taRec = $this->_json->saveTimeaccount($ta->toArray(), $_forceCreation);
            $this->_lastCreatedRecord = $taRec;
        }

        return $ta;
    }

    /**
     * get grants
     * 
     * @param boolean $adminRight
     * @return array
     */
    protected function _getGrants($adminRight = FALSE)
    {
        return array(
            array(
                'account_id'    => 0,
                'account_type'  => 'anyone',
                Timetracker_Model_TimeaccountGrants::BOOK_OWN           => TRUE,
                Timetracker_Model_TimeaccountGrants::VIEW_ALL           => TRUE,
                Timetracker_Model_TimeaccountGrants::BOOK_ALL           => TRUE,
                Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE    => TRUE,
                Tinebase_Model_Grants::GRANT_EXPORT                     => TRUE,
                Tinebase_Model_Grants::GRANT_ADMIN                      => $adminRight,
            )
        );
    }

    /**
     * get Timesheet (create timeaccount as well)
     *
     * @param array fields data
     * @param boolean force creation of the record
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet($_data = array(), $_forceCreation = false)
    {
        $defaultData = [
            'account_id' => Tinebase_Core::getUser()->getId(),
            'description' => 'blabla',
            'duration' => 30,
            'accounting_time' => 15,
            'timeaccount_id' => NULL,
            'start_date' => NULL
        ];

        $data = array_replace($defaultData, $_data);

        if ($data['timeaccount_id'] === NULL) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
            $data['timeaccount_id'] = $timeaccount->getId();
        }

        if ($data['start_date'] === NULL) {
            $data['start_date'] = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())
                ->toString('Y-m-d');
        }

        $ts = new Timetracker_Model_Timesheet($data, TRUE);

        if ($_forceCreation) {
            $tsRec = $this->_json->saveTimesheet($ts->toArray());
            $this->_lastCreatedRecord = $tsRec;
        }

        return $ts;
    }

    /**
     * get custom field record
     *
     * @return Tinebase_Model_CustomField_Config
     */
    protected function _getCustomField()
    {
        $record = new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_Timesheet',
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        ));

        $result = Tinebase_CustomField::getInstance()->addCustomField($record);

        $this->_lastCreatedRecord = $result;

        return $result;
    }

    /**
     * get paging
     *
     * @param string $_sort
     * @return array
     */
    protected function _getPaging($_sort = 'creation_time')
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => $_sort,
            'dir' => 'ASC',
        );
    }

    /**
     * get Timeaccount filter
     *
     * @param boolean $_showClosed
     * @return array
     */
    protected function _getTimeaccountFilter($_showClosed = FALSE)
    {
        $result = array(
            array(
                'field' => 'description',
                'operator' => 'contains',
                'value' => 'blabla'
            ),
            array(
                'field' => 'containerType',
                'operator' => 'equals',
                'value' => Tinebase_Model_Container::TYPE_SHARED
            ),
        );

        if (! $_showClosed) {
            $result[] = array(
                'field' => 'is_open',
                'operator' => 'equals',
                'value' => TRUE
            );
        }

        return $result;
    }

    /**
     * get Timesheet filter
     *
     * @param array $_cfFilter
     * @param string $taId
     * @return array
     */
    protected function _getTimesheetFilter($_cfFilter = null, $taId = null)
    {
        $result = array(
            array(
                'field' => 'query',
                'operator' => 'contains',
                'value' => 'blabla'
            ),
        );

        if ($taId) {
            $result[] = [
                'field' => 'timeaccount_id',
                'operator' => 'equals',
                'value' => $taId
            ];
        }

        if ($_cfFilter !== NULL) {
            $result[] = $_cfFilter;
        }

        return $result;
    }

    /**
     * get customfield value filter
     *
     * @param string $_cfid
     * @return array
     */
    protected function _getCfValueFilter($_cfid)
    {
        return array(
            array(
                'field' => 'customfield_id',
                'operator' => 'equals',
                'value' => $_cfid
            ),
            array(
                'field' => 'value',
                'operator' => 'group',
                'value' => ''
            )
        );
    }

    /**
     * get Timesheet filter with custom field
     *
     * @return array
     */
    protected function _getTimesheetFilterWithCustomField($_cfId, $_value)
    {
        return array(
            array(
                'field' => 'query',
                'operator' => 'contains',
                'value' => 'blabla'
            ),
            array(
                'field' => 'customfield',
                'operator' => 'equals',
                'value' => array('cfId' => $_cfId, 'value' => $_value)
            ),
        );
    }

    /**
     * get persistent filter filter
     *
     * @param string $_name
     * @return array
     */
    protected function _getPersistentFilterFilter($_name)
    {
        return array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $_name
            ),
        );
    }

    /**
     * get Timesheet filter with date
     *
     * @param string $_type week filter type
     * @return array
     */
    protected function _getTimesheetDateFilter($_type = 'weekThis')
    {
        $result = array(
            array(
                'field' => 'query',
                'operator' => 'contains',
                'value' => 'blabla'
            ),
            array(
                'field' => 'start_date',
                'operator' => 'after',
                'value' => '2008-12-12'
            ),
        );

        if ($_type == 'inweek') {
            $date = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone());
            $weekNumber = $date->get('W');
            $result[] = array(
                'field' => 'start_date',
                'operator' => 'inweek',
                'value' => $weekNumber
            );
        } else {
            $result[] = array(
                'field' => 'start_date',
                'operator' => 'within',
                'value' => $_type
            );
        }

        return $result;
    }

    /**
     * add timesheet with customfield
     *
     * @param Tinebase_Model_CustomField_Config $_customField1
     * @param string $_cf1Value
     */
    protected function _addTsWithCf($_customField1, $_cf1Value)
    {
        // create custom fields
        $customField2 = $this->_getCustomField();

        // create timesheet and add custom fields
        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$_customField1->name] = $_cf1Value;
        $timesheetArray[$customField2->name] = Tinebase_Record_Abstract::generateUID();

        $timesheetData = $this->_json->saveTimesheet($timesheetArray);
        $this->_deleteTimeSheets[] = $timesheetData['id'];
        $this->_deleteTimeAccounts[] = $timesheetData['timeaccount_id']['id'];

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // checks
        $this->assertTrue((isset($timesheetData['customfields'][$_customField1->name]) || array_key_exists($_customField1->name, $timesheetData['customfields'])), 'cf 1 not found');
        $this->assertTrue((isset($timesheetData['customfields'][$customField2->name]) || array_key_exists($customField2->name, $timesheetData['customfields'])), 'cf 2 not found');
        $this->assertGreaterThan(0, count($timesheetData['customfields']));
        $this->assertEquals($timesheetArray[$_customField1->name], $timesheetData['customfields'][$_customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $timesheetData['customfields'][$customField2->name]);

        // check if custom fields are returned with search
        $searchResult = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertGreaterThan(0, count($searchResult['results'][0]['customfields']));
        foreach($searchResult['results'] as $result) {
            if ($result['id'] == $timesheetData['id']) {
                $ts = $result;
            }
        }
        $this->assertTrue(isset($ts));
        $this->assertTrue((isset($ts['customfields'][$_customField1->name]) || array_key_exists($_customField1->name, $ts['customfields'])));
        $this->assertTrue((isset($ts['customfields'][$customField2->name]) || array_key_exists($customField2->name, $ts['customfields'])));
        $this->assertEquals($timesheetArray[$_customField1->name], $ts['customfields'][$_customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $ts['customfields'][$customField2->name]);

        // test search with custom field filter
        $searchResult = $this->_json->searchTimesheets(
            $this->_getTimesheetFilterWithCustomField($_customField1->getId(), $_cf1Value),
            $this->_getPaging()
        );
        $this->assertGreaterThan(0, $searchResult['totalcount'], 'cf filter not working');
    }

    /**
     * get last created record
     */
    protected function _getLastCreatedRecord() {
        return $this->_lastCreatedRecord;
    }
}
