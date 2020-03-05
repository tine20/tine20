<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * Test class for Inventory
 */
class HumanResources_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    /**
     * @group longrunning
     * @group nogitlabci
     * @throws Tinebase_Exception_InvalidArgument
     *
     * @group nogitlabci
     */
    public function testImportDemoData()
    {
        $this->_skipIfLDAPBackend();

        $now = Tinebase_DateTime::now();
        $this->_importContainer = $this->_getTestContainer('HumanResources', 'HumanResources_Model_Employee');
        $importer = new Tinebase_Setup_DemoData_ImportSet('HumanResources', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('hr.yml')
        ]);

        $importer->importDemodata();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('HumanResources_Model_Employee', [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = HumanResources_Controller_Employee::getInstance()->search($filter);
        self::assertEquals(2, count($result));
    }
}
