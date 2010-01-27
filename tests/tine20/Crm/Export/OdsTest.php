<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Export_OdsTest::main');
}

/**
 * Test class for Crm_Export_Ods
 */
class Crm_Export_OdsTest extends Crm_Export_AbstractTest
{
    /**
     * csv export class
     *
     * @var Crm_Export_Ods
     */
    protected $_instance;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm_Export_OdsTest');
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
        $this->_instance = Tinebase_Export::factory(new Crm_Model_LeadFilter($this->_getLeadFilter()), 'ods');
        parent::setUp();
    }

    /**
     * test ods export
     * 
     * @return void
     * 
     * @todo add assertions for relations / other fields
     */
    public function testExportOds()
    {
        //$translate = Tinebase_Translation::getTranslation('Crm');   
        $odsFilename = $this->_instance->generate();
        
        $this->assertTrue(file_exists($odsFilename));
        
        $xmlBody = $this->_instance->getDocument()->asXML();    
        //echo  $xmlBody;
        $this->assertEquals(1, preg_match("/PHPUnit/",      $xmlBody), 'no name'); 
        $this->assertEquals(1, preg_match("/Description/",  $xmlBody), 'no description');
        $this->assertEquals(1, preg_match('/open/',         $xmlBody), 'no leadstate');
        
        unlink($odsFilename);
    }
}       

if (PHPUnit_MAIN_METHOD == 'Crm_Export_OdsTest::main') {
    Addressbook_ControllerTest::main();
}
