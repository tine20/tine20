<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    }
    
    /**
     * test import data
     */
    public function testImport()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_import_vcard');
        $this->_instance = Addressbook_Import_VCard::createFromDefinition($definition, array('dryrun' => TRUE));
        
        $result = $this->_instance->importFile($this->_filename);
        //print_r($result['results']->getFirstRecord()->toArray());
                
        $this->assertEquals(2, $result['totalcount'], 'Didn\'t import anything.');
        $this->assertEquals('spass, alex', $result['results']->getFirstRecord()->n_fileas, 'file as not found');
        $this->assertEquals('+49732121258035', $result['results']->getFirstRecord()->tel_home, 'n_fileas not found');
        $this->assertEquals('mitbewohner', $result['results']->getFirstRecord()->note, 'note not found');
        $this->assertEquals('Eisenhüttenstraße 723', $result['results']->getFirstRecord()->adr_one_street, 'street not found');
        $this->assertEquals('http://www.vcard.de', $result['results']->getFirstRecord()->url, 'url not found');
    }
}		
