<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        // remove test config
        Tinebase_Config::getInstance()->deleteConfigForApplication(Tinebase_Model_Config::XLSEXPORTCONFIG, 'Crm');
    }
    
    /**
     * test xls export
     * 
     * @return void
     * 
     * @todo save and test xls file (with xls reader)
     * @todo check metadata
     */
    public function testExportXls()
    {
        $this->_setTestConfig();
        $this->_instance = new Crm_Export_Xls();
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $excelObj = $this->_instance->generate(new Crm_Model_LeadFilter($this->_getLeadFilter()));
        
        // output as csv
        $xlswriter = new PHPExcel_Writer_CSV($excelObj);
        $xlswriter->save('php://output');
        
        $csvFilename = 'test.csv';
        $xlswriter->save($csvFilename);
        $noteString = Tinebase_Translation::getTranslation('Tinebase')->_('created') . ' ' . Tinebase_Translation::getTranslation('Tinebase')->_('by');
        
        $this->assertTrue(file_exists($csvFilename));
        $export = file_get_contents($csvFilename);
        $this->assertEquals(1, preg_match("/PHPUnit/",                          $export), 'no name'); 
        $this->assertEquals(1, preg_match("/Description/",                      $export), 'no description');
        $this->assertEquals(1, preg_match('/Admin Account, Tine 2.0/',          $export), 'no creator');
        $this->assertEquals(1, preg_match('/' . $translate->_('open') . '/',    $export), 'no leadstate');
        $this->assertEquals(1, preg_match('/Kneschke/',                         $export), 'no partner');
        $this->assertEquals(1, preg_match('/' . $noteString . '/',              $export), 'no note');
        
        unlink($csvFilename);
    }

    /**
     * test xls export
     * 
     * @return void
     * 
     * @todo save and test xls file (with xls reader)
     */
    public function testExportXlsWithTemplate()
    {
        $this->_setTestConfig(TRUE);
        $this->_instance = new Crm_Export_Xls();
        $excelObj = $this->_instance->generate(new Crm_Model_LeadFilter($this->_getLeadFilter()));
        
        // output as csv
        $xlswriter = new PHPExcel_Writer_CSV($excelObj);
        $xlswriter->setSheetIndex(1);
        //$xlswriter->save('php://output');
        
        $csvFilename = 'test.csv';
        $xlswriter->save($csvFilename);
        
        $this->assertTrue(file_exists($csvFilename));
        $export = file_get_contents($csvFilename);
        $this->assertEquals(1, preg_match("/PHPUnit/", $export), 'no name'); 
        $this->assertEquals(1, preg_match("/Description/", $export), 'no description');
        
        unlink($csvFilename);
    }
    
    /**
     * set test config for xls export
     * 
     * @param boolean $template
     * @return void
     */
    public function _setTestConfig($template = FALSE)
    {
        $translate = Tinebase_Translation::getTranslation('Crm');
        $config = array('fields' => array(
                'lead_name' => array(
                    'header'    => $translate->_('Lead Name'),
                ),
                'description' => array(
                    'header'    => $translate->_('Description'),
                ),
                'turnover' => array(
                    'header'    => $translate->_('Turnover'),
                ),
                'probability' => array(
                    'header'    => $translate->_('Probability'),
                ),
                'start' => array(
                    'header'    => $translate->_('Date Start'),
                    'type'      => 'datetime', 
                ),
                'end' => array(
                    'header'    => $translate->_('Date End'),
                    'type'      => 'datetime', 
                ),
                'end_scheduled' => array(
                    'header'    => $translate->_('Date End Scheduled'),
                    'type'      => 'datetime', 
                ),
                'created_by' => array(
                    'header'    => $translate->_('Created By'),
                    'type'      => 'user', 
                ),
                'leadstate' => array(
                    'header'    => $translate->_('Leadstate'),
                    'type'      => 'config', 
                ),
                'leadsource' => array(
                    'header'    => $translate->_('Leadsource'),
                    'type'      => 'config', 
                ),
                'leadtype' => array(
                    'header'    => $translate->_('Leadtype'),
                    'type'      => 'config', 
                ),
                'PARTNER' => array(
                    'header'    => $translate->_('Partner'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'CUSTOMER' => array(
                    'header'    => $translate->_('Customer'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'RESPONSIBLE' => array(
                    'header'    => $translate->_('Responsible'),
                    'type'      => 'relation',
                    'field'     => 'n_fileas',
                ),
                'TASK' => array(
                    'header'    => $translate->_('Task'),
                    'type'      => 'relation',
                    'field'     => 'summary',
                ),
                'PRODUCT' => array(
                    'header'    => $translate->_('Product'),
                    'type'      => 'relation',
                    'field'     => 'name',
                ),
                'notes' => array(
                    'header'    => $translate->_('History'),
                    'type'      => 'notes',
                    'field'     => 'note',
                ), 
            )
        );
        
        if ($template) {
            $config['template'] = 'lead_test_template.xls';
        }
        
        Tinebase_Config::getInstance()->setConfigForApplication(
            Tinebase_Model_Config::XLSEXPORTCONFIG, 
            Zend_Json::encode($config), 
            'Crm'
        );
    }
}       

if (PHPUnit_MAIN_METHOD == 'Crm_Export_XlsTest::main') {
    Addressbook_ControllerTest::main();
}
