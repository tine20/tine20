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

class Admin_Import_GroupTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    /**
     * @group longrunning
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Admin', 'Tinebase_Model_Group');
        $importer = new Tinebase_Setup_DemoData_ImportSet('Admin', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('Admin.yml')
        ]);
        $importer->importDemodata();

        $groups = array('Orcester', 'Vorstand Elbphilharmonie', 'Elbphilharmonie', 'Gaertner', 'Koenig', 'Schloss Schönbrunn');

        $count = null;

        foreach ($groups as $group) {
            // @ToDo not good, but works...
            $conrollerGroup = Admin_Controller_Group::getInstance()->search($group)->_idMap;
            if (!empty($conrollerGroup)) {
                $count++;
            }
        }
        self::assertEquals(count($groups), $count);
    }
}
