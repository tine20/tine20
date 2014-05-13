<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metawys.de
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Inventory_TestCase
 */
class ExampleApplication_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var ExampleApplication_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ExampleApplication Json Tests');
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }
    
    /**
     * get ExampleRecord record
     *
     * @return Inventory_Model_ExampleRecord
     */
    protected function _getExampleRecord()
    {
        return new ExampleApplication_Model_ExampleRecord(array(
                'name' => 'minimal example record by PHPUnit::ExampleApplication_JsonTest'
        ));
    }
    
    /**
     * get filter for ExampleApplication search
     *
     * @return Tasks_Model_Task
     */
    protected function _getFilter()
    {
        // define filter
        return array(
                array('field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'),
                array('field' => 'name'        , 'operator' => 'contains',    'value' => 'example record by PHPUnit'),
                array('field' => 'due'         , 'operator' => 'within',      'value' => 'dayThis'),
        );
    }
    
    /**
     * get default paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        // define paging
        return array(
                'start' => 0,
                'limit' => 50,
                'sort' => 'name',
                'dir' => 'ASC',
        );
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
}
