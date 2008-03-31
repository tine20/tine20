<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Timemachine_ModificationLogTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Timemachine_ModificationLogTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Tinebase_Timemachine_ModificationLog
	 */
	protected $_modLogClass;
	
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_logEntries;
    
    /**
     * @var Tinebase_Record_RecordSet
     * Persistant Records we need to cleanup at tearDown()
     */
    protected $_persistantLogEntries;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Timemachine_ModificationLogTest');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Lets update a record tree times
     *
     * @access protected
     */
    protected function setUp()
    {
    	$this->_modLogClass = Tinebase_Timemachine_ModificationLog::getInstance();
    	$this->_persistantLogEntries = new Tinebase_Record_RecordSet('Tinebase_Timemachine_Model_ModificationLog');
    	
    	$this->_logEntries = new Tinebase_Record_RecordSet('Tinebase_Timemachine_Model_ModificationLog', array(
        array(
            'application_id'       => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'record_id'            => '5dea69be9c72ea3d263613277c3b02d529fbd8bc',
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => Zend_Date::now()->addDay(-2),
            'modification_account' => 7,
            'modified_attribute'   => 'TestAttribute',
            'old_value'            => 'InitialValue'
        ),
        array(
            'application_id'       => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'record_id'            => '5dea69be9c72ea3d263613277c3b02d529fbd8bc',
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => Zend_Date::now()->addDay(-1),
            'modification_account' => 7,
            'modified_attribute'   => 'TestAttribute',
            'old_value'            => 'FirstUpdate'
        ),
        array(
            'application_id'       => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'record_id'            => '5dea69be9c72ea3d263613277c3b02d529fbd8bc',
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => Zend_Date::now(),
            'modification_account' => 7,
            'modified_attribute'   => 'TestAttribute',
            'old_value'            => 'SecondUpdate'
        )), true, false);
        
        foreach ($this->_logEntries as $logEntry) {
        	$id = $logEntry->generateUID();
        	
        	$logEntry->setId($id);
        	$this->_modLogClass->setModification($logEntry);
        	$this->_persistantLogEntries->addRecord($this->_modLogClass->getModification($id));
        }
    }

    /**
     * cleanup database
     * @access protected
     */
    protected function tearDown()
    {
        $table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'timemachine_modificationlog'));
        
        foreach ($this->_persistantLogEntries as $persistantLogEntry) {
             $table->delete($table->getAdapter()->quoteInto('id = ?', $persistantLogEntry->getId()));
        }
    }
    
    public function testGetModification()
    {
    	foreach ($this->_logEntries as $num => $logEntry) {
    		$RawLogEntry = $logEntry->toArray();
    		$RawPersistantLogEntry = $this->_persistantLogEntries[$num]->toArray();
    		
    		foreach ($RawLogEntry as $field => $value) {
    			$persistantValue = $RawPersistantLogEntry[$field];
    			if ($value != $persistantValue) {
    				$this->fail("Failed asserting that contents of saved LogEntry #$num in field $field equals initial datas. \n" . 
    				            "Expected '$value', got '$persistantValue'");
    			}
    		}
    	}
    	$this->assertTrue(true);
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_Timemachine_ModificationLogTest::main') {
    Tinebase_Timemachine_ModificationLogTest::main();
}
