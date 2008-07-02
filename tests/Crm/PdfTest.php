<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  PDF
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Crm_PdfTest extends PHPUnit_Framework_TestCase
{
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Controller Tests');
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
       $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->objects['lead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => new Zend_Date( "2007-12-12" ),
            'description'   => 'Lead Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Zend_Date::now(),
        )); 

        $this->objects['leadWithLink'] = new Crm_Model_Lead(array(
            'id'            => 22,
            'lead_name'     => 'PHPUnit with contact',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => new Zend_Date( "2007-12-24" ),
            'description'   => 'Lead Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 50,
            'end_scheduled' => Zend_Date::now(),
        )); 
        
       $this->objects['linkedContact'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 20,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => $this->testContainer->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));             	

        $this->objects['linkedTask'] = new Tasks_Model_Task(array(
            'summary'               => 'task test',
            'container_id'          => $this->testContainer->id,
        ));
        
        try {
            $lead = Crm_Controller::getInstance()->createLead($this->objects['leadWithLink']);
        } catch ( Exception $e ) {
            // already there
        }
        try {
            $contact = Addressbook_Controller::getInstance()->addContact($this->objects['linkedContact']);
        } catch ( Exception $e ) {
            // already there
        }
        
        return;
        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // delete the db entries
        try { 
            Crm_Controller::getInstance()->deleteLead($this->objects['leadWithLink']);
        } catch ( Exception $e ) {
            // access denied ?
        }
        
        try { 
            Addressbook_Controller::getInstance()->deleteContact($this->objects['linkedContact']);
        } catch ( Exception $e ) {
            // access denied ?
        }
    	
    }
    
    /**
     * try to create a pdf
     *
     */
    public function testLeadPdf()
    {
    	
		$pdf = new Crm_Pdf();
		$pdfOutput = $pdf->getLeadPdf($this->objects['lead']);
		
		$this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput)); 
		$this->assertEquals(1, preg_match("/Lead Description/", $pdfOutput)); 
		$this->assertEquals(1, preg_match("/PHPUnit/", $pdfOutput));
				
    }

    /**
     * try to create a pdf with a linked contact
     *
     */
    public function testLeadPdfLinkedContact()
    {
    	// create lead + contact + link
        
        $lead = Crm_Controller::getInstance()->getLead($this->objects['leadWithLink']->getId());
        $lead->customer = array($this->objects['linkedContact']->id);
        $lead = Crm_Controller::getInstance()->updateLead($lead);
        
    	$pdf = new Crm_Pdf();
        $pdfOutput = $pdf->getLeadPdf($lead);
        
        //$pdf->save("test.pdf");
                
        $this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput), "no pdf generated"); 
        $this->assertEquals(1, preg_match("/Lars Kneschke/", $pdfOutput), "no contact data/fullname found");

        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();                
        $backend->purgeAllRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $this->objects['leadWithLink']->getId());        
    }

    /**
     * try to create a pdf with a linked task
     *
     */
    public function testLeadPdfLinkedTask()
    {
        // create lead + task + link
        $task = Tasks_Controller::getInstance()->createTask($this->objects['linkedTask']);

        $lead = Crm_Controller::getInstance()->getLead($this->objects['leadWithLink']->getId());
        $lead->customer = array();
        $lead->tasks = array($task->getId());
        $lead = Crm_Controller::getInstance()->updateLead($lead);
        
        $pdf = new Crm_Pdf();
        $pdfOutput = $pdf->getLeadPdf($lead);
        
        //$pdf->save("test.pdf");
                
        $this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput), "no pdf generated"); 
        $this->assertEquals(1, preg_match("/".$task->summary."/", $pdfOutput), "no summary found");
                
        // remove
        $lead->tasks = array();
        $lead = Crm_Controller::getInstance()->updateLead($lead);
        Tasks_Controller::getInstance()->deleteTask($task->getId());
        
        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();        
        $backend->purgeAllRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $this->objects['leadWithLink']->getId());
    }
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_ControllerTest::main') {
    Addressbook_ControllerTest::main();
}
