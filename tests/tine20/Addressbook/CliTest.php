<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Addressbook_Frontend_Cli
 */
class Addressbook_CliTest extends TestCase
{
    /**
     * Backend
     *
     * @var Addressbook_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container = null;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_cli = new Addressbook_Frontend_Cli();
        $this->_container = $this->_getTestContainer('Addressbook', Addressbook_Model_Contact::class);
    }
    
    /**
     * test to set container grants
     */
    public function testSetContainerGrants()
    {
        $this->markTestSkipped('FIXME: 0010343: fix some CLI tests');
        
        $out = $this->_cliHelper(array(
            'containerId=' . $this->_container->getId(), 
            'accountId=' . Tinebase_Core::getUser()->getId(), 
            'grants=privateGrant'
        ));
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
        $this->assertTrue(($grants->getFirstRecord()->privateGrant == 1));
    }

    /**
     * test to set container grants with filter and overwrite old grants
     */
    public function testSetContainerGrantsWithFilterAndOverwrite()
    {
        $this->markTestSkipped('FIXME: 0010343: fix some CLI tests');
        
        $nameFilter = $this->_container->name;
        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 
                'value' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()),
            array('field' => 'name', 'operator' => 'contains', 'value' => $nameFilter),
        ));
        $count = Tinebase_Container::getInstance()->searchCount($filter);
        
        $out = $this->_cliHelper(array(
            'namefilter="' . $nameFilter . '"', 
            'accountId=' . Tinebase_Core::getUser()->getId(), 
            'grants=privateGrant,adminGrant',
            'overwrite=1'
        ), $count);
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
        $this->assertTrue(($grants->getFirstRecord()->privateGrant == 1));
        $this->assertTrue(($grants->getFirstRecord()->adminGrant == 1));
    }
    
    /**
     * call setContainerGrants cli function with params
     * 
     * @param array $_params
     * @return string
     */
    protected function _cliHelper($_params, $_numberExpected = 1)
    {
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments($_params);
        
        ob_start();
        $this->_cli->setContainerGrants($opts);
        $out = ob_get_clean();
        
        $this->assertContains("Updated $_numberExpected container(s)", $out,
                'Text not found in: ' . $out . '(current user: ' . Tinebase_Core::getUser()->accountLoginName . ')');
        
        return $out;
    }

    protected function _doSetListRoleIdByName($_name, $_id)
    {
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments([ '--',
            Addressbook_Frontend_Cli::ROLE_NAME . '=' . $_name,
            Addressbook_Frontend_Cli::ROLE_ID . '=' . $_id,
        ]);

        ob_start();
        $this->_cli->setListRoleIdByName($opts);
        $result = ob_get_clean();
        ob_end_clean();

        $listRole = Addressbook_Controller_ListRole::getInstance()->get($_id);
        static::assertSame($_name, $listRole->name);

        $listRoles = Addressbook_Controller_ListRole::getInstance()->search(new Addressbook_Model_ListRoleFilter([
            ['field' => 'name', 'operator' => 'equals', 'value' => $_name],
        ]));
        static::assertSame(1, $listRoles->count(), 'did\'nt find right amount');
        static::assertSame($_id, $listRoles->getFirstRecord()->getId(), 'ids don\'t match');

        return $result;
    }

    public function testSetListRoleIdByNameNew()
    {
        $result = $this->_doSetListRoleIdByName('fooBar', '73');

        static::assertSame('created role as it didn\'t exist yet' . PHP_EOL, $result);
    }

    public function testSetListRoleIdByNameExistent()
    {
        $listRole = Addressbook_Controller_ListRole::getInstance()->create(new Addressbook_Model_ListRole([
            'name' => 'fooBar',
            'id' => '73',
        ]));
        $result = $this->_doSetListRoleIdByName($listRole->name, $listRole->getId());

        static::assertSame('', $result);
    }

    public function testSetListRoleIdByNameExistent1()
    {
        $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => 'fooBar',
        ]));
        $listRole = Addressbook_Controller_ListRole::getInstance()->create(new Addressbook_Model_ListRole([
            'name' => 'fooBar',
            'id' => '72',
        ]));
        $scleverContactId = $this->_personas['sclever']->contact_id;
        Addressbook_Controller_List::getInstance()->addListMember($list->getId(), $scleverContactId);
        $list->memberroles = [
            ['contact_id' => $scleverContactId, 'list_role_id' => $listRole->getId(), 'list_id' => $list->getId()]
        ];
        $list = Addressbook_Controller_List::getInstance()->update($list);
        static::assertCount(1, $list->memberroles, 'memberroles miscount');

        $result = $this->_doSetListRoleIdByName($listRole->name, '73');

        static::assertSame('', $result);
        $listRoleMember = Addressbook_Controller_List::getInstance()->getMemberRolesBackend()
            ->getByProperty('73', 'list_role_id');
        static::assertSame($scleverContactId, $listRoleMember->contact_id);
        static::assertSame($list->getId(), $list->getId());
    }

    public function testSetListRoleIdByNameExistent2()
    {
        $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => 'fooBar',
        ]));
        Addressbook_Controller_ListRole::getInstance()->create(new Addressbook_Model_ListRole([
            'name' => 'fooBar',
            'id' => '71',
        ]));
        $listRole = Addressbook_Controller_ListRole::getInstance()->create(new Addressbook_Model_ListRole([
            'name' => 'fooBar',
            'id' => '72',
        ]));
        $scleverContactId = $this->_personas['sclever']->contact_id;
        Addressbook_Controller_List::getInstance()->addListMember($list->getId(), $scleverContactId);
        $list->memberroles = [
            ['contact_id' => $scleverContactId, 'list_role_id' => $listRole->getId(), 'list_id' => $list->getId()]
        ];
        $list = Addressbook_Controller_List::getInstance()->update($list);
        static::assertCount(1, $list->memberroles, 'memberroles miscount');

        $result = $this->_doSetListRoleIdByName($listRole->name, '73');

        static::assertSame('', $result);
        $listRoleMember = Addressbook_Controller_List::getInstance()->getMemberRolesBackend()
            ->getByProperty('73', 'list_role_id');
        static::assertSame($scleverContactId, $listRoleMember->contact_id);
        static::assertSame($list->getId(), $list->getId());
    }

    /**
     * testRemoveAutogeneratedContacts
     * 
     * @see 0010257: add cli function for deleting autogenerated contacts
     */
    public function testRemoveAutogeneratedContacts()
    {
        if (! Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            $this->markTestSkipped('only works with Calendar app');
        }
        
        $attenderEmail = 'test@external.org';
        $attenderData = array(
            'email' => $attenderEmail
        );
        Calendar_Model_Attender::resolveEmailToContact($attenderData);

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array());
        $this->_cli->removeAutogeneratedContacts($opts);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'email', 'operator' => 'equals', 'value' => $attenderEmail)
        ));
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);
        $this->assertEquals(0, count($result), 'should not find autogenerated contact any more: ' . print_r($result->toArray(), true));
    }

    /**
     * @group longrunning
     */
    public function testUpdateGeodata()
    {
        // create contact without geodata
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'          => 'PHPUNIT',
            'container_id'      => $this->_container->id,
            'adr_one_street'    => 'Pickhuben 2',
            'adr_one_locality'  => 'Hamburg',
        )));
        self::assertTrue(! isset($contact->adr_one_lon), 'no geodata should be in contact: ' . print_r($contact->toArray(), true));

        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array(
            'containerId=' . $this->_container->getId(),
        ));
        ob_start();
        $this->_cli->updateContactGeodata($opts);
        $out = ob_get_clean();

        // check geodata in contact
        $updatedContact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        self::assertTrue(isset($updatedContact->adr_one_lon), 'no geodata in contact: ' . print_r($updatedContact->toArray(), true));
        self::assertEquals('Updated 1 Record(s)', $out);
    }

    public function testSearchDuplicatesContactByUser()
    {
        $region = Tinebase_Record_Abstract::generateUID();
        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_fileas'          => 'duplicate, test',
            'container_id'      => $this->_container->id,
            'adr_one_street'    => 'Pickhuben 2',
            'adr_one_region'    => $region,
        )));

        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_fileas'          => 'duplicate, test',
            'container_id'      => $this->_container->id,
            'adr_one_street'    => 'Pickhuben 2',
            'adr_one_region'    => $region,
        )));

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_fileas', 'operator' => 'equals', 'value' => 'duplicate, test')
        ));
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);

        self::assertEquals(2, count($result));

        $opts = new Zend_Console_Getopt('abp:');

        $user = Tinebase_Core::getUser();

        $opts->setArguments(array(
            'created_by=' . $user['accountLoginName'],
            'fields=' . 'n_fileas,adr_one_region',
        ));

        ob_start();
        $this->_cli->searchDuplicatesContactByUser($opts);
        ob_get_clean();

        $result = Addressbook_Controller_Contact::getInstance()->search($filter);

        self::assertEquals(1, count($result));
    }
}
