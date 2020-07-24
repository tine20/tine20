<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract Test class for Tinebase_Admin frontend tests
 */
abstract class Admin_Frontend_TestCase extends TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Json
     */
    protected $_json;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_json = new Admin_Frontend_Json();

        $this->objects['role'] = new Tinebase_Model_Role(array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
    }
    
    protected function tearDown()
    {
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::ANYONE_ACCOUNT_DISABLED, false);
    }

    protected function _getInternalAddressbook()
    {
        return Tinebase_Container::getInstance()->getContainerByName(
            Addressbook_Model_Contact::class,
            'Internal Contacts',
            Tinebase_Model_Container::TYPE_SHARED
        );
    }

    protected function _createGroup()
    {
        $group = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunitgroup',
            'description'   => 'initial group',
            'members'       => [],
        ));
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $group->container_id = $this->_getInternalAddressbook()->getId();
        }
        $groupArray = $this->_json->saveGroup($group->toArray());
        $this->_groupIdsToDelete[] = $groupArray['id'];
        return $groupArray;
    }

    protected function _getRole()
    {
        return new Tinebase_Model_Role(array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
    }
}
