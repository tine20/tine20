<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: XlsTest.php 10905 2009-10-12 13:39:57Z p.schuele@metaways.de $
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Export_XlsTest::main');
}

/**
 * Test class for Crm_Export_Xls
 */
class Crm_Export_XlsTest extends Crm_Export_AbstractTest
{
    /**
     * csv export class
     *
     * @var Crm_Export_Xls
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm_Export_XlsTest');
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
        $this->_instance = Tinebase_Export::factory(new Crm_Model_LeadFilter($this->_getLeadFilter()), 'xls');
        parent::setUp();
    }

    /**
     * test xls export
     * 
     * @return void
     * 
     * @todo save and test xls file (with xls reader)
     * @todo check metadata
     * @todo add note/partner checks again?
     */
    public function testExportXls()
    {
        $excelObj = $this->_instance->generate();
        
        // output as csv
        $xlswriter = new PHPExcel_Writer_CSV($excelObj);
        $xlswriter->setSheetIndex(1);
        //$xlswriter->save('php://output');
        
        $csvFilename = 'test.csv';
        $xlswriter->save($csvFilename);
        //$noteString = Tinebase_Translation::getTranslation('Tinebase')->_('created') . ' ' . Tinebase_Translation::getTranslation('Tinebase')->_('by');
        
        $this->assertTrue(file_exists($csvFilename));
        $export = file_get_contents($csvFilename);
        $this->assertEquals(1, preg_match("/PHPUnit/",                          $export), 'no name'); 
        $this->assertEquals(1, preg_match("/Description/",                      $export), 'no description');
        $this->assertEquals(1, preg_match('/Admin Account, Tine 2.0/',          $export), 'no creator');
        $this->assertEquals(1, preg_match('/open/',                             $export), 'no leadstate');
        //$this->assertEquals(1, preg_match('/Kneschke/',                         $export), 'no partner');
        //$this->assertEquals(1, preg_match('/' . $noteString . '/',              $export), 'no note');
        
        unlink($csvFilename);
    }
}       

if (PHPUnit_MAIN_METHOD == 'Crm_Export_XlsTest::main') {
    Addressbook_ControllerTest::main();
}
