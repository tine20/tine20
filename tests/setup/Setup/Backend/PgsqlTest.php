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

/**
 * Test class for Tinebase_User
 */
class Setup_Backend_PgsqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Setup_Backend_Pgsql
     * @access protected
     */
    protected $_backend;
    
    protected function setUp()
    {
        $this->_backend = Setup_Backend_Factory::factory();
    }
    
    public function testStringToFieldStatement()
    {
        $field = Setup_Backend_Schema_Field_Factory::factory('Xml', "
            <field>
                <name>account_id</name>
                <type>integer</type>
                <unsigned>true</unsigned>
                <notnull>false</notnull>
            </field>
        ");
        
        $this->assertEquals('  "account_id" integer', $this->_backend->getFieldDeclarations($field));
    }
}