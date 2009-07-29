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
     * @var    Setup_Backend_Oracle
     * @access protected
     */
    protected $_backend;
    
    /**
     * Array holding table names that should be deleted with {@see tearDown}
     * 
     * @var array
     */
    protected $_tableNames = array();
    
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
        $this->_createTestTable();
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

    
    
    
    
    public function testGetCreateStatement()
    {
        $expected = 'CREATE TABLE "' . SQL_TABLE_PREFIX. 'oracle_test" ('."\n".'  "id" NUMBER(11,0) NOT NULL,'."\n".'  "name" VARCHAR2(128) NOT NULL,'."\n".'CONSTRAINT "pk_' . $this->_table->name .'" PRIMARY KEY ("id")'."\n".')';
        $actual = $this->_backend->getCreateStatement(Setup_Backend_Schema_Table_Factory::factory('Xml', $this->_tableXml));

        $this->assertEquals($expected, $actual);
    }
    
    public function testTableExists()
    {
        $this->assertTrue($this->_backend->tableExists($this->_table->name));
        $this->_backend->dropTable($this->_table->name);
        $this->assertFalse($this->_backend->tableExists($this->_table->name));
    }
    
    public function testColumnExists()
    {
    	$columntName = 'testColumnExists';
        $string ="
                <field>
                    <name>$columntName</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);

        $this->assertFalse($this->_backend->columnExists($columntName, $this->_table->name));
        $this->_backend->addCol($this->_table->name, $field);
        $this->assertTrue($this->_backend->columnExists($columntName, $this->_table->name));
    }
    
    public function testSequenceExists()
    {
        //Tests standard test table (with sequence)
        $this->assertTrue($this->_backend->sequenceExists($this->_table->name));

        //Tests table without sequence
        $tableXml = '
        <table>
            <name>oracle_seq_test</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>true</notnull>
                </field>
            </declaration>
        </table>';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXml);
        $this->_tableNames[] = $table->name;
        $this->_backend->createTable($table);
        $this->assertFalse($this->_backend->sequenceExists($table->name));
    }
    
    public function testGetExistingSchema()
    {
    	$schema = $this->_backend->getExistingSchema($this->_table->name);
    	$this->assertEquals(2, count($schema->fields));
    	
    	$idField = $schema->fields[0];
    	$this->assertEquals('true', $idField->notnull, 'Test idField->notnull');
    	$this->assertEquals('true', $idField->primary, 'Test idField->primary');
    	$this->assertEquals('true', $idField->autoincrement, 'Test idField->auto_increment');
    	$this->assertTrue(empty($idField->unsigned), 'Test idField->unsigned');
    	
    }
    
    public function testAddCol() 
    {
        $string ="
                <field>
                    <name>testAddCol</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"testAddCol" VARCHAR2(25) NOT NULL');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));

        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToMysqlFieldStatement_001() 
    {
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


    
    
    protected function _createTestTable()
    {
        $this->_table = Setup_Backend_Schema_Table_Factory::factory('Xml', $this->_tableXml);
        $this->_tableNames[] = $this->_table->name;
        try {
            $this->_backend->createTable($this->_table);
        } catch (Zend_Db_Statement_Exception $e) {
        	$this->_backend->dropTable($this->_table->name);
        	$this->_backend->createTable($this->_table);
        }
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
    
}        


                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_OracleTest::main();
}
