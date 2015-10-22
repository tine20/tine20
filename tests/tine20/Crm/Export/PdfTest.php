<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Crm_Export_PdfTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var Tinebase_Model_Container
     */
    protected $_testContainer = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_testContainer = $this->_getPersonalContainer('Crm');

        $this->objects['lead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => $this->_testContainer->id,
            'start'         => new Tinebase_DateTime( "2007-12-12" ),
            'description'   => 'Lead Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Tinebase_DateTime::now(),
        ));

        $this->objects['leadWithLink'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit with contact',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => $this->_testContainer->id,
            'start'         => new Tinebase_DateTime( "2007-12-24" ),
            'description'   => 'Lead Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 50,
            'end_scheduled' => Tinebase_DateTime::now(),
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
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
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
        ));
        
        Crm_Controller_Lead::getInstance()->create($this->objects['leadWithLink']);
        $this->objects['linkedContact'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['linkedContact'], FALSE);
    }

    /**
     * try to create a pdf
     *
     */
    public function testLeadPdf()
    {
        $pdf = new Crm_Export_Pdf();
        $pdf->generate($this->objects['lead']);
        $pdfOutput = $pdf->render();
        
        $this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput));
        $this->assertEquals(1, preg_match("/Lead Description/", $pdfOutput));
        $this->assertEquals(1, preg_match("/PHPUnit/", $pdfOutput));
    }

    /**
     * try to create a pdf with a linked contact
     */
    public function testLeadPdfLinkedContact()
    {
        // create lead + contact + link    
        $lead = Crm_Controller_Lead::getInstance()->get($this->objects['leadWithLink']->getId());
        $lead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'own_id'                 => $lead->getId(),
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Addressbook_Backend_Factory::SQL,
            'related_id'             => $this->objects['linkedContact']->id,
            'type'                   => 'RESPONSIBLE',
        ));
        $lead = Crm_Controller_Lead::getInstance()->update($lead);
        
        $pdf = new Crm_Export_Pdf();
        $pdf->generate($lead);
        $pdfOutput = $pdf->render();
        
        $this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput), "no pdf generated");
        $this->assertEquals(1, preg_match("/Lars Kneschke/", $pdfOutput), "no contact data/fullname found");

        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();
        $backend->purgeAllRelations('Crm_Model_Lead', 'Sql', $this->objects['leadWithLink']->getId());
    }

    /**
     * try to create a pdf with a linked task
     */
    public function testLeadPdfLinkedTask()
    {
        // create lead + task + link
        $task = Tasks_Controller_Task::getInstance()->create($this->objects['linkedTask']);

        $lead = Crm_Controller_Lead::getInstance()->get($this->objects['leadWithLink']->getId());
        $lead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'own_id'                 => $lead->getId(),
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => $task->getId(),
            'type'                   => 'TASK'
        ));
        $lead = Crm_Controller_Lead::getInstance()->update($lead);
        
        $pdf = new Crm_Export_Pdf();
        $pdf->generate($lead);
        $pdfOutput = $pdf->render();
        
        //$pdf->save("test.pdf");
        
        $this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput), "no pdf generated");
        $this->assertEquals(1, preg_match("/".$task->summary."/", $pdfOutput), "no summary found");
        
        // remove
        Tasks_Controller_Task::getInstance()->delete($task->getId());
        
        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();
        $backend->purgeAllRelations('Crm_Model_Lead', 'Sql', $this->objects['leadWithLink']->getId());
    }
}
