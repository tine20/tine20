<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';


/**
 * Test class for Addressbook_Convert_Contact_VCard_Sogo
 */
class Addressbook_Convert_Contact_VCard_WebDAVCollaboratorTest extends \PHPUnit\Framework\TestCase
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
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook WebDAV Collaborator Contact Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }
    
    /**
     * test converting vcard from sogo connector to Addressbook_Model_Contact 
     * 
     * @return Addressbook_Model_Contact
     */
    public function testConvertToTine20Model()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/WebDAVCollaborator.vcf', 'r');
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_COLLABORATOR);
        
        $contact = $converter->toTine20Model($vcardStream);
        
        $this->assertEquals('DEUTSCHLAND',        $contact->adr_one_countryname);
        $this->assertEquals('businesscity',       $contact->adr_one_locality);
        $this->assertEquals('45734',              $contact->adr_one_postalcode);
        $this->assertEquals('businessarea',       $contact->adr_one_region);
        $this->assertEquals('business street 2',  $contact->adr_one_street);
//         $this->assertEquals('Address Business 2',      $contact->adr_one_street2);
        $this->assertEquals('DEUTSCHLAND',        $contact->adr_two_countryname);
        $this->assertEquals('private cyty',       $contact->adr_two_locality);
        $this->assertEquals('44322',              $contact->adr_two_postalcode);
        $this->assertEquals('private area',       $contact->adr_two_region);
        $this->assertEquals('private street 44',  $contact->adr_two_street);
//         $this->assertEquals('Address Privat 2',        $contact->adr_two_street2);
        $this->assertEquals('mail@example.com',   $contact->email);
        $this->assertEquals('mail2@example.com',  $contact->email_home);
        $this->assertEquals('Nachname',           $contact->n_family);
        $this->assertEquals('Vorname',            $contact->n_given);
        $this->assertEquals('Middle',             $contact->n_middle);
        $this->assertEquals('salutation',         $contact->n_prefix);
        $this->assertEquals('name suffix',        $contact->n_suffix);
        $this->assertEquals("Nots \nWith \nLine\nBreaks\n", $contact->note);
        $this->assertEquals('Firma',              $contact->org_name);
        $this->assertEquals('department',         $contact->org_unit);
        $this->assertEquals('+49 (040) 12345 - 3', $contact->tel_cell);
        $this->assertEquals(null,                 $contact->tel_cell_private);
        $this->assertEquals('+49 (040) 12345 - 2', $contact->tel_fax);
        $this->assertEquals(null,                 $contact->tel_fax_home);
        $this->assertEquals('+49 (040) 12345 - 1', $contact->tel_home);
        $this->assertEquals(null,                 $contact->tel_pager);
        $this->assertEquals('+49 (040) 12345 - 0', $contact->tel_work);
//         $this->assertEquals('Titel',                   $contact->title);
        $this->assertEquals('websi.te',           $contact->url);
//         $this->assertEquals('http://www.tine20.org',   $contact->url_home);
        
        return $contact;
    }

//     public function testConvertToVCard()
//     {
//         $contact = $this->testConvertToTine20Model();
        
//         $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO);
        
//         $vcard = $converter->fromTine20Model($contact)->serialize();
        
//         // required fields
//         $this->assertStringContainsString('VERSION:3.0', $vcard, $vcard);
//         $this->assertStringContainsString('PRODID:-//tine20.com//Tine 2.0//EN', $vcard, $vcard);
        
//         // @todo can not test for folded lines
//         $this->assertStringContainsString('ADR;TYPE=HOME:;Address Privat 2;Address Privat 1;City Privat;Region Privat;', $vcard, $vcard);
//         $this->assertStringContainsString('ADR;TYPE=WORK:;Address Business 2;Address Business 1;City Business;Region B', $vcard, $vcard);
//         $this->assertStringContainsString('EMAIL;TYPE=HOME:lars@kneschke.de', $vcard, $vcard);
//         $this->assertStringContainsString('EMAIL;TYPE=WORK:l.kneschke@metaways.de', $vcard, $vcard);
//         $this->assertStringContainsString('N:Kneschke;Lars', $vcard, $vcard);
//         $this->assertStringContainsString('NOTE:Notes\nwith\nLine Break', $vcard, $vcard);
//         $this->assertStringContainsString('ORG:Organisation;Business Unit', $vcard, $vcard);
//         $this->assertStringContainsString('TEL;TYPE=CELL:+49 MOBIL', $vcard, $vcard);
//         $this->assertStringContainsString('TEL;TYPE=FAX:+49 FAX', $vcard, $vcard);
//         $this->assertStringContainsString('TEL;TYPE=HOME:+49 PRIVAT', $vcard, $vcard);
//         $this->assertStringContainsString('TEL;TYPE=PAGER:+49 PAGER', $vcard, $vcard);
//         $this->assertStringContainsString('TEL;TYPE=WORK:+49 BUSINESS', $vcard, $vcard);
//         $this->assertStringContainsString('TITLE:Titel', $vcard, $vcard);
//         $this->assertStringContainsString('URL;TYPE=WORK:http://www.tine20.com', $vcard, $vcard);
//         $this->assertStringContainsString('URL;TYPE=HOME:http://www.tine20.org', $vcard, $vcard);
//         #$this->assertStringContainsString('BDAY:1975-01-16', $vcard, $vcard);
//     }
}
