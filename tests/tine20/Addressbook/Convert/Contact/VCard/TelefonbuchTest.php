<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <kontakt@michaelspahn.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Convert_Contact_VCard_TelefonbuchTest::main');
}

/**
 * Test class for Addressbook_Convert_Contact_VCard_TelefonbuchTest
 */
class Addressbook_Convert_Contact_VCard_TelefonbuchTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook WebDAV Telefonbuch Contact Tests');
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
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/telefonbuch.vcf', 'r');

        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_TELEFONBUCH);

        $contact = $converter->toTine20Model($vcardStream);

        $this->assertEquals('Hamburg',                 $contact->adr_one_locality);
        $this->assertEquals('12345',                   $contact->adr_one_postalcode);
        $this->assertEquals('Teststraße 1',            $contact->adr_one_street);
        $this->assertEquals('Spahn',                   $contact->n_family);
        $this->assertEquals('Spahn, Michael',          $contact->n_fileas);
        $this->assertEquals('Michael',                 $contact->n_given);
        $this->assertEquals('040 12345',               $contact->tel_work);
        $this->assertEquals('http://michaelspahn.de', $contact->url);

        return $contact;
    }
}
