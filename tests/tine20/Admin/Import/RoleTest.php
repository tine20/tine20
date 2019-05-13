<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Admin_Import_RoleTest extends TestCase
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
        $this->_importContainer = $this->_getTestContainer('Admin', 'Tinebase_Model_Role');
        $importer = new Tinebase_Setup_DemoData_ImportSet('Admin', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('Admin.yml')
        ]);
        $importer->importDemodata();

        $roles = array('Gartenpflege', 'Schloss Admin', 'Vorstand');

        $count = 0;
        foreach ($roles as $role) {
            $filter = Tinebase_Model_RoleFilter::getFilterForModel('Tinebase_Model_Role', [
                ['field' => 'name', 'operator' => 'equals', 'value' => $role]
            ]);
            $count += Admin_Controller_Role::getInstance()->searchCount($filter);
        }
        self::assertEquals(count($roles), $count);
    }
}