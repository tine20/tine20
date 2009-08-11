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
class Setup_Backend_OracleTest extends BaseTest
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

    public function testOracleDbAdapterIsQuoted()
    {
        $prefix = 'phpunit';
        $dbProxy = $this->_getDbProxy($prefix);
        $testString = 'This is NOT QUOTED but this is "QUOTED" and this is NOT QUOTED and "still NOT "QUOTED" but here yes it is QUOTED" but this is \"NOT QUOTED\" while this is \\\\"QUOTED\\\\".'; 
        
        $this->assertFalse($dbProxy->proxy_isQuoted($testString, 12, '"'), 'Test position of "THIS1"');
        $this->assertTrue($dbProxy->proxy_isQuoted($testString, 32, '"'), 'Test position of "THIS2"');
        $this->assertFalse($dbProxy->proxy_isQuoted($testString, 56, '"'), 'Test position of "THIS3"');
        $this->assertFalse($dbProxy->proxy_isQuoted($testString, 79, '"'), 'Test position of "THIS4"');
        $this->assertTrue($dbProxy->proxy_isQuoted($testString, 106, '"'), 'Test position of "THIS5"');
        $this->assertFalse($dbProxy->proxy_isQuoted($testString, 132, '"'), 'Test position of "THIS6"');
        $this->assertTrue($dbProxy->proxy_isQuoted($testString, 158, '"'), 'Test position of "THIS7"');
    }
    
    public function testOracleDbAdapterPositionalToNamedParameters()
    {
        $db = Tinebase_Core::getDb();
        $prefix = 'phpunit';
        $dbProxy = $this->_getDbProxy($prefix);
        
        $sqlOrig = 'test';
        $bindOrig = array();
        list($sqlConverted, $bindConverted) = $dbProxy->proxy_positionalToNamedParameters($sqlOrig, $bindOrig);
        $this->assertEquals($sqlOrig, $sqlConverted);
        $this->assertEquals($bindOrig, $bindConverted);
        
        $sqlOrig = 'select x from y where x=?';
        $bindOrig = array('z');
        
        list($sqlConverted, $bindConverted) = $dbProxy->proxy_positionalToNamedParameters($sqlOrig, $bindOrig);
        $this->assertFalse(strpos($sqlConverted, '?'));
        $this->assertTrue(false !== strpos($sqlConverted, ':' . $prefix . '0'));
        $this->assertEquals($bindConverted[$prefix . '0'], 'z');

        $sqlOrig = "select x from y where z='why?' and a=?";
        $bindOrig = array('b');
        
        list($sqlConverted, $bindConverted) = $dbProxy->proxy_positionalToNamedParameters($sqlOrig, $bindOrig);
        $this->assertEquals("select x from y where z='why?' and a=:" . $prefix . '0', $sqlConverted);
        $this->assertTrue(false !== strpos($sqlConverted, ':' . $prefix . '0'));
        $this->assertEquals($bindConverted[$prefix . '0'], 'b');
    }
    
    public function testQuestionMarkInFieldValue()
    {
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        //$value = 'test ??  . ?=? ..';
        $value = 'test?';
        $db->insert($tableName, array('name' => $value));
        $result = $db->fetchCol($db->select()->from($tableName, 'name'));
        $this->assertEquals($value, $result[0]);
        
        $db->query('INSERT INTO ' . $db->quoteIdentifier($tableName) . ' (' . $db->quoteIdentifier('name') . ') VALUES (' . $db->quote($value, 'text') . ')');
        $result = $db->fetchCol($db->select()->from($tableName, 'name'));
        $this->assertEquals($value, $result[1]);
        
        $string ="
                <field>
                    <name>test</name>
                    <type>text</type>
                    <length>25</length>
                </field>";
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);

        $db->query('INSERT INTO ' . $db->quoteIdentifier($tableName) . ' (' . $db->quoteIdentifier('name') . ', ' . $db->quoteIdentifier('test') . ') VALUES (' . $db->quote($value, 'text') . ', ?)', array('test value for col 2'));
        $result = $db->fetchCol($db->select()->from($tableName, 'name'));
        $this->assertEquals($value, $result[1]);
    }
    
    public function testGetCreateStatement()
    {
        $expected = 'CREATE TABLE "' . SQL_TABLE_PREFIX. 'oracle_test" ('."\n".'  "id" NUMBER(11,0) NOT NULL,'."\n".'  "name" VARCHAR2(128) NOT NULL,'."\n".'  CONSTRAINT "' . SQL_TABLE_PREFIX . 'pk_' . $this->_table->name .'" PRIMARY KEY ("id")'."\n".')';
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
    	
    	$this->assertEquals($this->_table->name, $schema->name, 'Test table name');
    	
    	$this->assertEquals(1, count($schema->indices));
    	$idIndex = $schema->indices[0];
        $this->assertEquals('true', $idIndex->notnull, 'Test $idIndex->notnull');
        $this->assertEquals('true', $idIndex->primary, 'Test $idIndex->primary');
        $this->assertEquals('true', $idIndex->autoincrement, 'Test $idIndex->auto_increment');
    	
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
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));

        $this->_backend->addCol($this->_table->name, $field);
        
        $this->setExpectedException('Setup_Backend_Exception_NotImplemented');
        
        $this->_backend->addCol($this->_table->name, $field, 1); //Cannot use 3rd parameter $_position in Oracle 
    }

    public function testStringToFieldStatement_001() 
    {
        $string ="
            <field>
                <name>id</name>
                <type>integer</type>
            </field>";
            
        $statement = $this->_fixFieldDeclarationString('"id" NUMBER(11,0)');    
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '1430'); //1060: Column "id" already exists - expecting Exception'
        $this->_backend->addCol($this->_table->name, $field);
        
    }

    public function testStringToFieldStatement_002() 
    {
        $string ="
            <field>
                <name>id2</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>";
            
        $statement = $this->_fixFieldDeclarationString('"id2" NUMBER(11,0) NOT NULL');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->setExpectedException('Setup_Backend_Exception_NotImplemented');
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_003() 
    {
        $string ="
                <field>
                    <name>test</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"test" VARCHAR2(25) NOT NULL');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));

        $this->_backend->addCol($this->_table->name, $field);
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('test', $newColumn->name);
        $this->assertEquals('25', $newColumn->length);
        $this->assertEquals('true', $newColumn->notnull);
        $this->assertEquals('text', $newColumn->type);
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
    }
    
    public function testStringToFieldStatement_004() 
    {
        $string ="
                 <field>
                    <name>test</name>
                    <type>enum</type>
                    <value>enabled</value>
                    <value>disabled</value>
                    <notnull>true</notnull>
                </field>";
   
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals(array('enabled', 'disabled'), $newColumn->value);
        $this->assertEquals('test', $newColumn->name);
        $this->assertEquals('true', $newColumn->notnull);       
        $this->assertEquals('enum', $newColumn->type);
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'test' => 'enabled'));
        $this->setExpectedException('Zend_Db_Statement_Exception', '2290'); //invalid enum value -> expect exception
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'test' => 'deleted'));
    }  
    
    public function testStringToFieldStatement_005() 
    {
        $string ="
                <field>
                    <name>order</name>
                    <type>integer</type>
                    <length>11</length>
                    <unsigned>true</unsigned>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`order` int(11)  unsigned  NOT NULL");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->_backend->addCol($this->_table->name, $field);
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('order', $newColumn->name);
        $this->assertEquals('true', $newColumn->notnull);       
        $this->assertEquals('integer', $newColumn->type);
        $this->assertFalse(isset($newColumn->unsigned)); //unsigned option is currently not supported by oracle adapter
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
    }
    
    public function testStringToFieldStatement_006() 
    {
        $string ="
                
                <field>
                    <name>last_login</name>
                    <type>datetime</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"last_login" VARCHAR2(25)');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('last_login', $newColumn->name);
        $this->assertEquals('text', $newColumn->type);
        $this->assertEquals('25', $newColumn->length);
    }
    
    public function testStringToFieldStatement_007() 
    {
        $string ="
                
                <field>
                    <name>email_sent</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"email_sent" NUMBER(1,0) DEFAULT 0');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('email_sent', $newColumn->name);
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('1', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
    }
    
    public function testStringToFieldStatement_008() 
    {
        $string ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <notnull>false</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"account_id" NUMBER(11,0)');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_009() 
    {
        $string ="
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"last_modified_time" VARCHAR2(25) NOT NULL ');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('last_modified_time', $newColumn->name);
        $this->assertEquals('text', $newColumn->type);
        $this->assertEquals('25', $newColumn->length);
    }
    
    public function testStringToFieldStatement_010() 
    {
        $string ="
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"is_deleted" NUMBER(1,0) DEFAULT 0 NOT NULL');
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('is_deleted', $newColumn->name);
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('1', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
    }    

    public function testStringToFieldStatement_011() 
    {
        $string ="
                <field>
                    <name>new_value</name>
                    <type>clob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"new_value" CLOB ');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_012() 
    {
        $string ="
                <field>
                    <name>created_by</name>
                    <type>integer</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"created_by" NUMBER(11,0)');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_013() 
    {
        $string ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <comment>comment</comment>
                </field>";
            
        
        $statement = $this->_fixFieldDeclarationString('"account_id" NUMBER(11,0)'); //COMMENTS are ignored in Oracle Adapter    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_014() 
    {
        $string ="
               <field>
                    <name>jpegphoto</name>
                    <type>blob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"jpegphoto" BLOB');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_015() 
    {
        $string ="
                <field>
                    <name>private</name>
                    <type>integer</type>
                    <default>0</default>
                    <length>4</length>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"private" NUMBER(4,0) DEFAULT 0');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_016() 
    {
        $string ="
                <field>
                    <name>created</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"created" VARCHAR2(25) NOT NULL');
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_018() 
    {
        $string ="
               <field>
                    <name>leadtype_translate</name>
                    <type>integer</type>
                    <length>4</length>
                    <default>1</default>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"leadtype_translate" NUMBER(4,0) DEFAULT 1');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_019() 
    {
        $string ="
               <field>
                    <name>bigint</name>
                    <type>integer</type>
                    <length>24</length>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString('"bigint" NUMBER(24,0)');    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field, $this->_table->name));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToForeignKeyStatement_001() 
    {
     
     $referencedTableName = 'oracle_foreign';
     $referencedTableXml = "
            <table>
                <name>$referencedTableName</name>
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
            </table>";
        $referencedTable = Setup_Backend_Schema_Table_Factory::factory('Xml', $referencedTableXml);
        $this->_tableNames[] = $referencedTableName;
        $this->_backend->createTable($referencedTable);

        $fieldString ="
            <field>
                <name>foreign_id</name>
                <type>integer</type>
            </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
     
        $string ="
                <index>
                    <field>
                        <name>foreign_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>$referencedTableName</table>
                        <field>id</field>
                    </reference>
                </index>";  
        
        $foreignKey = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);

        $this->_backend->addForeignKey($this->_table->name, $foreignKey);
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $referencedTableName, array('name' => 'test'));
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 1));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', 'ORA-02291'); //ORA-02291: foreign key constraint violation
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 999));
    }
    
    public function testLongForeignKeyName() 
    {
     
     $referencedTableName = 'oracle_foreign';
     $referencedTableXml = "
            <table>
                <name>$referencedTableName</name>
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
            </table>";
        $referencedTable = Setup_Backend_Schema_Table_Factory::factory('Xml', $referencedTableXml);
        $this->_tableNames[] = $referencedTableName;
        $this->_backend->createTable($referencedTable);

        $fieldString ="
            <field>
                <name>foreign_id</name>
                <type>integer</type>
            </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
     
        $string ="
                <index>
                    <name>" . str_pad('X', 31, 'x') . "</name>
                    <field>
                        <name>foreign_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>$referencedTableName</table>
                        <field>id</field>
                    </reference>
                </index>";

        $foreignKey = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);

        $this->_backend->addForeignKey($this->_table->name, $foreignKey);

    }
    
    public function testStringToIndexStatement_001() 
    {
        $string ="
                <index>
                    <primary>true</primary>
                    <unique>true</unique>
                    <field>
                        <name>id</name>
                    </field>
                </index>";
            
        $statement = $this->_fixIndexDeclarationString('CONSTRAINT "' . SQL_TABLE_PREFIX . 'pk_oracle_test" PRIMARY KEY ("id")');    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index, $this->_table->name));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', 'ORA-02260'); //ORA-02260: there can only be one primary key - expecting Exception
        $this->_backend->addIndex($this->_table->name, $index);
    }
    
    public function testStringToIndexStatement_002() 
    {
        $string ="
                <index>
                    <primary>true</primary>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>application_id</name>
                    </field>
                </index>";
            
        $statement = $this->_fixIndexDeclarationString('CONSTRAINT "' . SQL_TABLE_PREFIX . 'pk_oracle_test" PRIMARY KEY ("name","application_id")');    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index, $this->_table->name));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', 'ORA-00904'); //ORA-00904: field application_id does not exist
        $this->_backend->addIndex($this->_table->name, $index);
    } 
    
    public function testStringToIndexStatement_003() 
    {
     
        $fieldString ="
                <field>
                    <name>group_id</name>
                    <type>integer</type>
                </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
        
        $fieldString ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
     
     
        $string ="
                <index>
                    <name>group_id-account_id</name>
                    <unique>true</unique>
                    <field>
                        <name>group_id</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index> ";
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);

        $this->_backend->addIndex($this->_table->name, $index);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        $db->insert($tableName, array('name' => 'test1', 'group_id' => 1, 'account_id' => 1));
        $db->insert($tableName, array('name' => 'test2', 'group_id' => 1, 'account_id' => 2));
        $db->insert($tableName, array('name' => 'test3', 'group_id' => 2, 'account_id' => 1));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', 'ORA-00001'); //ORA-00001: unique constraint violation
        $db->insert($tableName, array('name' => 'test4', 'group_id' => 1, 'account_id' => 1));
    }
    
    public function testStringToIndexStatement_004() 
    {
        $fieldString ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
     
        $string ="
                <index>
                    <name>id-account_type-account_id</name>
                    <field>
                        <name>name</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>";
            
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        
        $indexesBefore = $this->_backend->getIndexesForTable($this->_table->name);
        $this->_backend->addIndex($this->_table->name, $index);
        $indexesAfter = $this->_backend->getIndexesForTable($this->_table->name);
        $this->assertEquals(count($indexesBefore) + 1, count($indexesAfter));
    }
    
    public function testCreateTableWithIndex()
    {
        $tableXml = '
            <table>
                <name>oracle_test_index</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>first_name</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>last_name</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <field>
                            <name>first_name</name>
                        </field>
                        <field>
                            <name>last_name</name>
                        </field>
                    </index>
                </declaration>
            </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXml);
        $this->_tableNames[] = $table->name;
        $this->_backend->createTable($table);
        
        $indexes = $this->_backend->getIndexesForTable($table->name);
        $this->assertEquals(1, count($indexes));
    }
    
