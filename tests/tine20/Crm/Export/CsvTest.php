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
class Crm_Export_CsvTest extends Crm_AbstractTest
{
    /**
     * json frontend
     *
     * @var Crm_Frontend_Json
     */
    protected $_json;
    
    /**
     * csv export class
     *
     * @var Crm_Export_Csv
     */
    protected $_instance;
    
    /**
     * @var array test objects
     */
    protected $_objects = array();

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
        $this->_json = new Crm_Frontend_Json();
        
        $contact = $this->_getContact();
        $task = $this->_getTask();
        $lead = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',    'related_record' => $task->toArray()),
            array('type'  => 'PARTNER', 'related_record' => $contact->toArray()),
        );
        
        $this->_objects['lead'] = $this->_json->saveLead(Zend_Json::encode($leadData));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_json->deleteLeads($this->_objects['lead']['id']);
        Addressbook_Controller_Contact::getInstance()->delete($this->_objects['lead']['relations'][0]['related_id']);        
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
        $this->assertEquals('"lead_name","leadstate_id","leadtype_id","leadsource_id","container_id","description","turnover","probability","start","end","end_scheduled","CUSTOMER","PARTNER","RESPONSIBLE","TASK"
"PHPUnit","1","1","1","31","Description","200000","70","' . $this->_objects['lead']['start'] . '","","","","Kneschke, Lars
","","phpunit: crm test task
"
', $export);
        unlink($csvFilename);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Crm_Export_CsvTest::main') {
    Addressbook_ControllerTest::main();
}
