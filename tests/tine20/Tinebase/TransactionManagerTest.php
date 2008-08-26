<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_TransactionManagerTest::main');
}

class Tinebase_TransactionManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var bool allow the use of GLOBALS to excange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * @var string
     */
    protected $_testTableName = '';
    
    /**
     * @var Tinebase_TransactionManager
     */
    protected $_instance = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_TransactionManagerTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_testTableName = SQL_TABLE_PREFIX . 'transactiontest';
        $this->_instance = Tinebase_TransactionManager::getInstance();
    }
    
    /**
     * create test data in database instance
     *
     * @param Zend_Db_Adapter_Abstract $_db
     */
    protected function _createDbTestTable($_db)
    {
        $tableName = $_db->quoteIdentifier($this->_testTableName);
        $_db->query("DROP TABLE IF EXISTS $tableName;");
        $_db->query(
            "CREATE TABLE $tableName (
                " . $_db->quoteIdentifier('Column1') . " varchar(64),
                " . $_db->quoteIdentifier('Column2') . " varchar(64)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }
    
    protected function _cleanDbTestTable($db)
    {
        
    }
    
    /**
     * test getInstance()
     *
     */
    public function testGetInstance()
    {
        $instance = Tinebase_TransactionManager::getInstance();
        $this->assertTrue($instance instanceof Tinebase_TransactionManager, 'Could not get an instance of Tinebase_TransactionManager');
    }
    
    /**
     * tests transaction ids
     *
     */
    public function testUniqeTransactionIds()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $transactionId01 = $this->_instance->startTransaction($db);
        $this->assertType('string', $transactionId01, 'TransactionId is not a string');
        $this->assertGreaterThan(10, strlen($transactionId01) , 'TransactionId is weak');
        
        $transactionId02 = $this->_instance->startTransaction($db);
        $this->assertNotEquals($transactionId01, $transactionId02, 'TransactionId is not unique');
        
        $this->_instance->rollBack();
    }
    
    /**
     * test transaction exception on non transactionable
     * 
     */
    public function testNonTransactionable()
    {
        $date = Zend_Date::now();
        $this->setExpectedException('Exception');
        $this->_instance->startTransaction($date);
    }
    
    public function testOneDbSingleTransaction()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $transactionId = $this->_instance->startTransaction($db);
        $this->_createDbTestTable($db);
        $db->insert($this->_testTableName, array(
            'Column1' => $transactionId
        ));
        $this->_instance->commitTransaction($transactionId);
        
        $columns = $db->fetchAll("SELECT * FROM " . $this->_testTableName . " WHERE " . $db->quoteInto('Column1 = ?', $transactionId) . ";");
        $this->assertEquals(1, count($columns), 'Transaction failed');
        $this->assertEquals($transactionId, $columns[0]['Column1'], 'Transaction was not executed properly');
    }

    public function testOneDbRollback()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $transactionId = $this->_instance->startTransaction($db);
        $this->_createDbTestTable($db);
        $db->insert($this->_testTableName, array(
            'Column1' => $transactionId
        ));
        $this->_instance->rollBack();
        
        $columns = $db->fetchAll("SELECT * FROM " . $this->_testTableName . " WHERE " . $db->quoteInto('Column1 = ?', $transactionId) . ";");
        foreach ($columns as $column) {
            $this->assertNotEquals($transactionId, $column['Column1'], 'RollBack failed, data was inserted anyway');
        }
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_TransactionManagerTest::main') {
    AllTests::main();
}