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

    /**
     * @group longrunning
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Admin', 'Tinebase_Model_Role');
        $importer = new Tinebase_Setup_DemoData_ImportSet('Admin', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('Admin.yml')
        ]);
        $importer->importDemodata();

        $roles = array('Gartenpflege', 'Schloss Admin', 'Vorstand');

        $paging = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 0,

        ));

        $count = 0;
        foreach ($roles as $role) {
            $filter = Tinebase_Model_RoleFilter::getFilterForModel('Tinebase_Model_Role', [
                ['field' => 'name', 'operator' => 'equals', 'value' => $role]
            ]);
            $role = Admin_Controller_Role::getInstance()->search($filter, $paging);
            if ($role) {
                $count++;
                $rolerights = Admin_Controller_Role::getInstance()->getRoleRights($role->getFirstRecord()->getId());
                if ($role->getFirstRecord()['name'] == 'Vorstand') {
                    self::assertEquals(0, count($rolerights));
                }else {
                    self::assertGreaterThan(0, count($rolerights));
                }
            }
        }
        self::assertEquals(count($roles), $count);
    }

    public function testUpdateImportRole()
    {
        $this->testImportDemoData();

        if (!extension_loaded('yaml')) {
            self::markTestSkipped('yml extension is missing');
        }

        $path = dirname(__FILE__) . '/files/rights.yml';

        if (file_exists($path)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Importing DemoData set from file ' . $path);
            $setData = yaml_parse_file($path);

            $set = $setData['updateRoleRights'];

            $roleRights = [];
            // resolve rights
            foreach ($set as $data) {
                $data = explode('/', $data);
                try {
                    $appId = Tinebase_Application::getInstance()->getApplicationByName($data[0])->getId();
                    $rightsData = explode(',', $data[1]);
                    foreach ($rightsData as $right) {
                        $roleRights[] = array('application_id' => $appId, 'right' => $right);
                    }

                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice
                    (__METHOD__ . '::' . __LINE__ . 'Application "' . $data[0] .
                        '" not installed. Skipping...' . PHP_EOL);
                }
            }
        }
        $paging = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 0,

        ));
        $filter = Tinebase_Model_RoleFilter::getFilterForModel('Tinebase_Model_Role', [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'Gartenpflege']
        ]);
        $role = Admin_Controller_Role::getInstance()->search($filter, $paging);

        Tinebase_Role::getInstance()->setRoleRights($role->getFirstRecord()->getId(), $roleRights);
        $rolerights = Admin_Controller_Role::getInstance()->getRoleRights($role->getFirstRecord()->getId());
        self::assertGreaterThan(8, count($rolerights));
    }
}