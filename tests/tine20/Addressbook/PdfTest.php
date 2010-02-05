<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
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
 * 
 * take care: the tests only work with the zend_pdf fonts ... don't use custom fonts
 */
class Addressbook_PdfTest extends PHPUnit_Framework_TestCase
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
        $this->objects['contact'] = NULL;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // delete contact afterwards
        Addressbook_Controller_Contact::getInstance()->delete($this->objects['contact']);
    }
    
    /**
     * try to create a pdf
     *
     */
    public function testContactPdf()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $contact = $this->_createContact();
        
		$pdf = new Addressbook_Export_Pdf();
		$pdf->generate($contact);
		$pdfOutput = $pdf->render();
		
		//$pdf->save("test.pdf");
		
		$this->assertEquals(1, preg_match("/^%PDF-1.4/", $pdfOutput), 'no pdf document'); 
		$this->assertEquals(1, preg_match("/Pickhuben 4/", $pdfOutput), 'street not found'); 
		
        // check name and company name
        $this->assertEquals(1, preg_match("/Metaways Infosystems GmbH/", $pdfOutput), 'name not found');    

        // check notes (removed that because system notes are no longer shown in pdf)
        /*
        $translatedNoteString = $translate->_('created') . ' ' . $translate->_('by');
        $this->assertEquals(1, preg_match("/$translatedNoteString/", $pdfOutput), 'note not found');
        */   
    }

    /**
     * test pdf locale settings (translation & date formatting)
     *
     * remark: be careful with this test, when another user timezone (something like America/Vancouver) is set, 
     *  the birthday could be one day earlier and the test fails
     * 
     * @todo fix the timezone problem
     */
    public function testContactPdfLocale()
    {
    	// set de_DE locale
    	Zend_Registry::set('locale', new Zend_Locale('de'));
    	
    	$contact = $this->_createContact();
    	
        $pdf = new Addressbook_Export_Pdf();
        $pdf->generate($contact);
        $pdfOutput = $pdf->render();
        
        //$pdf->save("test.pdf");
        
        $this->assertEquals(1, preg_match("/02.01.1975/", $pdfOutput), 'date format wrong or not found'); 
        $this->assertEquals(1, preg_match("/Private Kontaktdaten/", $pdfOutput), 'translation not found');
    }
    
    /**
     * create contact with note
     *
     */
    protected function _createContact()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $container = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Addressbook', 'PHPUNIT');
        } else {
            $container = $personalContainer[0];
        }
        
        $contact = new Addressbook_Model_Contact(array(
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
            'bday'                  => new Zend_Date ('1975-01-02 03:04:05', Tinebase_Record_Abstract::ISO8601LONG),
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->id,
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
        
        $this->objects['contact'] = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        return $this->objects['contact'];
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_ControllerTest::main') {
    Addressbook_ControllerTest::main();
}
