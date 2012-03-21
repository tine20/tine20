<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Convert_Contact_VCard_GenericTest::main');
}

/**
 * Test class for Addressbook_Convert_Contact_VCard_Generic
 */
class Addressbook_Convert_Contact_VCard_GenericTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook WebDAV Generic Contact Tests');
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    /**
     * test converting vcard from sogo connector to Addressbook_Model_Contact 
     */
    public function testConvertToTine20Model()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/sogo_connector.vcf', 'r');
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO);
        
        $contact = $converter->toTine20Model($vcardStream);
        
        $this->assertEquals('COUNTRY BUSINESS',        $contact->adr_one_countryname);
        $this->assertEquals('City Business',           $contact->adr_one_locality);
        $this->assertEquals('12345',                   $contact->adr_one_postalcode);
        $this->assertEquals('Region Business',         $contact->adr_one_region);
        $this->assertEquals('Address Business 1',      $contact->adr_one_street);
        $this->assertEquals('Address Business 2',      $contact->adr_one_street2);
        $this->assertEquals('COUNTRY PRIVAT',          $contact->adr_two_countryname);
        $this->assertEquals('City Privat',             $contact->adr_two_locality);
        $this->assertEquals('12345',                   $contact->adr_two_postalcode);
        $this->assertEquals('Region Privat',           $contact->adr_two_region);
        $this->assertEquals('Address Privat 1',        $contact->adr_two_street);
        $this->assertEquals('Address Privat 2',        $contact->adr_two_street2);
        $this->assertEquals('l.kneschke@metaways.de',  $contact->email);
        $this->assertEquals('lars@kneschke.de',        $contact->email_home);
        $this->assertEquals('Kneschke',                $contact->n_family);
        $this->assertEquals('Kneschke, Lars',          $contact->n_fileas);
        $this->assertEquals('Lars',                    $contact->n_given);
        $this->assertEquals(null,                      $contact->n_middle);
        $this->assertEquals(null,                      $contact->n_prefix);
        $this->assertEquals(null,                      $contact->n_suffix);
        $this->assertEquals("Notes\nwith\nLine Break", $contact->note);
        $this->assertEquals('Organisation',            $contact->org_name);
        $this->assertEquals('Business Unit',           $contact->org_unit);
        $this->assertEquals('+49 MOBIL',               $contact->tel_cell);
        $this->assertEquals(null,                      $contact->tel_cell_private);
        $this->assertEquals('+49 FAX',                 $contact->tel_fax);
        $this->assertEquals(null,                      $contact->tel_fax_home);
        $this->assertEquals('+49 PRIVAT',              $contact->tel_home);
        $this->assertEquals('+49 PAGER',               $contact->tel_pager);
        $this->assertEquals('+49 BUSINESS',            $contact->tel_work);
        $this->assertEquals('Titel',                   $contact->title);
        $this->assertEquals('http://www.tine20.com',   $contact->url);
        $this->assertEquals('http://www.tine20.org',   $contact->url_home);
    }            
}
