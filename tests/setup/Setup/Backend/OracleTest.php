<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @version     $Id: SqlTest.php 1703 2008-04-03 18:16:32Z lkneschke $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_Backend_PdoOciTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Setup_Backend_OracleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Setup_Backend_Mysql
     * @access protected
     */
    protected $_backend;
    
    /**
     * @var Setup_Backend_Schema_Table_Abstract
     */
    protected $_table;
    
    protected $_tableXml = '
            <table>
                <name>oracle_test</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>integer</type>
                        <autoincrement>true</autoincrement>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                </declaration>
            </table>';
    
    /**
     * Array holding table names that should be deleted with {@see tearDown}
     * 
     * @var array
     */
    protected $_tableNames = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup Backend Pdo_Oci Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_backend = Setup_Backend_Factory::factory('Oracle');
        
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_tableNames as $tableName) {
            try {
                $this->_backend->dropTable($tableName);
            }
            catch (Zend_Db_Statement_Exception $e) {
                //probably the table already was deleted by a test
            }
        }
    }
    
    protected function _createTestTable()
    {
        $this->_table = Setup_Backend_Schema_Table_Factory::factory('Xml', $this->_tableXml);
        $this->_tableNames[] = $this->_table->name;
        $this->_backend->createTable($this->_table);
    }

    /**
     * Perform some insignificant string format manipulations (add/remove Whitespace).
     * This is needed because the format of the return values of the tested methods 
     * has changed over time and might change again someday.
     * 
     * @param String $_value
     * @return String
     */
    protected function _fixFieldDeclarationString($_value) {
        $return = trim($_value);
        $return = str_replace('  ', ' ', $return);
        return '  ' . $return;
    }

    /**
     * Perform some insignificant string format manipulations (add/remove Whitespace).
     * This is needed because the format of the return values of the tested methods 
     * has changed over time and might change again someday.
     * 
     * @param String $_value
     * @return String
     */
    protected function _fixIndexDeclarationString($_value) {
        $return = trim($_value);
        return '  ' . $return;
    }

    public function testGetCreateStatement()
    {
        $expected = 'CREATE TABLE "' . SQL_TABLE_PREFIX. 'oracle_test" ('."\n".'  "id" NUMBER(11,0) NOT NULL,'."\n".'  "name" VARCHAR2(128) NOT NULL,'."\n".'CONSTRAINT "pk_" PRIMARY KEY ("id")'."\n".')';
        $actual = $this->_backend->getCreateStatement(Setup_Backend_Schema_Table_Factory::factory('Xml', $this->_tableXml));

        $this->assertEquals($expected, $actual);
    }
    
    public function testCreateAndDropTable()
    {
        $this->_createTestTable();
        $this->assertTrue($this->_backend->tableExists($this->_table->name));
        $this->_backend->dropTable($this->_table->name);
        $this->assertFalse($this->_backend->tableExists($this->_table->name));
    }
    
    public function testStringToMysqlFieldStatement_001() 
    {
    	$this->_createTestTable();
    	
        $string ="
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
                <unsigned>true</unsigned>
            </field>";
            
        $statement = $this->_fixFieldDeclarationString('"id" NUMBER(11,0) NOT NULL');    
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        //TODO make addCol work and throw the right exception
        //$this->setExpectedException('Zend_Db_Statement_Exception', '1060'); //1060: Column "id" already exists - expecting Exception'
        //$this->_backend->addCol($this->_table->name, $field);
        
    }

}        
                
                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_OracleTest::main();
}
