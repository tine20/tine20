<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Addressbook_Convert_Contact_VCard_Generic
 */
class Addressbook_Convert_Contact_VCard_GenericTest extends TestCase
{
    /**
     * test converting vcard from sogo connector to Addressbook_Model_Contact
     * 
     * @return Addressbook_Model_Contact
     */
    public function testConvertToTine20Model()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/sogo_connector.vcf', 'r');
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC);
        
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
        $this->assertContains('CATEGORY 1',            $contact->tags->name);
        $this->assertContains('CATEGORY 2',            $contact->tags->name);
        
        return $contact;
    }

    /**
     * test converting vcard from sogo connector to Addressbook_Model_Contact
     *
     * @return Addressbook_Model_Contact
     */
    public function testConvertToTine20ModelWithoutAddressType()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/without_adr_type.vcf', 'r');

        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC);

        $contact = $converter->toTine20Model($vcardStream);

        $this->assertEquals(null,          $contact->adr_one_countryname, 'no address should be set');
        $this->assertEquals(null,          $contact->adr_two_countryname, 'no address should be set');
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function testConvertToVCard()
    {
        $contact = $this->testConvertToTine20Model();
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC);
        $vcard = $converter->fromTine20Model($contact)->serialize();
        
        // required fields
        $this->assertStringContainsString('VERSION:3.0', $vcard, $vcard);
        
        $version = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->version;
        $this->assertStringContainsString("PRODID:-//tine20.com//Tine 2.0 Addressbook V$version//EN", $vcard, $vcard);
        
        // @todo can not test for folded lines
        $this->assertStringContainsString('ADR;TYPE=HOME:;Address Privat 2;Address Privat 1;City Privat;Region Privat;', $vcard, $vcard);
        $this->assertStringContainsString('ADR;TYPE=WORK:;Address Business 2;Address Business 1;City Business;Region B', $vcard, $vcard);
        $this->assertStringContainsString('EMAIL;TYPE=HOME:lars@kneschke.de', $vcard, $vcard);
        $this->assertStringContainsString('EMAIL;TYPE=WORK:l.kneschke@metaways.de', $vcard, $vcard);
        $this->assertStringContainsString('N:Kneschke;Lars', $vcard, $vcard);
        $this->assertStringContainsString('NOTE:Notes\nwith\nLine Break', $vcard, $vcard);
        $this->assertStringContainsString('ORG:Organisation;Business Unit', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=CELL,WORK:+49 MOBIL', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=FAX,WORK:+49 FAX', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=HOME:+49 PRIVAT', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=WORK:+49 BUSINESS', $vcard, $vcard);
        $this->assertStringContainsString('TITLE:Titel', $vcard, $vcard);
        $this->assertStringContainsString('URL;TYPE=WORK:http://www.tine20.com', $vcard, $vcard);
        $this->assertStringContainsString('URL;TYPE=HOME:http://www.tine20.org', $vcard, $vcard);
        $this->assertStringContainsString('URL;TYPE=HOME:http://www.tine20.org', $vcard, $vcard);
        $this->assertStringContainsString('CATEGORIES:CATEGORY 1,CATEGORY 2', $vcard, $vcard);
    }
}
