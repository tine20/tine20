<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Timetracker_AbstractTest Test class
 */
abstract class Timetracker_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timetracker_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * timesheet/timeaccounts to delete
     * @var array
     */
    protected $_toDeleteIds = array(
        'ta'    => array(),
        'cf'    => array(),
    );
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_json = new Timetracker_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     * 
     * @todo use this for all ts/ta that are created in the tests
     */
    protected function tearDown()
    {
        $this->_json->deleteTimeaccounts($this->_toDeleteIds['ta']);
        foreach ($this->_toDeleteIds['cf'] as $cf) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cf);
        }
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get Timesheet
     *
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount()
    {
        return new Timetracker_Model_Timeaccount(array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
        ), TRUE);
    }
    
    /**
     * get grants
     *
     * @return array
     */
    protected function _getGrants()
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
                Tinebase_Model_Grants::GRANT_ADMIN                      => TRUE,
            )
        );        
    }
    
    /**
     * get Timesheet (create timeaccount as well)
     *
     * @param string $_taId
     * @param Tinebase_DateTime $_startDate
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet($_taId = NULL, $_startDate = NULL)
    {
        if ($_taId === NULL) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
            $taId = $timeaccount->getId();
        } else {
            $taId = $_taId;
        }
        
        $startDate = ($_startDate !== NULL) ? $_startDate : Tinebase_DateTime::now()->toString('Y-m-d');
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $taId,
            'description'       => 'blabla',
            'start_date'        => $startDate,
            'duration'          => 30,
        ), TRUE);
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
        
        $this->_toDeleteIds['cf'][] = $result->getId();
        
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
        
        if ($_showClosed) {
            $result[] = array(
                'field' => 'showClosed', 
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
     * @return array
     */
    protected function _getTimesheetFilter($_cfFilter = NULL)
    {
        $result = array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        );
        
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
            ),
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
            $date = Tinebase_DateTime::now();
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
        
        // tearDown settings
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        // checks
        $this->assertTrue(array_key_exists($_customField1->name, $timesheetData['customfields']), 'cf 1 not found');
        $this->assertTrue(array_key_exists($customField2->name, $timesheetData['customfields']), 'cf 2 not found');
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
        $this->assertTrue(array_key_exists($_customField1->name, $ts['customfields']));
        $this->assertTrue(array_key_exists($customField2->name, $ts['customfields']));
        $this->assertEquals($timesheetArray[$_customField1->name], $ts['customfields'][$_customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $ts['customfields'][$customField2->name]);
        
        // test search with custom field filter
        $searchResult = $this->_json->searchTimesheets(
            $this->_getTimesheetFilterWithCustomField($_customField1->getId(), $_cf1Value), 
            $this->_getPaging()
        );
        $this->assertGreaterThan(0, $searchResult['totalcount'], 'cf filter not working');
    }
}
