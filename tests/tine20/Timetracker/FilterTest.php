<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * this tests some filters
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Timetracker_FilterTest extends Timetracker_AbstractTest
{
    /**
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = array();
    
    /**
     * objects
     *
     * @var array
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Filter Tests');
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
        parent::setUp();
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
    }
    
    /************ test functions follow **************/

    /**
     * test timeaccount - sales contract filter
     * also tests Tinebase_Model_Filter_ExplicitRelatedRecord
     */
    public function testTimeaccountContractFilter()
    {
        $this->_getTimeaccount(array('title' => 'TA1', 'number' => 12345), true);
        $ta1 = $this->_timeaccountController->get($this->_lastCreatedRecord['id']);
        
        $this->_getTimeaccount(array('title' => 'TA2', 'number' => 12346), true);
        $ta2 = $this->_timeaccountController->get($this->_lastCreatedRecord['id']);
        
        $cId = Tinebase_Container::getInstance()->getDefaultContainer('Sales_Model_Contract')->getId();
        $contract = Sales_Controller_Contract::getInstance()->create(new Sales_Model_Contract(
            array('title' => 'testRelateTimeaccount', 'number' => Tinebase_Record_Abstract::generateUID(), 'container_id' => $cId)
        ));
        $ta1->relations = array($this->_getRelation($contract, $ta1));
        $this->_timeaccountController->update($ta1);
        
        // search by contract
        $f = new Timetracker_Model_TimeaccountFilter(array(array('field' => 'contract', 'operator' => 'AND', 'value' =>
            array(array('field' => ':id', 'operator' => 'equals', 'value' => $contract->getId()))
        )));

        $result = $this->_timeaccountController->search($f);
        $this->assertEquals(1, $result->count());
        $this->assertEquals('TA1', $result->getFirstRecord()->title);
        
        // test empty filter (without contract)
        $f = new Timetracker_Model_TimeaccountFilter(array(array('field' => 'contract', 'operator' => 'AND', 'value' =>
            array(array('field' => ':id', 'operator' => 'equals', 'value' => null))
        )));

        $result = $this->_timeaccountController->search($f);

        $this->assertEquals(1, $result->count());
        $this->assertEquals('TA2', $result->getFirstRecord()->title);
        
        // test generic relation filter
        $f = new Timetracker_Model_TimeaccountFilter(array(array('field' => 'foreignRecord', 'operator' => 'AND', 'value' =>
            array('appName' => 'Sales', 'linkType' => 'relation', 'modelName' => 'Contract',
                'filters' => array('field' => 'query', 'operator' => 'contains', 'value' => 'TA1'))
        )));
        $result = $this->_timeaccountController->search($f);
        $this->assertEquals(1, $result->count());
        $this->assertEquals('TA1', $result->getFirstRecord()->title);
    }

    /**
     * returns timeaccount-contract relation
     * @param Sales_Model_Contract $contract
     * @param Timetracker_Model_Timeaccount $timeaccount
     */
    protected function _getRelation($contract, $timeaccount)
    {
        $r = new Tinebase_Model_Relation();
        $ra = array(
            'own_model' => 'Timetracker_Model_Timeaccount',
            'own_backend' => 'Sql',
            'own_id' => $timeaccount->getId(),
            'own_degree' => 'sibling',
            'remark' => 'phpunit test',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'CONTRACT');
        $r->setFromArray($ra);
        return $r;
    }
}
