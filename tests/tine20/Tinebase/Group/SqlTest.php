<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Group_SqlTest extends TestCase
{
    /**
     * sql user backend
     *
     * @var Tinebase_Group_Sql
     */
    protected $_backend = NULL;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_backend = new Tinebase_Group_Sql();
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        ));
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
        ));
    }
    
    /**
     * try to add a group
     * 
     * @return Tinebase_Model_Group
     */
    public function testAddGroup()
    {
        $group = $this->_backend->addGroup($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
        
        return $group;
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $this->testAddGroup();
        
        $groups = $this->_backend->getGroups('tine20phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $this->testAddGroup();
        
        $group = $this->_backend->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $this->testAddGroup();
        
        $group = $this->_backend->getGroupById($this->objects['initialGroup']->id);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
        
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $this->testAddGroup();
        
        $group = $this->_backend->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to delete a group
     */
    public function testDeleteGroups()
    {
        $this->testAddGroup();
        
        $this->_backend->deleteGroups($this->objects['initialGroup']);

        $this->expectException('Tinebase_Exception_Record_NotDefined');
        $group = $this->_backend->getGroupById($this->objects['initialGroup']);
    }
    
    public function testSetGroupMembershipsWithRecordset()
    {
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit1',
            'description'   => 'group1'
        ));
        
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit2',
            'description'   => 'group2'
        ));
        
        $groupdId1 = $this->_backend->addGroup($groups[0]);
        $groupdId2 = $this->_backend->addGroup($groups[1]);
        
        $accountId = Tinebase_Core::getUser()->getId();
        $oldGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        
        $_groupIds = new Tinebase_Record_RecordSet('Tinebase_Model_Group', $groups);
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, $_groupIds);
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupdId1);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupdId2);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, $oldGroupMemberships);
        $this->_backend->deleteGroups(array($groupdId1, $groupdId2));
        
    }
    
    public function testSetGroupMembershipsWithArray()
    {
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit1',
            'description'   => 'group1'
        ));
        
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit2',
            'description'   => 'group2'
        ));
        
        $groupId1 = $this->_backend->addGroup($groups[0]);
        $groupId2 = $this->_backend->addGroup($groups[1]);
        
        
        $accountId = Tinebase_Core::getUser()->getId();
        $oldGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, array($groupId1->id, $groupId2->id, $groupId1->id));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupId1);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupId2);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, $oldGroupMemberships);
        
        $this->_backend->deleteGroups(array($groupId1, $groupId2));
    }

    /**
     * note, this is in longrunning as the update from far far behind to most advance and then run all test
     * would fail here. Guess that is ok. Though new, clean installations should not
     *
     * @group longrunning
     */
    public function testCleanUpEmptyRun()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        static::assertTrue(empty($str = ob_get_clean()), 'sanitizeGroupListSync echoed: ' . $str);
    }

    public function testCleanUpDuplicateListReference()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        ob_get_clean();

        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $group = Tinebase_Group::getInstance()->addGroup(new Tinebase_Model_Group([
            'name'      => 'unittestgroup',
            'list_id'   => $defaultGroup->list_id,
        ]));

        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        $result = ob_get_clean();

        static::assertFalse(empty($result), 'sanitizeGroupListSync should find the duplicate list reference');
        static::assertEquals('found 1 duplicate list references (fixed)' . PHP_EOL . PHP_EOL
            . 'found 2 groups not having a list (fixed)', trim($result));

        $changedDefaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        static::assertEquals($defaultGroup->list_id, $changedDefaultGroup->list_id,
            'default groups list_id should not change');

        static::assertNotEquals($group->list_id, Tinebase_Group::getInstance()->getGroupById($group->id)->list_id,
            'groups list_id should have changed');
    }

    public function testCleanUpDeletedGroups()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        ob_get_clean();

        $groupController = Tinebase_Group::getInstance();
        $defaultGroup = $groupController->getDefaultGroup();
        $db = Tinebase_Core::getDb();
        $db->update(SQL_TABLE_PREFIX . 'groups', ['is_deleted' => 1], $db->quoteInto($db->quoteIdentifier('id')
            . ' = ?', $defaultGroup->getId()));

        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        $result = ob_get_clean();

        static::assertEquals('found 1 groups which are deleted and linked to undeleted lists: ' . $defaultGroup->getId()
                . PHP_EOL . '(not fixed!)', trim($result));
    }

    public function testCleanUpDeletedLists()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        ob_get_clean();

        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        Addressbook_Controller_List::getInstance()->getBackend()->updateMultiple([$defaultGroup->list_id],
            ['is_deleted' => 1]);

        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        $result = ob_get_clean();

        static::assertEquals('found 1 groups which are linked to deleted lists: ' . $defaultGroup->getId()
            . PHP_EOL . '(not fixed!)', trim($result));
    }

    public function testCleanUpWrongListType()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        ob_get_clean();

        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        Addressbook_Controller_List::getInstance()->getBackend()->updateMultiple([$defaultGroup->list_id],
            ['type' => Addressbook_Model_List::LISTTYPE_LIST]);

        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        $result = ob_get_clean();

        static::assertEquals('found 1 lists linked to groups of the wrong type (fixed)', trim($result));

        $list = Addressbook_Controller_List::getInstance()->getBackend()->get($defaultGroup->list_id);
        static::assertEquals(Addressbook_Model_List::LISTTYPE_GROUP, $list->type);
    }

    public function testCleanUpListWithoutGroup()
    {
        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        ob_get_clean();

        $listController = Addressbook_Controller_List::getInstance();
        $list = $listController->create(new Addressbook_Model_List(['name' => 'unittestlist']));
        $listController->getBackend()->updateMultiple([$list->getId()],
            ['type' => Addressbook_Model_List::LISTTYPE_GROUP]);

        ob_start();
        Tinebase_Group::getInstance()->sanitizeGroupListSync(false);
        $result = ob_get_clean();

        static::assertEquals('changed the following lists from type group to type list:' . PHP_EOL
            . $list->name, trim($result));
        static::assertEquals(Addressbook_Model_List::LISTTYPE_LIST, $listController->getBackend()
            ->get($list->getId())->type);
    }
}
