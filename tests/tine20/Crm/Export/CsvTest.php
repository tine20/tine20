<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $this->_instance = new Crm_Export_Csv(new Crm_Model_LeadFilter($this->_getLeadFilter()), Crm_Controller_Lead::getInstance());
        parent::setUp();
    }

    /**
     * test csv export
     * 
     * @return void
     */
    public function testExportCsv()
    {
        $csvFilename = $this->_instance->generate();
        
        $export = file_get_contents($csvFilename);
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $defaultContainerId = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Crm')->getId();
        $this->assertContains('"lead_name","leadstate_id","Leadstate","leadtype_id","Leadtype","leadsource_id","Leadsource","container_id","start"'
            . ',"description","end","turnover","probableTurnover","probability","end_scheduled","tags","notes","tags","CUSTOMER","PARTNER","RESPONSIBLE","TASK"', $export, 'headline wrong');
        $this->assertContains('"PHPUnit","1","' . $translate->_('open') . '","1","' . $translate->_('Customer') . '","1","' . $translate->_('Market') . '","' 
            . $defaultContainerId . '"', $export, 'data #1 wrong');
        $this->assertContains('"Kneschke, Lars","","phpunit: crm test task"', $export, 'relations wrong');
        
        $dateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat(NULL, NULL, NULL, 'date');
        $this->assertContains($dateString, $export, 'note date wrong');

        unlink($csvFilename);
    }
}       
