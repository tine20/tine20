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

    public function testStringToMysqlFieldStatement_001() 
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
    }
    
    public function testStringToMysqlFieldStatement_002() 
    {
        $string ="
            <field>
                <name>id</name>
                <type>integer</type>
                <autoincrement>true</autoincrement>
            </field>";
            
        $statement = $this->_fixFieldDeclarationString("`id` int(11) unsigned NOT NULL auto_increment");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }
    
    public function testStringToMysqlFieldStatement_003() 
    {
        $string ="
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>25</length>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`name` varchar(25) NOT NULL");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }
    
    public function testStringToMysqlFieldStatement_004() 
    {
        $string ="
                 <field>
                    <name>status</name>
                    <type>enum</type>
                    <value>enabled</value>
                    <value>disabled</value>
                    <notnull>true</notnull>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`status` enum('enabled','disabled') NOT NULL");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }            
    
    public function testStringToMysqlFieldStatement_005() 
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
    }        
    
    public function testStringToMysqlFieldStatement_006() 
    {
        $string ="
                
                <field>
                    <name>last_login</name>
                    <type>datetime</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`last_login` datetime ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }    
    
    public function testStringToMysqlFieldStatement_007() 
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
    }    
    
    public function testStringToMysqlFieldStatement_008() 
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
    }    
    
    public function testStringToMysqlFieldStatement_009() 
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
    }    
    
    public function testStringToMysqlFieldStatement_010() 
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
    }    
    
    public function testStringToMysqlFieldStatement_011() 
    {
        $string ="
                <field>
                    <name>new_value</name>
                    <type>clob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`new_value` text ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }        
    
    public function testStringToMysqlFieldStatement_012() 
    {
        $string ="
                <field>
                    <name>created_by</name>
                    <type>integer</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`created_by` int(11)  unsigned ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }    
    
    public function testStringToMysqlFieldStatement_013() 
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
    }
    
    public function testStringToMysqlFieldStatement_014() 
    {
        $string ="
               <field>
                    <name>jpegphoto</name>
                    <type>blob</type>
                </field>";
            
        $statement = $this->_fixFieldDeclarationString("`jpegphoto` longblob ");    
        
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getFieldDeclarations($field));
    }    
    
    public function testStringToMysqlFieldStatement_015() 
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
    }
    
    public function testStringToMysqlFieldStatement_016() 
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
    }        

    public function testStringToMysqlFieldStatement_018() 
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
    }    
    
    public function testStringToMysqlFieldStatement_019() 
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
    }        
    
    
    ##############################
    #    I     N     D     I    C     I     E    S 
    ##############################
    
    public function testStringToMysqlIndexStatement_001() 
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
    }        
    
    public function testStringToMysqlIndexStatement_002() 
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
    }        

    public function testStringToMysqlIndexStatement_003() 
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
    }        
    
    public function testStringToMysqlIndexStatement_004() 
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
    }    
    
    
    #####################################
    
    public function testStringToMysqlForeignKeyStatement_001() 
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
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getForeignKeyDeclarations($index));
    }        
    
    public function testStringToMysqlForeignKeyStatement_002() 
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
        
        $index = Setup_Backend_Schema_Index_Factory::factory('Xml', $string);
        $this->assertEquals($statement, $this->_backend->getForeignKeyDeclarations($index));
    }        
        
}        
                
                 
                
                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_MysqlTest::main();
}
