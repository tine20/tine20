<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Test helper
 */


require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';


/**
 * Test class for HumanResources_Export_Ods
 */
class HumanResources_Export_OdsTest extends PHPUnit_Framework_TestCase
{
    /**
     * csv export class
     *
     * @var HumanResources_Export_Ods
     */
    protected $_instance;

    /**
     * export file
     *
     * @var string
     */
    protected $_filename;

    /**
     * the import definition used for this test
     *
     * @var Tinebase_Model_ImportExportDefinition
     */
    protected $_importDefinition;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $fe = new Tinebase_Frontend_Cli();
        
        $opts = new Zend_Console_Getopt('abp:');
        $path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tine20/tine20/HumanResources/Export/definitions/hr_default_ods.xml';
        $opts->setArguments(array($path));
        
        ob_start();
        $fe->updateImportExportDefinition($opts);
        $output = ob_get_clean();
        
        $this->assertContains('hr_default_ods.xml successfully.', $output);
        
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, array(
            array('field' => 'name', 'operator' => 'equals', 'value' => 'hr_default_ods')
        ));
        $backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_ImportExportDefinition',
            'tableName' => 'importexport_definition'
        ), NULL);

        $this->_importDefinition = $backend->search($filter)->getFirstRecord();

        $filter = new HumanResources_Model_EmployeeFilter(array(
        ));

        $options = array('definitionId' => $this->_importDefinition->getId());

        $this->_instance = Tinebase_Export::factory($filter, $options);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        unlink($this->_filename);
        parent::tearDown();
    }

    /**
     * test ods export
     *
     * @return void
     *
     * @todo add assertions for relations / other fields
     */
    public function testExportOds()
    {
        $this->assertEquals('ods', $this->_instance->getFormat());
        $this->_filename = $this->_instance->generate();
        $this->assertTrue(file_exists($this->_filename));

        $xmlBody = $this->_instance->getDocument()->asXML();
        $this->assertEquals(1, preg_match("/Admin Account, Tine 2.0/", $xmlBody));
    }
}
