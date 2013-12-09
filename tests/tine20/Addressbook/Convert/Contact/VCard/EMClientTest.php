<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Thomas Pawassarat <tomp@topanet.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Convert_Contact_VCard_EMClient
 */
class Addressbook_Convert_Contact_VCard_EMClientTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook WebDAV EMClient Contact Tests');
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
     * 
     * @return Addressbook_Model_Contact
     */
    public function testConvertToTine20Model()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/emclient_addressbook.vcf', 'r');
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_EMCLIENT);
        
        $contact = $converter->toTine20Model($vcardStream);
        
        $this->assertEquals('COUNTRY BUSINESS',        $contact->adr_one_countryname);
        $this->assertEquals('City Business',           $contact->adr_one_locality);
        $this->assertEquals('12345',                   $contact->adr_one_postalcode);
        $this->assertEquals(null,                      $contact->adr_one_region);
        $this->assertEquals('Address Business',        $contact->adr_one_street);
        $this->assertEquals(null,                      $contact->adr_one_street2);
        $this->assertEquals('COUNTRY PRIVAT',          $contact->adr_two_countryname);
        $this->assertEquals('City Privat',             $contact->adr_two_locality);
        $this->assertEquals('98765',                   $contact->adr_two_postalcode);
        $this->assertEquals(null,                      $contact->adr_two_region);
        $this->assertEquals('Address Privat',          $contact->adr_two_street);
        $this->assertEquals(null,                      $contact->adr_two_street2);
        $this->assertEquals('business@email.de',       $contact->email);
        $this->assertEquals('privat@email.de',         $contact->email_home);
        $this->assertEquals('Nach',                    $contact->n_family);
        $this->assertEquals('Nach, Vor',               $contact->n_fileas);
        $this->assertEquals('Vor',                     $contact->n_given);
        $this->assertEquals(null,                      $contact->n_middle);
        $this->assertEquals('Prefix',                  $contact->n_prefix);
        $this->assertEquals(null,                      $contact->n_suffix);
        $this->assertEquals("Notes\nwith\nbreaks",     $contact->note);
        $this->assertEquals('Firma',                   $contact->org_name);
        $this->assertEquals('Abteilung',               $contact->org_unit);
        $this->assertEquals('+49 MOBIL',               $contact->tel_cell);
        $this->assertEquals('+49 MOBIL2',              $contact->tel_cell_private);
        $this->assertEquals('+49 FAX',                 $contact->tel_fax);
        $this->assertEquals('+49 PRIVATFAX',           $contact->tel_fax_home);
        $this->assertEquals('+49 PRIVAT',              $contact->tel_home);
        $this->assertEquals(null,                      $contact->tel_pager);
        $this->assertEquals('+49 BUSINESS',            $contact->tel_work);
        $this->assertEquals('Position',                $contact->title);
        $this->assertEquals('www.business.de',         $contact->url);
        $this->assertEquals(null,                      $contact->url_home);
                
        return $contact;
    }

    public function testConvertToVCard()
    {
        $contact = $this->testConvertToTine20Model();
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_EMCLIENT);
        
        $vcard = $converter->fromTine20Model($contact)->serialize();
        
        // required fields
        $this->assertContains('VERSION:3.0', $vcard, $vcard);
        
        $version = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->version;
        $this->assertContains("PRODID:-//tine20.com//Tine 2.0 Addressbook V$version//EN", $vcard, $vcard);
        
        // @todo can not test for folded lines
        $this->assertContains('ADR;TYPE=HOME:;;Address Privat;City Privat;;98765;COUNTRY PRIVAT', $vcard, $vcard);
        $this->assertContains('ADR;TYPE=WORK:;;Address Business;City Business;;12345;COUNTRY BUSINESS', $vcard, $vcard);
        $this->assertContains('EMAIL:privat@email.de', $vcard, $vcard);
        $this->assertContains('EMAIL;TYPE=PREF:business@email.de', $vcard, $vcard);
        $this->assertContains('N:Nach;Vor;;Prefix', $vcard, $vcard);
        $this->assertContains('NOTE:Notes\nwith\nbreaks', $vcard, $vcard);
        $this->assertContains('ORG:Firma;Abteilung', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=CELL:+49 MOBIL', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=FAX,HOME:+49 PRIVATFAX', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=FAX,WORK:+49 FAX', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=HOME,VOICE:+49 PRIVAT', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=OTHER:+49 MOBIL2', $vcard, $vcard);
        $this->assertContains('TEL;TYPE=WORK,VOICE:+49 BUSINESS', $vcard, $vcard);
        
    }
}
