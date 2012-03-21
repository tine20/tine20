<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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

}        
                
                
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_MysqlTest::main') {
    Setup_Backend_MysqlTest::main();
}
