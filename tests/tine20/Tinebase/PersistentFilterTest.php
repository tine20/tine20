<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_PersistentFilter
 */
class Tinebase_PersistentFilterTest extends TestCase
{
    /**
     * unit under test (UIT)
     * 
     * @var Tinebase_PersistentFilter
     */
    protected $_instance;
    
    /**
     * the test user, if this is not null, restore the user in tearDown
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_restoreTestUser = null;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = Tinebase_PersistentFilter::getInstance();
        parent::setUp();
    }

    /**
     * tear down tests
     */
    protected function tearDown()
    {
        if ($this->_restoreTestUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_restoreTestUser);
        }
        
        parent::tearDown();
    }
    
    /**
     * save personal persistent filter (should add user with all grants by default)
     */
    public function testSavePersonalFavorite()
    {
        $filter = new Tinebase_Model_PersistentFilter(Tinebase_Frontend_Json_PersistentFilterTest::getPersistentFilterData());
        $newFilter = $this->_instance->create($filter);
        
        $this->assertTrue(count($newFilter->grants) === 1, 'did not find default grants in filter: '
            . print_r($newFilter->toArray(), true));
        
        $grant = $newFilter->grants->getFirstRecord();
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $grant->account_id);
        foreach (array(
            Tinebase_Model_PersistentFilterGrant::GRANT_READ,
            Tinebase_Model_PersistentFilterGrant::GRANT_EDIT,
            Tinebase_Model_PersistentFilterGrant::GRANT_DELETE
        ) as $expectedGrant) {
            $this->assertTrue($grant->$expectedGrant);
        }
    }
    
    /**
     * save shared persistent filter
     * 
     * @return Tinebase_Model_PersistentFilter
     */
    public function testSaveSharedFavorite()
    {
        $filter = new Tinebase_Model_PersistentFilter(
            Tinebase_Frontend_Json_PersistentFilterTest::getPersistentFilterData()
        );
        $filter->account_id = null;
        $newFilter = $this->_instance->create($filter);
        
        $this->assertTrue(count($newFilter->grants) === 2, 'did not find default grants in filter: '
            . print_r($newFilter->toArray(), true));

        $filterArray = $newFilter->toArray();
        $this->assertEquals(Tinebase_Model_User::CURRENTACCOUNT, $filterArray['filters'][2]['value'],
            print_r($filterArray['filters'], true));

        return $newFilter;
    }
    
    /**
     * test read grant
     * 
     * @return Tinebase_Model_PersistentFilter
     */
    public function testReadGrant()
    {
        $filter = $this->testSaveSharedFavorite();
        
        $this->_restoreTestUser = Tinebase_Core::getUser();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Setting sclever (id: ' . $sclever->getId() .') as current user');
        
        $filterAsSeenBySclever = $this->_instance->get($filter->getId());
        $this->assertEquals($filter->name, $filterAsSeenBySclever->name);
        
        return $filterAsSeenBySclever;
    }

    /**
     * test edit grant
     */
    public function testEditGrant()
    {
        $filterAsSeenBySclever = $this->testReadGrant();
        $filterAsSeenBySclever->name = 'new name';
        try {
            $this->_instance->update($filterAsSeenBySclever);
            $this->fail('sclever should not be able to edit filter');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('No permission to update record.', $tead->getMessage());
        }
    }

    /**
     * test delete grant
     */
    public function testDeleteGrant()
    {
        $filterAsSeenBySclever = $this->testReadGrant();
        try {
            $deletedIds = $this->_instance->delete($filterAsSeenBySclever->getId());
            if (count($deletedIds) > 0) {
                $this->fail('sclever should not be able to delete filter');
            } else {
                $this->fail('delete filter did not work: ' . print_r($filterAsSeenBySclever->toArray(), true));
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('No Permission.', $tead->getMessage());
        }
    }
    
    /**
     * test remove all grants (should not be possible)
     */
    public function testRemoveAllGrants()
    {
        $filter = $this->testSaveSharedFavorite();
        $filter->grants = new Tinebase_Record_RecordSet('Tinebase_Model_PersistentFilterGrant',array(array(
            'account_id'       => Tinebase_Core::getUser()->getId(),
            'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'record_id'        => $filter->getId(),
            Tinebase_Model_Grants::GRANT_READ   => true,
        )));
        $updatedFilter = $this->_instance->update($filter);
        $grant = $updatedFilter->grants->filter('account_id', Tinebase_Core::getUser()->getId())->getFirstRecord();
        
        $this->assertTrue($grant !== null);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $grant->account_id);
        
        $this->assertTrue($grant->userHasGrant(Tinebase_Model_PersistentFilterGrant::GRANT_READ));
        $this->assertTrue($grant->userHasGrant(Tinebase_Model_PersistentFilterGrant::GRANT_EDIT), 'edit grant should not be removed: ' . print_r($grant->toArray(), true));
        $this->assertTrue($grant->userHasGrant(Tinebase_Model_PersistentFilterGrant::GRANT_DELETE), 'delete grant should not be removed: ' . print_r($grant->toArray(), true));
    }
    
    /**
     * test remove all grants (should not be possible)
     */
    public function testAddGrants()
    {
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $filter = $this->testSaveSharedFavorite();
        $filter->grants->addRecord(new Tinebase_Model_PersistentFilterGrant(array(
            'account_id'       => $defaultUserGroup->getId(),
            'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            'record_id'        => $filter->getId(),
            Tinebase_Model_Grants::GRANT_READ   => true,
        )));
        $updatedFilter = $this->_instance->update($filter);
        
        $this->assertEquals(3, count($updatedFilter->grants));
        $grant = $updatedFilter->grants->filter('account_id', $defaultUserGroup->getId())->getFirstRecord();
        $this->assertTrue($grant !== null);
        $this->assertTrue($grant->userHasGrant(Tinebase_Model_PersistentFilterGrant::GRANT_READ));
    }

    /**
     * testCurrentAccountValue: checks if organizer is "magic word" Tinebase_Model_User::CURRENTACCOUNT
     *
     * @see 0011090: Aufgaben - Favoriten - Falscher Verantwortlicher
     */
    public function testCurrentAccountValue()
    {
        // look at default task favorites: currentAccount should be set as value
        foreach (array('My open tasks', 'All tasks for me') as $filterName) {
            $filter = new Tinebase_Model_PersistentFilterFilter(array(
                //array('field' => 'account_id',      'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()),
                array(
                    'field' => 'name',
                    'operator' => 'equals',
                    'value' => $filterName
                ),
                array(
                    'field' => 'application_id',
                    'operator' => 'equals',
                    'value' => Tinebase_Application::getInstance()->getApplicationById('Tasks')->getId()
                ),
            ));

            $result = Tinebase_PersistentFilter::getInstance()->search($filter)->getFirstRecord();

            $this->assertTrue($result !== null);
            $filters = $result->toArray();
            $filters = $filters['filters'];
            $this->assertEquals(Tinebase_Model_User::CURRENTACCOUNT, $filters[0]['value'], print_r($filters, true));
        }
    }
}
