<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Inventory
 */
class Inventory_Import_CsvTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Inventory_Import_Csv instance
     */
    protected $_instance = NULL;
    
    /**
     * @var string $_filename
     */
    protected $_filename = NULL;
    
    /**
     * @var boolean
     */
    protected $_deleteImportFile = TRUE;
    
    protected $_deletePersonalInventoryItems = FALSE;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Inventory Csv Import Tests');
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
        Inventory_Controller_InventoryItem::getInstance()->resolveCustomfields(TRUE);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // cleanup
        if (file_exists($this->_filename) && $this->_deleteImportFile) {
            unlink($this->_filename);
        }
        
        if ($this->_deletePersonalInventoryItems) {
            Inventory_Controller_InventoryItem::getInstance()->deleteByFilter(new Inventory_Model_InventoryItemFilter(array(array(
                'field' => 'container_id', 'operator' => 'equals', 'value' => Inventory_Controller_InventoryItem::getInstance()->getDefaultInventory()->getId()
            ))));
        }
    }
    
    /**
     * test import of a csv
     *
     */
    public function testImportOfCSV()
    {
        $filename = dirname(__FILE__) . '/files/inv_tine_import_csv.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(__FILE__) . '/files/inv_tine_import.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array(), $definition);
        $this->_deletePersonalInventoryItems = TRUE;
        
        // There are two test entries, so check for 2 imported entries
        $this->assertEquals(2, $result['totalcount']);
        
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        
        $this->assertEquals($result['results'][0]['name'], 'Tine 2.0 für Einsteiger');
        $this->assertEquals($result['results'][0]['added_date'], '2013-01-11 00:00:00');
        $this->assertEquals($result['results'][0]['inventory_id'], '12345');
        $this->assertContains($translation->_("The following fields weren't imported: \n"), $result['results'][0]['description']);
        
        $this->assertEquals($result['results'][1]['name'], 'Tine 2.0 für Profis');
        $this->assertEquals($result['results'][1]['added_date'], '2012-01-11 00:00:00');
        $this->assertEquals($result['results'][1]['inventory_id'], '1333431666');
        $this->assertContains($translation->_("The following fields weren't imported: \n"), $result['results'][1]['description']);
        
    }
    
    /**
     * import helper
     *
     * @param array $_options
     * @param string|Tinebase_Model_ImportExportDefinition $_definition
     * @param Inventory_Model_InventoryItemFilter $_exportFilter
     * @return array
     */
    protected function _doImport(array $_options, $_definition, Inventory_Model_InventoryItemFilter $_exportFilter = NULL)
    {
        $definition = ($_definition instanceof Tinebase_Model_ImportExportDefinition) ? $_definition : Tinebase_ImportExportDefinition::getInstance()->getByName($_definition);
        $this->_instance = Inventory_Import_Csv::createFromDefinition($definition, $_options);

        // export first
        if ($_exportFilter !== NULL) {
            $exporter = new Inventory_Export_Csv($_exportFilter, Inventory_Controller_InventoryItem::getInstance());
            $this->_filename = $exporter->generate();
        }
        
        // then import
        $result = $this->_instance->importFile($this->_filename);
        
        return $result;
    }
}
