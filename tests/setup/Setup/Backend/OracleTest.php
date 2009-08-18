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
        $newColumn = $this->_getLastField();
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('4', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
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
        $newColumn = $this->_getLastField();
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('4', $newColumn->length);
        $this->assertEquals(1, $newColumn->default);
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
        $newColumn = $this->_getLastField();
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('24', $newColumn->length);
    }
    
    public function testStringToFieldStatement_020() 
    {
        $string ="
               <field>
                    <name>price</name>
                    <type>decimal</type>
                    <length>6</length>
                    <scale>2</scale>
                </field>";
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('price', $newColumn->name);
        $this->assertEquals('6', $newColumn->length);
        $this->assertEquals('2', $newColumn->scale);
        $this->assertEquals('decimal', $newColumn->type);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;

        $value = 9999.99;
        $db->insert($tableName, array('name' => 'test1', 'price' => $value));
        $result = $db->fetchCol($db->select()->from($tableName, 'price'));
        $this->assertEquals($value, $result[0]);
        
        $value = 1.999;
        $db->insert($tableName, array('name' => 'test2', 'price' => $value));
        $result = $db->fetchCol($db->select()->from($tableName, 'price'));
        $this->assertEquals(round($value), $result[1], 'Test if too many scale digits get rounded');
        
        $this->setExpectedException('Zend_Db_Statement_Exception', 'ORA-01438'); //ORA-01438: too many digits (maxim 4 + 2 precision)
        $value = 99999;
        $db->insert($tableName, array('name' => 'test3', 'price' => $value));
    }
    
    public function testStringToFieldStatement_021() 
    {
        $string ="
               <field>
                    <name>geo_lattitude</name>
                    <type>float</type>
                    <unsigned>false</unsigned>
                </field>";
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('geo_lattitude', $newColumn->name);
        $this->assertNull($newColumn->length);
        $this->assertNull($newColumn->scale);
        $this->assertEquals('float', $newColumn->type);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;

        $testValues = array(1.2, -1.2, 999.99999999);
        foreach ($testValues as $index => $value) {
            $db->insert($tableName, array('name' => 'test', 'geo_lattitude' => $value));
            $result = $db->fetchCol($db->select()->from($tableName, 'geo_lattitude'));
            $this->assertEquals($value, $result[$index], 'Testing value ' . $value);
        }
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

        $constraints = $this->_backend->getConstraintsForTable($this->_table->name, Setup_Backend_Oracle::CONSTRAINT_TYPE_FOREIGN);
        $this->assertEquals(0, count($constraints));
        
        $this->_backend->addForeignKey($this->_table->name, $foreignKey);

        //check number of foreign key constraints
        $constraints = $this->_backend->getConstraintsForTable($this->_table->name, Setup_Backend_Oracle::CONSTRAINT_TYPE_FOREIGN);
        $this->assertEquals(1, count($constraints));
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals(2, count($schema->indices));
        $index = $schema->indices[1];
        $this->assertEquals('true', $index->foreign);
        $this->assertNull($index->mul); //MUL is MySQL sepcific
        $this->assertEquals('false', $index->primary);
//        $this->assertFalse(empty($index->referencetable));
//        $this->assertFalse(empty($index->referencefield));
        
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
        
        $newColumn = $this->_getLastField();
        $this->assertEquals('text', $newColumn->type);
        
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
