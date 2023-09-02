<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

class Admin_Import_UserTest extends ImportTestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        Admin_Import_User_Csv::resetResolvedGroups();
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     * @group longrunning
     * @group nodockerci
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

                $this->assertTrue(isset($queryResult['password']), 'no password in result: ' . print_r($queryResult, TRUE));
                $count++;
            }
        }

        self::assertEquals(count($users), $count);
    }

    public function testImportUsersWithGroups()
    {
        $this->_filename = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'user_import_example.csv';
        $this->_deleteImportFile = false;
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('admin_user_import_csv');
        try {
            $result = $this->_doImport([], $definition);
        } finally {
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('PRIMARYGROUP')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('GROUP1')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('GROUP2')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
        }
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        $this->_usernamesToDelete[] = current($this->_instance->getCreatedAccounts())->accountLoginName;
    }

    public function testImportUserWithCharset()
    {
        $this->_filename = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'user_import_macCentralEurope.csv';
        $this->_deleteImportFile = false;
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('admin_user_import_csv');
        $definition->plugin_options = str_replace('</config>', '<encoding>MACCENTRALEUROPE</encoding></config>', $definition->plugin_options);
        try {
            $result = $this->_doImport([], $definition);
        } finally {
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('PRIMARYGROUP')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('GROUP1')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
            try {
                $this->_groupIdsToDelete[] = Tinebase_Group::getInstance()->getGroupByName('GROUP2')->getId();
            } catch (Tinebase_Exception_Record_NotDefined $e) {}
        }
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        $this->_usernamesToDelete[] = current($this->_instance->getCreatedAccounts())->accountLoginName;
    }
}
