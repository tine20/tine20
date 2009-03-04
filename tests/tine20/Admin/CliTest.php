<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: JsonTest.php 6967 2009-02-23 16:30:42Z p.schuele@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Admin_CliTest::main');
}

/**
 * Test class for Tinebase_Admin
 */
class Admin_CliTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin Cli Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_cli = new Admin_Frontend_Cli();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    /**
     * test to import admin users
     *
     */
    public function testImportUsers()
    {
        // create definition / check if exists
        $definitionBackend = new Tinebase_ImportExportDefinition();
        $config = '<?xml version="1.0" encoding="UTF-8"?>
        <config>
            <headline>1</headline>
            <dryrun>1</dryrun>
            <mapping>
                <field>
                    <source>firstname</source>
                    <destination>accountFirstName</destination>
                </field>
                <field>
                    <source>lastname</source>
                    <destination>accountLastName</destination>
                </field>
                <field>
                    <source>loginname</source>
                    <destination>accountLoginName</destination>
                </field>
                <field>
                    <source>password</source>
                    <destination>password</destination>
                </field>
            </mapping>
        </config>';
        
        try {
            $definition = $definitionBackend->getByProperty('admin_user_import_csv');
            $definition->plugin_options = $config;
        } catch(Tinebase_Exception_NotFound $e) {
            $definition = $definitionBackend->create(new Tinebase_Model_ImportExportDefinition(array(
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(),
                'name'              => 'admin_user_import_csv',
                'type'              => 'import',
                'model'             => 'Tinebase_Model_FullUser',
                'plugin'            => 'Admin_Import_Csv',
                'plugin_options'    => $config
            ))); 
        }
        
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array(dirname(__FILE__) . '/files/test.csv'));
        
        ob_start();
        $this->_cli->importUser($opts);
        $out = ob_get_clean();
        
        // check output
        $this->assertEquals("Imported 3 records.\n", $out);
    }
}       
    
if (PHPUnit_MAIN_METHOD == 'Admin_CliTest::main') {
    Admin_CliTest::main();
}
