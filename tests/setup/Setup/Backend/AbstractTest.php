<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @version     $Id: $
 */

/**
 * Abstract Test class for Tinebase_User
 */
abstract class Setup_Backend_AbstractTest extends BaseTest
{

    /**
     * @var    Setup_Backend_Abstract
     * @access protected
     */
    protected $_backend;
    
    /**
     * @var Setup_Backend_Schema_Table_Abstract
     */
    protected $_table;
    
    /**
     * Array holding table names that should be deleted with {@see tearDown}
     * 
     * @var array
     */
    protected $_tableNames = array();
    
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
    
    
    protected function setUp()
    {
        $this->_backend = Setup_Backend_Factory::factory();
        $this->_createTestTable();
    }

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
        try {
            $this->_backend->createTable($this->_table);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->_backend->dropTable($this->_table->name);
            $this->_backend->createTable($this->_table);
        }
    }
    
    protected function _getLastField($_tableName = null)
    {
        $tableName = empty($_tabelName) ? $this->_table->name : $_tabeName;
        $schema = $this->_backend->getExistingSchema($tableName);
        return end($schema->fields);
    }
    
    
    
    
    public function testTableName() 
    {
        $existingSchema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals($this->_table->name, $existingSchema->name);
    }
    
    public function testRenameTable()
    {
        $newTableName = 'renamed_test_table';
        $this->_backend->renameTable($this->_table->name, $newTableName);
        $this->_tableNames[] = $newTableName; //cleanup with tearDown
        $this->assertTrue($this->_backend->tableExists($newTableName));
    }
    
    public function testTableExists()
    {
        $this->assertTrue($this->_backend->tableExists($this->_table->name));
        $this->assertFalse($this->_backend->tableExists('non_existing_tablename'));
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
    }

    
    public function testQuestionMarkInFieldValue()
    {
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        $value = 'test ??  . ?=? ..';
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
        $this->assertEquals($value, $result[2]);
    }
    
    public function testRenameCol()
    {
        $existingSchema1 = $this->_backend->getExistingSchema($this->_table->name);
        $testCol = $existingSchema1->fields[1];
             
        $oldColumnName = $testCol->name;
        $newColumnName = "new_column_name";
        $testCol->name = $newColumnName; 
        $this->_backend->alterCol($this->_table->name, $testCol, $oldColumnName);
        $existingSchema2 = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals($existingSchema2->fields[1]->name, $newColumnName);     
    }
    
    public function testAlterCol()
    {
        $existingSchema1 = $this->_backend->getExistingSchema($this->_table->name);
        $testCol = $existingSchema1->fields[1];

        $testCol->type = 'integer';
        $testCol->length = 8;  
        $this->_backend->alterCol($this->_table->name, $testCol, $testCol->name);
        $existingSchema2 = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals($existingSchema2->fields[1]->type, 'integer');
        $this->assertEquals($existingSchema2->fields[1]->length, 8);
    }
    
    public function testDropCol()
    {
        $existingSchema1 = $this->_backend->getExistingSchema($this->_table->name);
        $testCol = $existingSchema1->fields[1];     
        
        $this->_backend->dropCol($this->_table->name, $testCol->name);
        $existingSchema2 = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals(count($existingSchema1->fields), count($existingSchema2->fields)+1);
    }
    
    public function testAddCol()
    {
        $existingSchema1 = $this->_backend->getExistingSchema($this->_table->name);
        $testCol = $existingSchema1->fields[1];
        $testCol->name = 'new_column_name';     
        
        $this->_backend->addCol($this->_table->name, $testCol);
        $existingSchema2 = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals(count($existingSchema1->fields), count($existingSchema2->fields)-1);    
    }
    
    public function testAddColWithPositionParameter() 
    {
        $existingSchema = $this->_backend->getExistingSchema($this->_table->name);
        $testCol = $existingSchema->fields[1];
        
        $testCol->name = 'new_column_name';     
        $this->_backend->addCol($this->_table->name, $testCol, 0);
        $existingSchema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals($testCol->name, $existingSchema->fields[0]->name);     
        
        $testCol->name = 'new_column_name2';     
        $this->_backend->addCol($this->_table->name, $testCol, 1);
        $existingSchema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals($testCol->name, $existingSchema->fields[2]->name);
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
            $result = $db->fetchOne($db->select()->from($tableName, array('test'))->where($db->quoteIdentifier('name') . '=?', $index));
            $this->assertEquals($value, $result);
        }
    }
    
    public function testStringToFieldStatement_001() 
    {
        $string ="
            <field>
                <name>id</name>
                <type>integer</type>
            </field>";
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->setExpectedException('Zend_Db_Statement_Exception'); //Column "id" already exists - expecting Exception'
        $this->_backend->addCol($this->_table->name, $field);
        
    }
    
    public function testExecQueryAndInsertStatement()
    {
        $testValue = 'test_exec_insert_statement';
        $recordsXml = "
            <defaultRecords>
               <record>
                    <table>
                        <name>{$this->_table->name}</name>
                    </table>
                    <field>
                        <name>id</name>
                        <value>1</value>
                    </field>
                    <field>
                        <name>name</name>
                        <value>{$testValue}</value>
                    </field>
                </record>
            </defaultRecords>";
        $records = simplexml_load_string($recordsXml);
        $this->_backend->execInsertStatement($records->record[0]);
        
        $db = Tinebase_Core::getDb();
        $statement = 'SELECT * FROM ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_table->name);
        $result = $this->_backend->execQuery($statement);
        $this->assertEquals(1, count($result));
        $this->assertEquals($testValue, $result[0]['name']);
    }
    
    public function testCheckTable()
    {
        $this->assertTrue($this->_backend->checkTable($this->_table));
        
        $string ="
            <field>
                <name>test</name>
                <type>integer</type>
            </field>";
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_table->addField($field);
        $this->assertFalse($this->_backend->checkTable($this->_table));
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
        $this->setExpectedException('Zend_Db_Statement_Exception'); //1075: There may only be one autoincrement column  - expecting Exception'
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
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        
        $newColumn = $this->_getLastField();
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
        $newColumn = $this->_getLastField();
        $this->assertEquals(array('enabled', 'disabled'), $newColumn->value);
        $this->assertEquals('test', $newColumn->name);
        $this->assertEquals('true', $newColumn->notnull);       
        $this->assertEquals('enum', $newColumn->type);
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'test' => 'enabled'));
        $this->setExpectedException('Zend_Db_Statement_Exception'); //invalid enum value -> expect exception
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
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('order', $newColumn->name);
        $this->assertEquals('11', $newColumn->length);
        $this->assertEquals('true', $newColumn->notnull);       
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('true', $newColumn->unsigned, 'Test unsigned');
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
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->_backend->addCol($this->_table->name, $field);
        
        $newColumn = $this->_getLastField();
        $this->assertTrue($newColumn->equals($field));
    }

    public function testStringToFieldStatement_007() 
    {
        $string ="
                
                <field>
                    <name>email_sent</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>";

        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('email_sent', $newColumn->name);
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('1', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
        $this->assertTrue($newColumn->equals($field));
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
        $this->assertEquals('datetime', $newColumn->type);
        $this->assertNull($newColumn->length);
        $this->assertTrue($newColumn->equals($field));
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
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('is_deleted', $newColumn->name);
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('1', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
        $this->assertTrue($newColumn->equals($field));
    }
    
    public function testStringToFieldStatement_011() 
    {
        $string ="
                <field>
                    <name>new_value</name>
                    <type>clob</type>
                </field>";
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals(null, $newColumn->default);
    }
    
    public function testStringToFieldStatement_013() 
    {
        $string ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <comment>comment</comment>
                </field>";
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('comment', $newColumn->comment);      
        $this->assertTrue($newColumn->equals($field)); 
    }
    
    
    public function testStringToFieldStatement_014() 
    {
        $string ="
               <field>
                    <name>jpegphoto</name>
                    <type>blob</type>
                </field>";
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('blob', $newColumn->type);
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
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('4', $newColumn->length);
        $this->assertEquals(0, $newColumn->default);
        $this->assertTrue($newColumn->equals($field));
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        $db->insert($tableName, array('name' => 'test'));
        $result = $db->fetchOne($db->select()->from($tableName, 'private')->where($db->quoteIdentifier('name') . '=?', array('test')));
        $this->assertEquals(0, $result);
    }
    
    public function testStringToFieldStatement_019() 
    {
        $string ="
               <field>
                    <name>bigint</name>
                    <type>integer</type>
                    <length>24</length>
                </field>";
            
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);

        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('integer', $newColumn->type);
        $this->assertEquals('24', $newColumn->length);
        $this->assertTrue($newColumn->equals($field));
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
        $this->assertTrue($newColumn->equals($field));
        
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
        
        $this->setExpectedException('Zend_Db_Statement_Exception'); //too many digits (maxim 4 + 2 precision)
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
        $this->assertTrue($newColumn->equals($field));
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;

        $testValues = array(1.2, -1.2, 999.999999);
        foreach ($testValues as $index => $value) {
            $db->insert($tableName, array('name' => 'test', 'geo_lattitude' => $value));
            $result = $db->fetchCol($db->select()->from($tableName, 'geo_lattitude'));
            $this->assertEquals($value, $result[$index], 'Testing value ' . $value);
        }
    }
    
    public function testAddAndDropForeignKey() 
    {
     
     $referencedTableName = 'phpunit_foreign';
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
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals(2, count($schema->indices));
        $index = $schema->indices[1];
        $this->assertEquals('true', $index->foreign);
        $this->assertEquals('false', $index->primary);
//        $this->assertFalse(empty($index->referencetable));
//        $this->assertFalse(empty($index->referencefield));
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $referencedTableName, array('name' => 'test'));
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 1));
        
        try {
          $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 999));
          $this->fail('Expected Zend_Db_Statement_Exception not thrown');
        } catch (Zend_Db_Statement_Exception $e) {
          //we expected this exception, everything is alright
        }

        $this->_backend->dropForeignKey($this->_table->name, $index->name);
        
        //now this should work without throwing an Exception
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 999));
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

        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);

        $this->setExpectedException('Zend_Db_Statement_Exception'); //ORA-02260: there can only be one primary key - expecting Exception
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
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        
        $this->setExpectedException('Zend_Db_Statement_Exception'); //field application_id does not exist
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
        
        $this->setExpectedException('Zend_Db_Statement_Exception'); //unique constraint violation
        $db->insert($tableName, array('name' => 'test4', 'group_id' => 1, 'account_id' => 1));
    }
    
}