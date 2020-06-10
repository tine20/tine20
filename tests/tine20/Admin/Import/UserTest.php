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

class Admin_Import_UserTest extends TestCase
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
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     * @group longrunning
     */
    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Admin', 'Tinebase_Model_FullUser');
        $importer = new Tinebase_Setup_DemoData_Import('Admin', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'admin_user_import_csv',
            'file' => 'user.csv',
        ]);
        $importer->importDemodata();

        $users = array('s.rattle', 's.fruehauf', 't.bar', 'j.baum', 'j.metzger', 'e.eichmann', 'm.schreiber');
        $count = 0;
        foreach ($users as $user) {
            $record = Admin_Controller_User::getInstance()->searchFullUsers($user)->getFirstRecord();
            if ($record) {
                $db = Tinebase_Core::getDb();
                $select = $db->select()
                    ->from(array($db->table_prefix . 'accounts'))
                    ->where($db->quoteIdentifier('id') . ' = ?', $record->getId());
                $stmt = $db->query($select);
                $queryResult = $stmt->fetch();
                $stmt->closeCursor();

                $this->assertTrue(isset($queryResult['pin']), 'no password in result: ' . print_r($queryResult, TRUE));
                $count++;
            }
        }

        self::assertEquals(count($users), $count);
    }
}