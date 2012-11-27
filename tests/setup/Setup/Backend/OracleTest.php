<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_Backend_OracleTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Setup_Backend_OracleTest extends Setup_Backend_AbstractTest
{

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

    public function testGetConstraintsForTable()
    {
        //test without optional parameters
        $constraints = $this->_backend->getConstraintsForTable($this->_table->name);
        $this->assertEquals(3, count($constraints)); //Primary Key, ID NOT NULL, NAME NTO NULL
        
        //test $_constraintType parameter
        $constraints = $this->_backend->getConstraintsForTable($this->_table->name, Setup_Backend_Oracle::CONSTRAINT_TYPE_PRIMARY);
        $this->assertEquals(1, count($constraints));
        $this->assertTrue(is_array($constraints[0]));
        $this->assertEquals(Setup_Backend_Oracle::CONSTRAINT_TYPE_PRIMARY, $constraints[0]['CONSTRAINT_TYPE']);
        
        $constraints = $this->_backend->getConstraintsForTable($this->_table->name, Setup_Backend_Oracle::CONSTRAINT_TYPE_FOREIGN);
        $this->assertEquals(0, count($constraints));
    }
    
    public function testAddColWithPositionParameter() 
    {
        $string ="
                <field>
                    <name>testAddCol</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->setExpectedException('Setup_Backend_Exception_NotImplemented');
        
        $this->_backend->addCol($this->_table->name, $field, 1); //Cannot use 3rd parameter $_position in Oracle 
    }

    public function testStringToFieldStatement_002() 
    {
        $string ="
            <field>
                <name>id2</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>";
            

        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->setExpectedException('Setup_Backend_Exception_NotImplemented');
        $this->_backend->addCol($this->_table->name, $field);
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
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        
        $newColumn = $this->_getLastField();
        $this->assertEquals('order', $newColumn->name);
        $this->assertEquals('true', $newColumn->notnull);
        $this->assertEquals('integer', $newColumn->type);
        $this->assertFalse(isset($newColumn->unsigned)); //unsigned option is currently not supported by oracle adapter
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
    }
    
    public function testStringToFieldStatement_009() 
    {
        $string ="
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('last_modified_time', $newColumn->name);
        $this->assertEquals('text', $newColumn->type);
        $this->assertEquals('25', $newColumn->length);
        $this->assertTrue($newColumn->equals($field));
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