//    public function testUnsignedNotImplemented()
//    {
//        $string ="
//                <field>
//                    <name>account_id</name>
//                    <type>integer</type>
//                    <unsigned>true</unsigned>
//                </field>";
//
//        
//        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
//        
//        $this->setExpectedException('Setup_Backend_Exception_NotImplemented', 'unsigned');
//        $this->_backend->addCol($this->_table->name, $field);
//    }

    
    public function testLongTableName() 
    {
        //Tests table without sequence
        $tableXml = '
        <table>
            <name>long_name_0123456789_0123456789</name>
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
        $this->setExpectedException('Zend_Db_Statement_Exception', '972'); //oracle identifiers cannot be longer than 30 characters 
        $this->_backend->createTable($table);
    }  
      
    public function testDatatypeTextReturnsPlainText() 
    {
        $string ="
                <field>
                    <name>test</name>
                    <type>text</type>
                </field>";
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        
//        $schema = $this->_backend->getExistingSchema($this->_table->name);
//        $newColumn = end($schema->fields);
//        $this->assertEquals('text', $newColumn->type);
//        $this->assertEquals('4000', $newColumn->length);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        $testValues = array(
            'some text',
            str_pad('test', 4001, 'x') 
        );

        foreach ($testValues as $index => $value) {
            $db->insert($tableName, array('name' => $index, 'test' => $value));
            $result = $db->fetchOne('SELECT "test" FROM "' . $tableName . '" WHERE "name"=:name', array('name' => $index));
            $this->assertEquals($value, $result);
        }
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
        return "  $return";
    }
    
    /**
     * setup proxy for Zend_Db_Adapter_Oralce because we want to test a protected method
     * 
     * @param String $_prefix
     * @return Zend_Db_Adapter_OralceProxy
     */
    protected function _getDbProxy($_prefix = 'npp')
    {
        $config = Tinebase_Core::getConfig();
        $dbConfig = $config->database;
        $dbProxy = $this->getProxy('Zend_Db_Adapter_Oracle', $dbConfig->toArray());
        $dbProxy->supportPositionalParameters(true);
        $dbProxy->setNamedParamPrefix($_prefix);
        
        return $dbProxy;
    }    
}        


                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_OracleTest::main();
}
