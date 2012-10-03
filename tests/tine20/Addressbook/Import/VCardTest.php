<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Import_VCard
 */
class Addressbook_Import_VCardTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Addressbook_Import_VCard instance
     */
    protected $_instance = NULL;
    
    /**
     * @var string $_filename
     */
    protected $_filename = NULL;
    
    /**
     * contact ids to delete in tearDown
     * 
     * @var array
     */
    protected $_contactIdsToDelete = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook VCard Import Tests');
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_filename = dirname(__FILE__) . '/files/contacts.vcf';
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test import data
     */
    public function testImport()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $this->_instance = Addressbook_Import_VCard::createFromDefinition($definition, array('dryrun' => TRUE));
        
        $result = $this->_instance->importFile($this->_filename);
        $this->_contactIdsToDelete = array($result['results']->getArrayOfIds());
        
        $this->assertEquals(2, $result['totalcount'], 'Didn\'t import all contacts.');
        $this->assertEquals('spass, alex', $result['results']->getFirstRecord()->n_fileas, 'file as not found');
        $this->assertEquals('+49732121258035', $result['results']->getFirstRecord()->tel_home, 'n_fileas not found');
        $this->assertEquals('mitbewohner', $result['results']->getFirstRecord()->note, 'note not found');
        $this->assertEquals('Eisenhüttenstraße 723', $result['results']->getFirstRecord()->adr_one_street, 'street not found');
        $this->assertEquals('http://www.vcard.de', $result['results']->getFirstRecord()->url, 'url not found');
    }

    /**
     * test import data #2
     * 
     * @see 0006248: Error with vcard-import
     */
    public function testImportWithUmlaut()
    {
        $this->_filename = dirname(__FILE__) . '/files/contactUmlaut.vcf';
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $this->_instance = Addressbook_Import_VCard::createFromDefinition($definition, array('dryrun' => FALSE));
        
        $result = $this->_instance->importFile($this->_filename);
        
        $importedContact = $result['results']->getFirstRecord();
        $this->assertTrue($importedContact !== NULL);
        $this->assertEquals('Hans Müller', $importedContact->n_fn, print_r($importedContact, TRUE));
    }

    /**
     * test import data #3
     * 
     * @see 0006852: always add iconv filter on import
     * 
     * @return Addressbook_Model_Contact
     */
    public function testImportWithIconv()
    {
        $this->_filename = dirname(__FILE__) . '/files/HerrStephanLaunig.vcf';
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $this->_instance = Addressbook_Import_VCard::createFromDefinition($definition, array('dryrun' => FALSE));
        
        $result = $this->_instance->importFile($this->_filename);
        $this->_contactIdsToDelete = array($result['results']->getArrayOfIds());
        
        $importedContact = $result['results']->getFirstRecord();
        $this->assertTrue($importedContact !== NULL);
        $this->assertEquals('Stephan Läunig', $importedContact->n_fn, print_r($importedContact, TRUE));
        
        return $importedContact;
    }

    /**
     * test import data #4
     * 
     * @see 0006852: always add iconv filter on import
     */
    public function testImportWithIconv2()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $definition->plugin_options = preg_replace('/<\/urlIsHome>/',
            "</urlIsHome>\n<encoding>iso-8859-1</encoding>", $definition->plugin_options);
        $this->_importFalk($definition);
    }
    
    /**
     * import helper for HerrFalkMünchen.vcf
     * 
     * @param Tinebase_Model_ImportExportDefinition $definition
     */
    protected function _importFalk(Tinebase_Model_ImportExportDefinition $definition)
    {
        // file is iso-8859-1 encoded
        $this->_filename = dirname(__FILE__) . '/files/HerrFalkMünchen.vcf';
        $this->_instance = Addressbook_Import_VCard::createFromDefinition($definition, array('dryrun' => FALSE));
        
        $result = $this->_instance->importFile($this->_filename);
        $this->_contactIdsToDelete = array($result['results']->getArrayOfIds());
        
        $importedContact = $result['results']->getFirstRecord();
        
        $this->assertTrue($importedContact !== NULL);
        $this->assertEquals('Falk München', $importedContact->n_fn, print_r($importedContact->toArray(), TRUE));
        $this->assertEquals('Düsseldorf', $importedContact->adr_one_locality, print_r($importedContact->toArray(), TRUE));
    }

    /**
     * test import data #5
     * 
     * @see 0006936: detect import file encoding
     */
    public function testImportDetectEncoding()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $this->_importFalk($definition);
    }
    
    /**
     * test import a duplicate
     * 
     * @see 0006898: duplicate-merging and tag attaching do not work on vcard import
     */
    public function testImportDuplicate()
    {
        $contact = $this->testImportWithIconv();
        
        // import again -> should have duplicate
        $result = $this->_instance->importFile($this->_filename);
        $this->assertEquals(1, $result['duplicatecount'], 'should detect duplicate contact ' . print_r($result, TRUE));
    }
}
