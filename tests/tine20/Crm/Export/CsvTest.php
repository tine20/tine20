<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: PdfTest.php 10879 2009-10-11 19:21:50Z p.schuele@metaways.de $
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Export_CsvTest::main');
}

/**
 * Test class for Crm_Export_Csv
 */
class Crm_Export_CsvTest extends Crm_Export_AbstractTest
{
    /**
     * csv export class
     *
     * @var Crm_Export_Csv
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm_Export_CsvTest');
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
        $this->_instance = new Crm_Export_Csv();
        parent::setUp();
    }

    /**
     * test csv export
     * 
     * @return void
     */
    public function testExportCsv()
    {
        $csvFilename = $this->_instance->generate(new Crm_Model_LeadFilter($this->_getLeadFilter()));
        
        $export = file_get_contents($csvFilename);
        //echo $export;
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $defaultContainerId = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Crm')->getId();
        $this->assertEquals('"lead_name","leadstate_id","Leadstate","leadtype_id","Leadtype","leadsource_id","Leadsource","container_id",'
            . '"description","turnover","probability","start","end","end_scheduled","CUSTOMER","PARTNER","RESPONSIBLE","TASK"
"PHPUnit","1","' . $translate->_('open') . '","1","' . $translate->_('Customer') . '","1","' . $translate->_('Market') . '","' .$defaultContainerId . '","Description","200000","70","' . $this->_objects['lead']['start'] 
            . '","","","","Kneschke, Lars
","","phpunit: crm test task
"
', $export);
        unlink($csvFilename);
    }
}       

if (PHPUnit_MAIN_METHOD == 'Crm_Export_CsvTest::main') {
    Addressbook_ControllerTest::main();
}
