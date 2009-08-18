<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: SqlTest.php 1703 2008-04-03 18:16:32Z lkneschke $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_Backend_MysqlTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Setup_Backend_MysqlTest extends Setup_Backend_AbstractTest
{    

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

    public function testStringToFieldStatement_008() 
    {
        $string ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>false</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`account_id` int(11)  unsigned ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
        $newColumn = $this->_getLastField();
        $this->assertEquals('false', $newColumn->notnull);
        $this->assertEquals('true', $newColumn->unsigned);
        $this->assertEquals(0, $newColumn->default);
        
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
            
        $statement = $this->_fixFieldDeclarationString("`private` tinyint(4)  unsigned DEFAULT 0");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
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
            
        $statement = $this->_fixFieldDeclarationString("`leadtype_translate` tinyint(4)  unsigned DEFAULT 1");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
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
            
        $statement = $this->_fixFieldDeclarationString("`bigint` bigint(24)  unsigned ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
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
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '22003'); //22003: too many digits (maxim 4 + 2 precision)
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
        $this->assertEquals(null, $newColumn->length);
        $this->assertEquals(null, $newColumn->scale);
        $this->assertEquals('float', $newColumn->type);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;

        $testValues = array(1.2, -1.2, 999.999999);
        foreach ($testValues as $index => $value) {
            $db->insert($tableName, array('name' => 'test', 'geo_lattitude' => $value));
            $result = $db->fetchCol($db->select()->from($tableName, 'geo_lattitude'));
            $this->assertEquals($value, $result[$index], 'Testing value ' . $value);
        }
    }
    
    ##############################
    #    I     N     D     I    C     I     E    S 
    ##############################
    
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
            
        $statement = $this->_fixIndexDeclarationString("  PRIMARY KEY  (`id`)");    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '1068'); //1068: there can only be one primary key - expecting Exception
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
            
        $statement = $this->_fixIndexDeclarationString("  PRIMARY KEY  (`name`,`application_id`)");    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '1068'); //1068: there can only be one primary key - expecting Exception
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
            
        $statement = $this->_fixIndexDeclarationString(" UNIQUE KEY `group_id-account_id` (`group_id`,`account_id`) ");    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index));

        $this->_backend->addIndex($this->_table->name, $index);
        
        $db = Tinebase_Core::getDb();
        $tableName = SQL_TABLE_PREFIX . $this->_table->name;
        $db->insert($tableName, array('name' => 'test1', 'group_id' => 1, 'account_id' => 1));
        $db->insert($tableName, array('name' => 'test2', 'group_id' => 1, 'account_id' => 2));
        $db->insert($tableName, array('name' => 'test3', 'group_id' => 2, 'account_id' => 1));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '23000'); //23000: unique constraint violation
        $db->insert($tableName, array('name' => 'test4', 'group_id' => 1, 'account_id' => 1));
    }        
    
    public function testStringToIndexStatement_004() 
    {
        $string ="
                <index>
                    <name>id-account_type-account_id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>";
            
        $statement = $this->_fixIndexDeclarationString(" KEY `id-account_type-account_id` (`container_id`,`account_type`,`account_id`) ");    
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getIndexDeclarations($index));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '42000'); //42000: group_id and account_id fields missing - expecting Exception
        $this->_backend->addIndex($this->_table->name, $index);
    }    
    
    

    public function testStringToForeignKeyStatement_001() 
    {
     
     $referencedTableName = 'foreign_key_test';
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
                    <name>test_fk</name>
                    <field>
                        <name>foreign_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>$referencedTableName</table>
                        <field>id</field>
                    </reference>
                </index>";

        $statement = $this->_fixIndexDeclarationString("CONSTRAINT `" . SQL_TABLE_PREFIX . "test_fk` FOREIGN KEY (`foreign_id`) REFERENCES `" . SQL_TABLE_PREFIX . "foreign_key_test` (`id`)");    
        
        $foreignKey = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getForeignKeyDeclarations($foreignKey));

        $this->_backend->addForeignKey($this->_table->name, $foreignKey);
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $this->assertEquals(2, count($schema->indices));
        $index = $schema->indices[1];
        $this->assertEquals('true', $index->foreign);
        $this->assertEquals('true', $index->mul);
        $this->assertEquals('false', $index->primary);
        $this->assertFalse(empty($index->referencetable));
        $this->assertFalse(empty($index->referencefield));
        
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $referencedTableName, array('name' => 'test'));
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 1));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '23000'); //23000: foreign key constraint violation
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'foreign_id' => 999));
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
    	
    	$statement = 'SELECT * FROM `' . SQL_TABLE_PREFIX . $this->_table->name . '`;';
        $result = $this->_backend->execQuery($statement);
    	$this->assertEquals(1, count($result));
        $this->assertEquals($testValue, $result[0]['name']);
    }

}        
                
                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_MysqlTest::main();
}
