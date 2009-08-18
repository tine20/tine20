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
    
 
}