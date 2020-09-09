<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

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
     * export file
     * 
     * @var string
     */
    protected $_filename;
    
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
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        unlink($this->_filename);
        parent::tearDown();
    }
    
    /**
     * test csv export
     * 
     * @return void
     * 
     * @see 0007242: add customer address fields to lead csv export
     */
    public function testExportCsv()
    {
        $this->_filename = $this->_instance->generate();
        
        $export = file_get_contents($this->_filename);
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $defaultContainerId = Tinebase_Container::getInstance()->getDefaultContainer(Crm_Model_Lead::class)->getId();
        $this->assertContains('"lead_name","leadstate_id","Leadstate","leadtype_id","Leadtype","leadsource_id","Leadsource","container_id","start"'
            . ',"description","end","turnover","probableTurnover","probability","end_scheduled","resubmission_date","tags","attachments","notes","seq","mute","tags",'
            . '"CUSTOMER-org_name","CUSTOMER-n_family","CUSTOMER-n_given","CUSTOMER-adr_one_street","CUSTOMER-adr_one_postalcode","CUSTOMER-adr_one_locality",'
            . '"CUSTOMER-adr_one_countryname","CUSTOMER-tel_work","CUSTOMER-tel_cell","CUSTOMER-email",'
            . '"PARTNER-org_name","PARTNER-n_family","PARTNER-n_given","PARTNER-adr_one_street","PARTNER-adr_one_postalcode","PARTNER-adr_one_locality",'
            . '"PARTNER-adr_one_countryname","PARTNER-tel_work","PARTNER-tel_cell","PARTNER-email",'
            . '"RESPONSIBLE-org_name","RESPONSIBLE-n_family","RESPONSIBLE-n_given","RESPONSIBLE-adr_one_street","RESPONSIBLE-adr_one_postalcode","RESPONSIBLE-adr_one_locality",'
            . '"RESPONSIBLE-adr_one_countryname","RESPONSIBLE-tel_work","RESPONSIBLE-tel_cell","RESPONSIBLE-email",'
            . '"TASK-summary","PRODUCT-name"', $export, 'headline wrong: ' . substr($export, 0, 360));
        $this->assertContains('"PHPUnit","1","' . $translate->_('open') . '","1","' . $translate->_('Customer') . '","1","' . $translate->_('Market') . '","' 
            . $defaultContainerId . '"', $export, 'data #1 wrong');
        $this->assertContains('"Metaways Infosystems GmbH","Kneschke","Lars","Pickhuben 4","24xxx","Hamburg","DE","+49TELWORK","+49TELCELL","unittests@tine20.org","","","","","","","","","","","","","","","","","","","","","phpunit: crm test task",""', $export, 'relations wrong');
        
        $dateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat(NULL, NULL, NULL, 'date');
        $this->assertContains($dateString, $export, 'note date wrong');
    }
    
    /**
     * test sorted csv export
     * 
     * @return void
     * 
     * @see 0010790: use current grid sort in exports
     */
    public function testSortedExportCsv()
    {
        $options = array(
            'sortInfo' => array('field' => 'leadstate_id')
        );
        $this->_instance = new Crm_Export_Csv(new Crm_Model_LeadFilter($this->_getLeadFilter()), Crm_Controller_Lead::getInstance(), $options);
        
        $this->_filename = $this->_instance->generate();
        
        $export = file_get_contents($this->_filename);
        $this->assertContains('"Metaways Infosystems GmbH"', $export);
    }
}
