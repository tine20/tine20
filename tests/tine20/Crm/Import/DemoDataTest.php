<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Inventory
 */
class Crm_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        Crm_Controller_Lead::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel('Crm_Model_Lead', [
                    ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
                ]
            ));

        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Crm', 'Crm_Model_Lead');
        $importer = new Tinebase_Setup_DemoData_Import('Crm_Model_Lead', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'crm_demo_import_csv',
            'file' => 'lead.csv',
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Crm_Model_Lead', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Crm_Controller_Lead::getInstance()->search($filter);
        self::assertEquals(2, count($result));
    }
}
