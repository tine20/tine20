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
class Setup_Backend_MysqlTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup Backend Mysql Tests');
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
        $this->_backend = Setup_Backend_Factory::factory('Mysql');
        
        $tableXml = '
	        <table>
	            <name>phpunit_mysql_test_table</name>
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

        $this->_table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXml);
        $this->_tableNames[] = $this->_table->name;
        
        $this->_backend->createTable($this->_table);
        
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

    public function testStringToFieldStatement_001() 
    {
        $string ="
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
                <unsigned>true</unsigned>
            </field>";
            
        $statement = $this->_fixFieldDeclarationString("`id` int(11) unsigned NOT NULL auto_increment");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '1060'); //1060: Column "id" already exists - expecting Exception'
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
            
        $statement = $this->_fixFieldDeclarationString("`id2` int(11) unsigned NOT NULL auto_increment");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        $this->setExpectedException('Zend_Db_Statement_Exception', '1075'); //1075: There may only be one autoincrement column  - expecting Exception'
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
            
        $statement = $this->_fixFieldDeclarationString("`test` varchar(25) NOT NULL");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));

        $this->_backend->addCol($this->_table->name, $field);
        
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('test', $newColumn->name);
        $this->assertEquals('25', $newColumn->length);
        $this->assertEquals('true', $newColumn->notnull);
        $this->assertEquals('text', $newColumn->type);
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

        $this->assertEquals('test', $newColumn->name);
        $this->assertEquals('true', $newColumn->notnull);       
        $this->assertEquals('enum', $newColumn->type);
        $this->assertNotEquals('true', $newColumn->primary);
        $this->assertNotEquals('true', $newColumn->unique);
        
        $db = Tinebase_Core::getDb();
        $db->insert(SQL_TABLE_PREFIX . $this->_table->name, array('name' => 'test', 'test' => 'enabled'));
        $this->setExpectedException('Zend_Db_Statement_Exception', '1265'); //invalid enum value -> expect exception
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
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
        $schema = $this->_backend->getExistingSchema($this->_table->name);
        $newColumn = end($schema->fields);
        $this->assertEquals('order', $newColumn->name);
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
            
        $statement = $this->_fixFieldDeclarationString("`last_login` datetime ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
    }    
    
    public function testStringToFieldStatement_007() 
    {
        $string ="
                
                <field>
                    <name>email_sent</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`email_sent` tinyint(4) unsigned default 0");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
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
    }    
    
    public function testStringToFieldStatement_009() 
    {
        $string ="
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`last_modified_time` datetime  NOT NULL ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
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
            
        $statement = $this->_fixFieldDeclarationString("`is_deleted` tinyint(4) unsigned NOT NULL default 0");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
    }    
    
    public function testStringToFieldStatement_011() 
    {
        $string ="
                <field>
                    <name>new_value</name>
                    <type>clob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`new_value` text ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
    }        
    
    public function testStringToFieldStatement_012() 
    {
        $string ="
                <field>
                    <name>created_by</name>
                    <type>integer</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`created_by` int(11)  unsigned ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
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
            
        $statement = $this->_fixFieldDeclarationString("`account_id` int(11)  unsigned COMMENT 'comment'");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
        $this->_backend->addCol($this->_table->name, $field);
    }
    
    public function testStringToFieldStatement_014() 
    {
        $string ="
               <field>
                    <name>jpegphoto</name>
                    <type>blob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`jpegphoto` longblob ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
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
            
        $statement = $this->_fixFieldDeclarationString("`private` tinyint(4)  unsigned default 0");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
        
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
            
        $statement = $this->_fixFieldDeclarationString("`created` datetime  NOT NULL ");
        
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
            
        $statement = $this->_fixFieldDeclarationString("`leadtype_translate` tinyint(4)  unsigned default 1");    
        
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
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '42000'); //42000: group_id and account_id fields missing - expecting Exception
        $this->_backend->addIndex($this->_table->name, $index);
        
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

        $this->_backend->addIndex($this->_table->name, $index);
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
    
    
    #####################################
    
    public function testStringToForeignKeyStatement_001() 
    {
        $string ="
                <index>
                    <name>container_id</name>
                    <field>
                        <name>container_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>container</table>
                        <field>id</field>
                    </reference>
                </index>";
            
        $statement = $this->_fixIndexDeclarationString("CONSTRAINT `" . SQL_TABLE_PREFIX . "container_id` FOREIGN KEY (`container_id`) REFERENCES `" . SQL_TABLE_PREFIX . "container` (`id`) ");    
        
        $foreignKey = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getForeignKeyDeclarations($foreignKey));
        
        $this->setExpectedException('Zend_Db_Statement_Exception', '42000'); //42000: container_id field missing - expecting Exception
        $this->_backend->addForeignKey($this->_table->name, $foreignKey);
        
        
        $fieldString ="
            <field>
                <name>container_id</name>
                <type>integer</type>
            </field>";
          
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $fieldString);
        $this->_backend->addCol($this->_table->name, $field);
        $this->_backend->addForeignKey($this->_table->name, $foreignKey);
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
    
    public function testTableExists()
    {
        $this->assertTrue($this->_backend->tableExists($this->_table->name));
        $this->assertFalse($this->_backend->tableExists('non_existing_tablename'));
    }
    
    public function testRenameTable()
    {
    	$newTableName = 'renamed_phpunit_mysql_test_table';
    	$this->_backend->renameTable($this->_table->name, $newTableName);
    	$this->_tableNames[] = $newTableName; //cleanup with tearDown
    	$this->assertTrue($this->_backend->tableExists($newTableName));
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
