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
 * Test class for Tinebase_Account
 */
class Setup_Backend_MysqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Setup_Backend_Mysql
     * @access protected
     */
	 
	protected $_backend;
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
		$this->_backend = Setup_Backend_Factory::factory(Setup_Backend_Factory::SQL);
		
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
	
    }

	public function testStringToMysqlStatement_001() 
	{
		$string ="
			<field>
				<name>id</name>
				<type>integer</type>
				<autoincrement>true</autoincrement>
				<unsigned>true</unsigned>
			</field>";
			
		$statement = "`id` int(11)  unsigned  auto_increment";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_002() 
	{
		$string ="
			<field>
				<name>id</name>
				<autoincrement>true</autoincrement>
			</field>";
			
		$statement = "`id` int(11)  unsigned  auto_increment";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_003() 
	{
		$string ="
				<field>
                    <name>name</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
			
		$statement = "`name` varchar(25)  NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_004() 
	{
		$string ="
				 <field>
                    <name>status</name>
                    <type>enum</type>
                    <value>enabled</value>
                    <value>disabled</value>
                    <notnull>true</notnull>
                </field>";
			
		$statement = "`status` enum('enabled','disabled')  NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}			
	
	public function testStringToMysqlStatement_005() 
	{
		$string ="
				<field>
                    <name>order</name>
                    <type>integer</type>
                    <length>11</length>
                    <unsigned>true</unsigned>
                    <notnull>true</notnull>
                </field>";
			
		$statement = "`order` int(11)  unsigned  NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}		
	
	public function testStringToMysqlStatement_006() 
	{
		$string ="
				
                <field>
                    <name>last_login</name>
                    <type>datetime</type>
                </field>";
			
		$statement = "`last_login` datetime ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_007() 
	{
		$string ="
				
                <field>
                    <name>email_sent</name>
                    <type>boolean</type>
                    <default>0</default>
                </field>";
			
		$statement = "`email_sent` tinyint default '1'";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_008() 
	{
		$string ="
				<field>
                    <name>account_id</name>
                    <type>integer</type>
                    <unsigned>true</unsigned>
                    <notnull>false</notnull>
                </field>";
			
		$statement = "`account_id` int(11)  unsigned ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_009() 
	{
		$string ="
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
			
		$statement = "`last_modified_time` datetime  NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_010() 
	{
		$string ="
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <notnull>true</notnull>
                    <default>false</default>
                </field>";
			
		$statement = "`is_deleted` tinyint default '0' NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_011() 
	{
		$string ="
				<field>
                    <name>new_value</name>
                    <type>clob</type>
                </field>";
			
		$statement = "`new_value` text ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}		
	
	public function testStringToMysqlStatement_012() 
	{
		$string ="
                <field>
                    <name>created_by</name>
                    <type>integer</type>
                </field>";
			
		$statement = "`created_by` int(11) ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_013() 
	{
		$string ="
                <field>
                    <name>account_id</name>
                    <type>integer</type>
                    <comment>comment</comment>
                </field>";
			
		$statement = "`account_id` int(11) COMMENT 'comment'";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_014() 
	{
		$string ="
               <field>
                    <name>jpegphoto</name>
                    <type>blob</type>
                </field>";
			
		$statement = "`jpegphoto` longblob ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
	
	public function testStringToMysqlStatement_015() 
	{
		$string ="
                <field>
                    <name>private</name>
                    <type>integer</type>
                    <default>0</default>
                    <length>4</length>
                </field>";
			
		$statement = "`private` tinyint(1) default '0'";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_016() 
	{
		$string ="
	            <field>
                    <name>created</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                </field>";
			
		$statement = "`created` datetime  NOT NULL ";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}		
	
	public function testStringToMysqlStatement_017() 
	{
		$string ="
               <field>
                    <name>price</name>
                    <type>decimal</type>
                    <value>12,2</value>
                    <default>0</default>
                </field>";
			
		$statement = "`price` decimal (12,2) default '0'";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}
	
	public function testStringToMysqlStatement_018() 
	{
		$string ="
               <field>
                    <name>leadtype_translate</name>
                    <type>integer</type>
                    <value>4</value>
                    <default>1</default>
                </field>";
			
		$statement = "`leadtype_translate` int(11) default '1'";	
		
		$field = Setup_Backend_Schema_Field_Factory::factory('String', $string);
		$this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
	}	
}		
	 

			
				 
				 
				
				
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_MysqlTest::main();
}
