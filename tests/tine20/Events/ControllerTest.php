<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Events
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for Events_ControllerTest
 */
class Events_ControllerTest extends Events_TestCase
{
    /**
     * @var array
     */
    protected $_groupIdsToDelete = array();

    /**
     * set up tests
     */
    protected function setUp()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $this->markTestSkipped('currently not working with PGSQL');
        }

        parent::setUp();
    }
    
    /**
     * @throws Exception
     * @throws Tinebase_Exception_Duplicate
     * @throws Tinebase_Exception_NotFound
     */
    public function testListCreatesEventContainer()
    {
        // create new group
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group(array(
            'name' => 'tine20phpunit group',
            'description' => 'test group'
        )));

        $this->_groupIdsToDelete[] = $group->getId();

        try {
            $eventContainer = Tinebase_Container::getInstance()->getContainerByName(
                'Events_Model_Event',
                $group->name,
                Tinebase_Model_Container::TYPE_SHARED
            );
            $this->fail('non-department lists should not get a container: ' . print_r($eventContainer->toArray(), true));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->assertTrue($tenf instanceof Tinebase_Exception_NotFound);
        }

        $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $list->list_type = 'DEPARTMENT';
        Addressbook_Controller_List::getInstance()->update($list);

        $eventContainer = Tinebase_Container::getInstance()->getContainerByName(
            'Events_Model_Event',
            $group->name,
            Tinebase_Model_Container::TYPE_SHARED
        );
        $this->assertTrue($eventContainer instanceof Tinebase_Model_Container);

        $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($eventContainer);
        $this->assertEquals(2, count($containerGrants), 'should have admin and group grants: ' . print_r($containerGrants->toArray(), true));

        return $list;
    }

    public function testListDeleteRemovesEventContainerGrants()
    {
        $list = $this->testListCreatesEventContainer();
        Addressbook_Controller_List::getInstance()->delete(array($list->getId()));
        $this->_groupIdsToDelete = array();

        $container = Tinebase_Container::getInstance()->getContainerByName(
            'Events_Model_Event',
            $list->name,
            Tinebase_Model_Container::TYPE_SHARED
        );
        $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($container);
        $this->assertEquals(1, count($containerGrants), 'should only have admin grants: ' . print_r($containerGrants->toArray(), true));
    }
}
