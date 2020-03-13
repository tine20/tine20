<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * Test class for Inventory
 */
class Addressbook_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');
        $importer = new Tinebase_Setup_DemoData_Import('Addressbook_Model_Contact', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'adb_tine_import_csv',
            'file' => 'Contact.csv'
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Addressbook_Model_Contact', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);
        self::assertEquals(7, count($result));
    }


    public function testImportDemoDataViaCli()
    {
        self::markTestSkipped('FIXME: this fails at random');

        $this->_cli = new Addressbook_Frontend_Cli();
        $out = $this->_appCliHelper('Addressbook', 'createDemoData', []);
        echo $out;

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Addressbook_Model_Contact', [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);
        self::assertGreaterThanOrEqual(18, count($result));
    }
}